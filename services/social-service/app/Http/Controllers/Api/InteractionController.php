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

class InteractionController extends Controller
{
    public function __construct(private SocialAccess $access, private SocialPagination $pages, private SocialOutbox $events) {}

    public function comments(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $this->access->post($actor, $postId);
        $q = $this->commentsQuery($postId)->select(['c.id', 'c.post_id', 'c.parent_comment_id', 'c.author_user_id', 'c.body_markdown', 'c.body_plaintext', 'c.created_at', 'c.updated_at', 'c.edited_at']);

        return response()->json($this->pages->page($q, $request, 'c.created_at', 'c.id', 'comments:'.$postId));
    }

    public function storeComment(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $data = $request->validate(['body_markdown' => ['required', 'string', 'max:10000'], 'parent_comment_id' => ['nullable', 'uuid'], 'author_user_id' => ['prohibited'], 'organization_id' => ['prohibited']]);
        abort_unless(trim(strip_tags($data['body_markdown'])) !== '', 422, 'Comment body is empty.');

        return DB::transaction(function () use ($actor, $data, $postId) {
            $post = $this->access->post($actor, $postId, lock: true);
            $this->access->channel($actor, $post->channel_id, true);
            abort_if($post->comments_locked_at !== null, 409, 'Comments are locked.');
            if (! empty($data['parent_comment_id'])) {
                $parent = $this->commentsQuery($postId)->where('c.id', $data['parent_comment_id'])->first();
                abort_unless($parent && $parent->parent_comment_id === null, 422, 'Reply must reference a visible top-level comment on this post.');
            }
            $id = (string) Str::uuid();
            DB::table('post_comments')->insert(['id' => $id, 'organization_id' => $actor['organization_id'], 'post_id' => $postId, 'parent_comment_id' => $data['parent_comment_id'] ?? null, 'author_user_id' => $actor['user_id'], 'body_markdown' => strip_tags($data['body_markdown']), 'body_plaintext' => trim(strip_tags($data['body_markdown'])), 'status' => 'published', 'moderation_status' => 'clear', 'created_at' => now(), 'updated_at' => now()]);
            $this->events->record($actor, 'comment', $id, 'comment.created', ['post_id' => $postId]);

            return response()->json(['data' => $this->commentsQuery($postId)->where('c.id', $id)->first()], 201);
        }, 3);
    }

    public function changeComment(Request $request, string $commentId)
    {
        $actor = $this->access->actor($request);
        $data = $request->isMethod('delete') ? [] : $request->validate(['body_markdown' => ['required', 'string', 'max:10000']]);

        return DB::transaction(function () use ($request, $actor, $data, $commentId) {
            $comment = DB::table('post_comments')->where('organization_id', $actor['organization_id'])->where('id', $commentId)->whereNull('deleted_at')->first();
            abort_unless($comment, 404, 'Comment not found.');
            $post = $this->access->post($actor, $comment->post_id, lock: true);
            $this->access->channel($actor, $post->channel_id, true);
            abort_unless($this->commentsQuery($post->id)->where('c.id', $commentId)->exists(), 404, 'Comment not found.');
            abort_unless($comment->author_user_id === $actor['user_id'], 403, 'Only the author can change this comment.');
            abort_if($post->comments_locked_at !== null, 409, 'Comments are locked.');
            if ($request->isMethod('delete')) {
                DB::table('post_comments')->where('id', $commentId)->update(['deleted_at' => now(), 'updated_at' => now()]);
                $this->events->record($actor, 'comment', $commentId, 'comment.deleted', ['post_id' => $post->id]);

                return response()->noContent();
            }
            abort_unless(trim(strip_tags($data['body_markdown'])) !== '', 422, 'Comment body is empty.');
            DB::table('post_comments')->where('id', $commentId)->update(['body_markdown' => strip_tags($data['body_markdown']), 'body_plaintext' => trim(strip_tags($data['body_markdown'])), 'edited_at' => now(), 'updated_at' => now()]);
            $this->events->record($actor, 'comment', $commentId, 'comment.updated', ['post_id' => $post->id]);

            return response()->json(['data' => $this->commentsQuery($post->id)->where('c.id', $commentId)->first()]);
        }, 3);
    }

    public function reaction(Request $request, string $postId)
    {
        $data = $request->isMethod('delete') ? [] : $request->validate(['reaction_type' => ['required', 'in:like,love,sad,angry']]);

        return $this->setInteraction($request, $postId, 'post_reactions', $data);
    }

    public function save(Request $request, string $postId)
    {
        return $this->setInteraction($request, $postId, 'post_saves', []);
    }

    private function setInteraction(Request $request, string $postId, string $table, array $data)
    {
        $actor = $this->access->actor($request);

        return DB::transaction(function () use ($request, $actor, $postId, $table, $data) {
            $post = $this->access->post($actor, $postId, lock: true);
            // Saves are private reads; reactions require current membership.
            if ($table === 'post_reactions') {
                $this->access->channel($actor, $post->channel_id, true);
            }
            $q = DB::table($table)->where('post_id', $postId)->where('user_id', $actor['user_id']);
            $existing = $q->first();
            if ($request->isMethod('delete')) {
                if ($q->delete()) {
                    $this->events->record($actor, 'post', $postId, $table === 'post_saves' ? 'post.unsaved' : 'post.reaction_removed');
                }

                return response()->noContent();
            }
            if (! $existing) {
                DB::table($table)->insert(['id' => (string) Str::uuid(), 'post_id' => $postId, 'user_id' => $actor['user_id'], ...$data, 'created_at' => now(), ...($table === 'post_reactions' ? ['updated_at' => now()] : [])]);
                $this->events->record($actor, 'post', $postId, $table === 'post_saves' ? 'post.saved' : 'post.reacted', $data);
            } elseif ($table === 'post_reactions' && $existing->reaction_type !== $data['reaction_type']) {
                $q->update([...$data, 'updated_at' => now()]);
                $this->events->record($actor, 'post', $postId, 'post.reacted', $data);
            }

            return response()->json(['data' => ['post_id' => $postId, ...($table === 'post_saves' ? ['is_saved' => true] : $data)]]);
        }, 3);
    }

    private function commentsQuery(string $postId): Builder
    {
        return DB::table('post_comments as c')->where('c.post_id', $postId)->whereNull('c.deleted_at')->where('c.status', 'published')->where('c.moderation_status', 'clear')
            ->where(function (Builder $q): void {
                $q->whereNull('c.parent_comment_id')->orWhereExists(fn (Builder $p) => $p->selectRaw('1')->from('post_comments as parent')->whereColumn('parent.id', 'c.parent_comment_id')->whereNull('parent.deleted_at')->where('parent.status', 'published')->where('parent.moderation_status', 'clear'));
            });
    }
}
