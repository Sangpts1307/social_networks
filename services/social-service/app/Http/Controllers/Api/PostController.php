<?php

namespace App\Http\Controllers\Api;

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
        $filters = $request->validate(['channel_id' => ['sometimes', 'uuid'], 'author_user_id' => ['sometimes', 'uuid'], 'saved' => ['sometimes', 'in:0,1'], 'post_type' => ['sometimes', 'in:update,news,announcement']]);
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

        return response()->json(['data' => $this->readQuery($actor)->where('p.id', $postId)->first()]);
    }

    public function store(Request $request)
    {
        $actor = $this->access->actor($request);
        $data = $request->validate([
            'channel_id' => ['required', 'uuid'], 'post_type' => ['sometimes', 'in:update,news,announcement'],
            'title' => ['nullable', 'string', 'max:500'], 'body_markdown' => ['required', 'string', 'max:50000'],
            'publication_status' => ['sometimes', 'in:draft,published'],
            'organization_id' => ['prohibited'], 'author_user_id' => ['prohibited'], 'visibility' => ['prohibited'],
        ]);
        $type = $data['post_type'] ?? 'update';
        $this->publishingPermission($actor, $type, $data['title'] ?? null);
        abort_unless(trim(strip_tags($data['body_markdown'])) !== '', 422, 'Post body is empty.');

        return DB::transaction(function () use ($actor, $data, $type) {
            $channel = $this->access->channel($actor, $data['channel_id'], true, true);
            $id = (string) Str::uuid();
            $status = $data['publication_status'] ?? 'published';
            DB::table('posts')->insert([
                'id' => $id, 'organization_id' => $actor['organization_id'], 'channel_id' => $channel->id,
                'author_user_id' => $actor['user_id'], 'created_by_user_id' => $actor['user_id'], 'post_type' => $type,
                'title' => isset($data['title']) ? strip_tags($data['title']) : null,
                'body_markdown' => strip_tags($data['body_markdown']), 'body_plaintext' => trim(strip_tags($data['body_markdown'])),
                'visibility' => $channel->visibility === 'organization' ? 'organization' : 'channel',
                'publication_status' => $status, 'moderation_status' => 'clear',
                'published_at' => $status === 'published' ? now() : null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->events->record($actor, 'post', $id, 'post.'.$status, ['channel_id' => $channel->id]);

            return response()->json(['data' => $this->readQuery($actor)->where('p.id', $id)->first()], 201)->header('Location', '/api/v1/social/posts/'.$id);
        }, 3);
    }

    public function update(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $data = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:500'], 'body_markdown' => ['sometimes', 'required', 'string', 'max:50000'],
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
            if (isset($data['publication_status']) && $post->publication_status === 'draft') {
                $updates['publication_status'] = 'published';
                $updates['published_at'] = now();
            }
            DB::table('posts')->where('id', $postId)->update($updates);
            $this->events->record($actor, 'post', $postId, 'post.updated');

            return response()->json(['data' => $this->readQuery($actor)->where('p.id', $postId)->first()]);
        }, 3);
    }

    public function destroy(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);

        return DB::transaction(function () use ($actor, $postId) {
            $this->access->post($actor, $postId, owner: true, lock: true);
            DB::table('posts')->where('id', $postId)->update(['deleted_at' => now(), 'updated_at' => now(), 'updated_by_user_id' => $actor['user_id']]);
            $this->events->record($actor, 'post', $postId, 'post.deleted');

            return response()->noContent();
        }, 3);
    }

    private function publishingPermission(array $actor, string $type, ?string $title): void
    {
        if ($type !== 'update') {
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
            ->selectRaw('EXISTS (SELECT 1 FROM post_saves WHERE post_saves.post_id = p.id AND post_saves.user_id = ?) AS is_saved', [$actor['user_id']]);
    }
}
