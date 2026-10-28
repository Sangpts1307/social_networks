<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionInteractionController
{
    private function question(Request $request, string $id, bool $lock = false): object
    {
        $actor = $request->attributes->get('learning_actor');
        $q = DB::table('questions')->where('organization_id', $actor['organization_id'])->where('id', $id)
            ->where('visibility', 'organization')->whereIn('question_status', ['open', 'closed'])->whereNull('deleted_at');
        $row = ($lock ? $q->lockForUpdate() : $q)->first(); abort_unless($row, 404); return $row;
    }

    public function update(Request $request, string $questionId)
    {
        $data = $request->validate(['title' => 'sometimes|required|string|max:500', 'body_markdown' => 'sometimes|required|string|max:50000']);
        abort_if($data === [], 422, 'No changes supplied.');
        return DB::transaction(function () use ($request, $questionId, $data) {
            $q = $this->question($request, $questionId, true);
            abort_unless($q->author_user_id === $request->attributes->get('learning_actor')['user_id'], 403);
            if (isset($data['body_markdown'])) { abort_unless(trim(strip_tags($data['body_markdown'])) !== '', 422); $data['body_plaintext'] = trim(strip_tags($data['body_markdown'])); }
            DB::table('questions')->where('id', $questionId)->update([...$data, 'updated_at' => now()]);
            return response()->json(['data' => DB::table('questions')->where('id', $questionId)->first()]);
        });
    }

    public function destroy(Request $request, string $questionId)
    {
        return DB::transaction(function () use ($request, $questionId) {
            $q = $this->question($request, $questionId, true);
            abort_unless($q->author_user_id === $request->attributes->get('learning_actor')['user_id'], 403);
            DB::table('questions')->where('id', $questionId)->update(['deleted_at' => now(), 'updated_at' => now()]);
            app(\App\Services\RewardEligibility::class)->cancelContent($q->organization_id, 'question', $questionId, 'content_deleted');
            return response()->noContent();
        });
    }

    public function follow(Request $request, string $questionId) { return $this->interaction($request, $questionId, 'question_follows'); }
    public function bookmark(Request $request, string $questionId) { return $this->interaction($request, $questionId, 'question_bookmarks'); }

    private function interaction(Request $request, string $id, string $table)
    {
        $this->question($request, $id);
        $userId = $request->attributes->get('learning_actor')['user_id'];
        if ($request->isMethod('delete')) DB::table($table)->where('question_id', $id)->where('user_id', $userId)->delete();
        else DB::table($table)->insertOrIgnore(['id' => (string) Str::uuid(), 'question_id' => $id, 'user_id' => $userId, 'created_at' => now()]);
        return response()->noContent();
    }

    public function comments(Request $request, string $questionId)
    {
        $this->question($request, $questionId);
        $data = $request->validate(['answer_id' => 'nullable|uuid', 'cursor' => 'nullable|uuid']);
        if (!empty($data['answer_id'])) $this->answer($questionId, $data['answer_id']);
        $q = DB::table('qa_comments')->where('question_id', $questionId)->whereNull('deleted_at')
            ->when(!empty($data['answer_id']), fn ($q) => $q->where('answer_id', $data['answer_id']), fn ($q) => $q->whereNull('answer_id'));
        if (!empty($data['cursor'])) $q->where('id', '>', $data['cursor']);
        $rows = $q->orderBy('id')->limit(21)->get(); $more = $rows->count() > 20; $rows = $rows->take(20)->values();
        return response()->json(['data' => $rows, 'meta' => ['next_cursor' => $more ? $rows->last()->id : null]]);
    }

    public function comment(Request $request, string $questionId)
    {
        $data = $request->validate(['body' => 'required|string|max:10000', 'answer_id' => 'nullable|uuid']);
        abort_unless(trim(strip_tags($data['body'])) !== '', 422);
        return DB::transaction(function () use ($request, $questionId, $data) {
            $q = $this->question($request, $questionId, true); abort_unless($q->question_status === 'open', 409, 'Question is closed.');
            if (!empty($data['answer_id'])) $this->answer($questionId, $data['answer_id']);
            $id = (string) Str::uuid(); $actor = $request->attributes->get('learning_actor');
            DB::table('qa_comments')->insert(['id' => $id, 'question_id' => $questionId, 'organization_id' => $actor['organization_id'],
                'author_user_id' => $actor['user_id'], 'answer_id' => $data['answer_id'] ?? null, 'body' => strip_tags($data['body']), 'created_at' => now(), 'updated_at' => now()]);
            $recipient = !empty($data['answer_id']) ? DB::table('answers')->where('id', $data['answer_id'])->value('author_user_id') : $q->author_user_id;
            app(\App\Services\LearningOutbox::class)->record('qa_comment', $id, 'learning.qa.comment.created.v1', $actor, [
                'question_id' => $questionId, 'recipient_user_ids' => $recipient === $actor['user_id'] ? [] : [$recipient],
            ]);
            return response()->json(['data' => DB::table('qa_comments')->where('id', $id)->first()], 201);
        });
    }

    public function changeComment(Request $request, string $commentId)
    {
        $actor = $request->attributes->get('learning_actor');
        $comment = DB::table('qa_comments')->where('organization_id', $actor['organization_id'])->where('id', $commentId)->whereNull('deleted_at')->first();
        abort_unless($comment, 404); $this->question($request, $comment->question_id);
        if ($comment->answer_id) $this->answer($comment->question_id, $comment->answer_id);
        abort_unless($comment->author_user_id === $actor['user_id'], 403);
        $data = $request->isMethod('delete') ? ['deleted_at' => now()] : $request->validate(['body' => 'required|string|max:10000']);
        if (isset($data['body'])) { $data['body'] = trim(strip_tags($data['body'])); abort_if($data['body'] === '', 422); }
        DB::table('qa_comments')->where('id', $commentId)->update([...$data, 'updated_at' => now()]);
        return response()->noContent();
    }

    private function answer(string $question, string $answer): void
    {
        abort_unless(DB::table('answers')->where('id', $answer)->where('question_id', $question)->where('answer_status', 'published')->whereNull('deleted_at')->exists(), 404);
    }
}
