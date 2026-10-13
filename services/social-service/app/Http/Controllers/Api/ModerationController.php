<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SocialAccess;
use App\Services\SocialOutbox;
use App\Services\SocialPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModerationController extends Controller
{
    public function __construct(private SocialAccess $access, private SocialPagination $pages, private SocialOutbox $events) {}

    public function report(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $data = $request->validate(['reason_code' => ['required', 'in:spam,secret_exposure,harassment,misinformation,other'], 'detail' => ['nullable', 'string', 'max:5000']]);

        return DB::transaction(function () use ($actor, $postId, $data) {
            $this->access->post($actor, $postId, lock: true);
            $existing = DB::table('post_reports')->where('target_type', 'post')->where('target_id', $postId)->where('reporter_user_id', $actor['user_id'])->whereIn('status', ['open', 'under_review'])->first();
            if ($existing) {
                return response()->json(['data' => ['id' => $existing->id, 'status' => $existing->status]]);
            }
            $id = (string) Str::uuid();
            DB::table('post_reports')->insert(['id' => $id, 'organization_id' => $actor['organization_id'], 'target_type' => 'post', 'target_id' => $postId, 'reporter_user_id' => $actor['user_id'], 'report_reason_code' => $data['reason_code'], 'report_detail' => isset($data['detail']) ? strip_tags($data['detail']) : null, 'source' => 'user', 'status' => 'open', 'opened_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $this->events->record($actor, 'post', $postId, 'post.reported', ['report_id' => $id]);

            return response()->json(['data' => ['id' => $id, 'status' => 'open']], 201);
        }, 3);
    }

    public function reports(Request $request)
    {
        $actor = $this->access->actor($request);
        $this->access->permission($actor, 'social.moderation.manage');
        $data = $request->validate(['status' => ['sometimes', 'in:open,under_review,resolved,dismissed']]);
        $q = DB::table('post_reports as r')->where('r.organization_id', $actor['organization_id'])->where('r.status', $data['status'] ?? 'open')
            ->whereIn('r.target_id', $this->access->posts($actor)->select('p.id'))->select('r.*');

        return response()->json($this->pages->page($q, $request, 'r.created_at', 'r.id', 'reports:'.($data['status'] ?? 'open')));
    }

    public function moderate(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $this->access->permission($actor, 'social.moderation.manage');
        $data = $request->validate(['action' => ['required', 'in:hide,remove,restore,lock_comments,unlock_comments'], 'reason_code' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/D'], 'note' => ['nullable', 'string', 'max:5000']]);

        return DB::transaction(function () use ($actor, $postId, $data) {
            $post = $this->access->post($actor, $postId, moderator: true, lock: true);
            $before = ['publication_status' => $post->publication_status, 'moderation_status' => $post->moderation_status, 'comments_locked_at' => $post->comments_locked_at];
            $after = $before;
            if (in_array($data['action'], ['hide', 'remove'], true)) {
                abort_unless(in_array($post->publication_status, ['published', 'hidden', 'removed'], true), 409, 'Only previously published posts can be moderated.');
                $after['publication_status'] = $data['action'] === 'hide' ? 'hidden' : 'removed';
                $after['moderation_status'] = 'blocked';
            } elseif ($data['action'] === 'restore') {
                abort_unless(in_array($post->publication_status, ['hidden', 'removed'], true) && $post->published_at !== null, 409, 'Post cannot be restored.');
                $after['publication_status'] = 'published';
                $after['moderation_status'] = 'clear';
            } else {
                abort_unless($post->publication_status === 'published', 409, 'Post is not published.');
                $after['comments_locked_at'] = $data['action'] === 'lock_comments' ? now()->toDateTimeString() : null;
            }
            DB::table('posts')->where('id', $postId)->update([...$after, 'updated_at' => now(), 'updated_by_user_id' => $actor['user_id']]);
            $id = (string) Str::uuid();
            DB::table('moderation_actions')->insert(['id' => $id, 'target_type' => 'post', 'target_id' => $postId, 'action_type' => $data['action'], 'reason_code' => $data['reason_code'], 'note' => isset($data['note']) ? strip_tags($data['note']) : null, 'actor_type' => 'user', 'actor_user_id' => $actor['user_id'], 'previous_state_json' => json_encode($before, JSON_THROW_ON_ERROR), 'new_state_json' => json_encode($after, JSON_THROW_ON_ERROR), 'created_at' => now()]);
            if (in_array($data['action'], ['hide', 'remove'], true)) {
                DB::table('post_reports')->where('organization_id', $actor['organization_id'])->where('target_type', 'post')->where('target_id', $postId)->whereIn('status', ['open', 'under_review'])->update(['status' => 'resolved', 'resolved_at' => now(), 'updated_at' => now()]);
            }
            $this->events->record($actor, 'post', $postId, 'post.moderated', ['action' => $data['action'], 'audit_id' => $id]);

            return response()->json(['data' => ['id' => $postId, ...$after]]);
        }, 3);
    }
}
