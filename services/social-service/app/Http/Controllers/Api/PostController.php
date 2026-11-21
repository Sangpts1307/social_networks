<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ResponseAPI;
use App\Http\Controllers\Controller;
use App\Services\SocialAccess;
use App\Services\SocialOutbox;
use App\Services\SocialPagination;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function __construct(private SocialAccess $access, private SocialPagination $pages, private SocialOutbox $events) {}

    public function index(Request $request)
    {
        $actor = $this->access->actor($request);
        $filters = $request->validate(['channel_id' => ['sometimes', 'uuid'], 'author_user_id' => ['sometimes', 'uuid'], 'saved' => ['sometimes', 'in:0,1'], 'post_type' => ['sometimes', 'in:update,news,announcement,event'], 'q' => ['sometimes', 'string', 'min:2', 'max:200']]);
        $q = $this->readQuery($actor)->where('p.publication_status', 'published')->where('p.moderation_status', 'clear');
        if (isset($filters['channel_id'])) {
            $this->access->channel($actor, $filters['channel_id']);
            $q->where('p.channel_id', $filters['channel_id']);
        }
        if (isset($filters['author_user_id'])) {
            $q->where('p.author_user_id', $filters['author_user_id']);
        }
        if (isset($filters['post_type'])) {
            $q->where('p.post_type', $filters['post_type']);
        }
        if (isset($filters['q'])) {
            $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($filters['q'])).'%';
            $q->where(function (Builder $search) use ($term): void {
                $search->whereRaw("p.title LIKE ? ESCAPE '!'", [$term])->orWhereRaw("p.body_plaintext LIKE ? ESCAPE '!'", [$term]);
            });
        }
        if (($filters['saved'] ?? '0') === '1') {
            $q->whereExists(fn (Builder $s) => $s->selectRaw('1')->from('post_saves as s')->whereColumn('s.post_id', 'p.id')->where('s.user_id', $actor['user_id']));
        }
        ksort($filters);

        return response()->json($this->pages->page($q, $request, 'p.published_at', 'p.id', 'posts:'.json_encode($filters)));
    }

    public function show(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        // Published content only: drafts are returned by create/update, never by the public detail route.
        $this->access->post($actor, $postId);

        return ResponseAPI::success($this->readQuery($actor)->where('p.id', $postId)->first());
    }

    public function drafts(Request $request)
    {
        $actor = $this->access->actor($request);
        $q = $this->readQuery($actor)->where('p.author_user_id', $actor['user_id'])
            ->where('p.publication_status', 'draft')->where('p.moderation_status', 'clear');
        return response()->json($this->pages->page($q, $request, 'p.created_at', 'p.id', 'posts:drafts'));
    }

    public function draft(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $this->access->post($actor, $postId, owner: true);
        $post = $this->readQuery($actor)->where('p.id', $postId)->where('p.publication_status', 'draft')->first();
        abort_unless($post, 404);
        return ResponseAPI::success($post);
    }

    public function store(Request $request)
    {
        $actor = $this->access->actor($request);
        $data = $request->validate([
            'channel_id' => ['required', 'uuid'], 'post_type' => ['sometimes', 'in:update,news,announcement,event'],
            'title' => ['nullable', 'string', 'max:500'], 'body_markdown' => ['required', 'string', 'max:500000'],
            'publication_status' => ['sometimes', 'in:draft,published'],
            'event' => ['required_if:post_type,event', 'prohibited_unless:post_type,event', 'array'],
            'event.starts_at' => ['required_if:post_type,event', 'date', 'after:now'],
            'event.ends_at' => ['required_if:post_type,event', 'date', 'after:event.starts_at'],
            'event.location_text' => ['nullable', 'string', 'max:255'],
            'event.rsvp_enabled' => ['sometimes', 'boolean'],
            'event.capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'event.registration_deadline_at' => ['nullable', 'date', 'after:now', 'before_or_equal:event.starts_at'],
            'organization_id' => ['prohibited'], 'author_user_id' => ['prohibited'], 'visibility' => ['prohibited'],
        ]);
        if (isset($data['event'])) {
            foreach (['starts_at', 'ends_at', 'registration_deadline_at'] as $field) {
                if (!empty($data['event'][$field])) $data['event'][$field] = \Carbon\Carbon::parse($data['event'][$field])->utc();
            }
        }
        $type = $data['post_type'] ?? 'update';
        $this->publishingPermission($actor, $type, $data['title'] ?? null);
        abort_unless(trim(strip_tags($data['body_markdown'])) !== '', 422, 'Post body is empty.');

        return DB::transaction(function () use ($actor, $data, $type) {
            $channel = $this->access->channel($actor, $data['channel_id'], true, true);
            $id = (string) Str::uuid();
            $status = $data['publication_status'] ?? 'published';
            $moderationStatus = 'clear';

            $moderation = app(\App\Services\ContentModerationService::class)->inspect($data['title'] ?? null, $data['body_markdown']);
            if ($moderation['is_flagged']) {
                if ($moderation['should_auto_hide']) {
                    $status = 'hidden';
                    $moderationStatus = 'blocked';
                } else {
                    $moderationStatus = 'flagged';
                }
            }

            DB::table('posts')->insert([
                'id' => $id, 'organization_id' => $actor['organization_id'], 'channel_id' => $channel->id,
                'author_user_id' => $actor['user_id'], 'created_by_user_id' => $actor['user_id'], 'post_type' => $type,
                'title' => isset($data['title']) ? strip_tags($data['title']) : null,
                'body_markdown' => strip_tags($data['body_markdown']), 'body_plaintext' => trim(strip_tags($data['body_markdown'])),
                'visibility' => $channel->visibility === 'organization' ? 'organization' : 'channel',
                'publication_status' => $status, 'moderation_status' => $moderationStatus,
                'published_at' => in_array($status, ['published', 'hidden'], true) ? now() : null, 'created_at' => now(), 'updated_at' => now(),
            ]);

            if ($moderation['is_flagged']) {
                $reportId = (string) Str::uuid();
                DB::table('post_reports')->insert([
                    'id' => $reportId,
                    'organization_id' => $actor['organization_id'],
                    'target_type' => 'post',
                    'target_id' => $id,
                    'reporter_user_id' => null,
                    'report_reason_code' => $moderation['reason_code'],
                    'report_detail' => $moderation['detail'],
                    'source' => 'ai',
                    'risk_score' => $moderation['risk_score'],
                    'status' => 'open',
                    'opened_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($moderation['should_auto_hide']) {
                    DB::table('moderation_actions')->insert([
                        'id' => (string) Str::uuid(),
                        'report_id' => $reportId,
                        'target_type' => 'post',
                        'target_id' => $id,
                        'action_type' => 'hide',
                        'reason_code' => $moderation['reason_code'],
                        'note' => $moderation['detail'],
                        'actor_type' => 'ai',
                        'actor_user_id' => null,
                        'previous_state_json' => json_encode(['publication_status' => 'published', 'moderation_status' => 'clear']),
                        'new_state_json' => json_encode(['publication_status' => 'hidden', 'moderation_status' => 'blocked']),
                        'created_at' => now(),
                    ]);
                }
            }

            if ($type === 'event') DB::table('post_events')->insert(['post_id' => $id, ...array_intersect_key($data['event'], array_flip(['starts_at', 'ends_at', 'location_text', 'rsvp_enabled', 'capacity', 'registration_deadline_at'])), 'created_at' => now(), 'updated_at' => now()]);
            $this->events->record($actor, 'post', $id, 'post.'.$status, ['channel_id' => $channel->id]);
            if ($type === 'announcement' && $status === 'published') $this->announcementEvents($actor, $id, $channel->id);

            try {
                app(\App\Services\AuditLoggerClient::class)->record(
                    organizationId: $actor['organization_id'],
                    actorUserId: $actor['user_id'],
                    eventType: 'social.post.created',
                    entityType: 'post',
                    entityId: $id,
                    action: 'CREATE_POST',
                    payload: [
                        'title' => $data['title'] ?? null,
                        'post_type' => $type,
                        'channel_id' => $channel->id,
                        'status' => $status,
                    ],
                    riskLevel: 'normal',
                    status: 'success',
                    actorName: $actor['display_name'] ?? null,
                    actorEmail: $actor['email'] ?? null,
                    module: 'social'
                );
            } catch (\Throwable) {}

            return ResponseAPI::success($this->readQuery($actor)->where('p.id', $id)->first(), 201)->header('Location', '/api/v1/social/posts/'.$id);
        }, 3);
    }

    public function update(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $data = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:500'], 'body_markdown' => ['sometimes', 'required', 'string', 'max:500000'],
            'publication_status' => ['sometimes', 'in:published'],
            'channel_id' => ['prohibited'], 'post_type' => ['prohibited'], 'author_user_id' => ['prohibited'], 'organization_id' => ['prohibited'], 'visibility' => ['prohibited'],
        ]);
        abort_if($data === [], 422, 'No changes supplied.');

        return DB::transaction(function () use ($actor, $data, $postId) {
            $post = $this->access->post($actor, $postId, owner: true, lock: true);
            $this->publishingPermission($actor, $post->post_type, array_key_exists('title', $data) ? $data['title'] : $post->title);
            $updates = ['edited_at' => now(), 'updated_at' => now(), 'updated_by_user_id' => $actor['user_id']];
            if (array_key_exists('title', $data)) {
                $updates['title'] = $data['title'] === null ? null : strip_tags($data['title']);
            }
            if (isset($data['body_markdown'])) {
                abort_unless(trim(strip_tags($data['body_markdown'])) !== '', 422, 'Post body is empty.');
                $updates['body_markdown'] = strip_tags($data['body_markdown']);
                $updates['body_plaintext'] = trim(strip_tags($data['body_markdown']));
            }
            if (isset($data['body_markdown']) || array_key_exists('title', $data)) {
                $checkTitle = array_key_exists('title', $data) ? $data['title'] : $post->title;
                $checkBody = $data['body_markdown'] ?? $post->body_markdown;
                $moderation = app(\App\Services\ContentModerationService::class)->inspect($checkTitle, $checkBody);
                if ($moderation['is_flagged']) {
                    if ($moderation['should_auto_hide']) {
                        $updates['publication_status'] = 'hidden';
                        $updates['moderation_status'] = 'blocked';
                    } else {
                        $updates['moderation_status'] = 'flagged';
                    }
                    $reportId = (string) Str::uuid();
                    DB::table('post_reports')->insert([
                        'id' => $reportId,
                        'organization_id' => $actor['organization_id'],
                        'target_type' => 'post',
                        'target_id' => $postId,
                        'reporter_user_id' => null,
                        'report_reason_code' => $moderation['reason_code'],
                        'report_detail' => $moderation['detail'],
                        'source' => 'ai',
                        'risk_score' => $moderation['risk_score'],
                        'status' => 'open',
                        'opened_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            if (isset($data['publication_status']) && $post->publication_status === 'draft') {
                $updates['publication_status'] = 'published';
                $updates['published_at'] = now();
                if ($post->post_type === 'announcement') $this->announcementEvents($actor, $postId, $post->channel_id);
            }
            DB::table('posts')->where('id', $postId)->update($updates);
            $this->events->record($actor, 'post', $postId, 'post.updated');

            return ResponseAPI::success($this->readQuery($actor)->where('p.id', $postId)->first());
        }, 3);
    }

    public function destroy(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);

        return DB::transaction(function () use ($actor, $postId) {
            $this->access->post($actor, $postId, owner: true, lock: true);
            DB::table('posts')->where('id', $postId)->update(['deleted_at' => now(), 'updated_at' => now(), 'updated_by_user_id' => $actor['user_id']]);
            $this->events->record($actor, 'post', $postId, 'post.deleted');

            try {
                app(\App\Services\AuditLoggerClient::class)->record(
                    organizationId: $actor['organization_id'],
                    actorUserId: $actor['user_id'],
                    eventType: 'social.post.deleted',
                    entityType: 'post',
                    entityId: $postId,
                    action: 'DELETE_POST',
                    payload: [],
                    riskLevel: 'normal',
                    status: 'success',
                    actorName: $actor['display_name'] ?? null,
                    actorEmail: $actor['email'] ?? null,
                    module: 'social'
                );
            } catch (\Throwable) {}

            return response()->noContent();
        }, 3);
    }

    private function announcementEvents(array $actor, string $postId, string $channelId): void
    {
        $users = DB::table('channel_members')->where('channel_id', $channelId)->where('membership_status', 'active')->where('user_id', '!=', $actor['user_id'])->pluck('user_id');
        foreach ($users->chunk(100) as $batch) $this->events->record($actor, 'post', $postId, 'announcement.published', ['recipient_user_ids' => $batch->values()->all()]);
    }

    private function publishingPermission(array $actor, string $type, ?string $title): void
    {
        if ($type !== 'update' && $type !== 'event') {
            $this->access->permission($actor, 'social.'.$type.'.publish');
            abort_unless(trim(strip_tags($title ?? '')) !== '', 422, 'Title is required.');
        }
    }

    private function readQuery(array $actor): Builder
    {
        // Counts come from source tables, so repeated requests cannot drift counters.
        return $this->access->posts($actor)->select(['p.id', 'p.channel_id', 'p.author_user_id', 'p.post_type', 'p.title', 'p.body_markdown', 'p.body_plaintext', 'p.visibility', 'p.publication_status', 'p.moderation_status', 'p.published_at', 'p.created_at', 'p.updated_at', 'p.edited_at', 'p.comments_locked_at'])
            ->selectSub(DB::table('post_reactions')->selectRaw('COUNT(*)')->whereColumn('post_id', 'p.id'), 'reaction_count')
            ->selectSub(DB::table('post_comments')->selectRaw('COUNT(*)')->whereColumn('post_id', 'p.id')->whereNull('deleted_at')->where('status', 'published')->where('moderation_status', 'clear')->where(function (Builder $q): void {
                $q->whereNull('parent_comment_id')->orWhereExists(fn (Builder $parent) => $parent->selectRaw('1')->from('post_comments as parent')->whereColumn('parent.id', 'post_comments.parent_comment_id')->whereNull('parent.deleted_at')->where('parent.status', 'published')->where('parent.moderation_status', 'clear'));
            }), 'comment_count')
            ->selectSub(DB::table('post_reactions')->select('reaction_type')->whereColumn('post_id', 'p.id')->where('user_id', $actor['user_id']), 'my_reaction')
            ->selectRaw('EXISTS (SELECT 1 FROM post_saves WHERE post_saves.post_id = p.id AND post_saves.user_id = ?) AS is_saved', [$actor['user_id']])
            ->selectRaw('EXISTS (SELECT 1 FROM post_pins WHERE post_pins.post_id = p.id AND (post_pins.expires_at IS NULL OR post_pins.expires_at > CURRENT_TIMESTAMP)) AS is_pinned')
            ->selectSub(
                DB::table('post_pins')
                    ->select('expires_at')
                    ->whereColumn('post_id', 'p.id')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->limit(1),
                'pin_expires_at'
            )
            ->selectSub(
                DB::table('post_attachments')
                    ->select('object_key')
                    ->whereColumn('post_id', 'p.id')
                    ->where('attachment_type', 'image')
                    ->where('scan_status', 'clean')
                    ->orderBy('sort_order')
                    ->limit(1),
                'image_url'
            );
    }
}
