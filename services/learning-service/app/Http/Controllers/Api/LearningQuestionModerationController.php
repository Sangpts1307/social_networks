<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LearningAuthorization;
use App\Services\LearningOutbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LearningQuestionModerationController extends Controller
{
    public function __construct(
        private readonly LearningAuthorization $authorization,
        private readonly LearningOutbox $outbox,
    ) {}

    public function questions(Request $request)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.questions.moderate');
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', 'in:open,closed,hidden']]);
        $query = DB::table('questions')->leftJoin('knowledge_topics', 'knowledge_topics.id', '=', 'questions.topic_id')
            ->leftJoin('knowledge_categories', 'knowledge_categories.id', '=', 'questions.category_id')
            ->where('questions.organization_id', $actor['organization_id'])->whereNull('questions.deleted_at')
            ->select(['questions.id', 'questions.title', 'questions.body_plaintext', 'questions.question_status', 'questions.answer_count', 'questions.author_user_id', 'questions.created_at', 'questions.closed_at', 'knowledge_topics.name as topic_name', 'knowledge_categories.name as category_name']);
        if (! empty($filters['status'])) {
            $query->where('questions.question_status', $filters['status']);
        }
        if (! empty($filters['q'])) {
            $query->where('questions.title', 'like', '%'.addcslashes($filters['q'], '%_\\').'%');
        }

        return response()->json(['data' => $query->orderByDesc('questions.created_at')->limit(100)->get()]);
    }

    public function moderateQuestion(Request $request, string $questionId)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.questions.moderate');
        $data = $request->validate(['action' => ['required', 'in:hide,restore,close,reopen'], 'reason_code' => ['nullable', 'string', 'max:64'], 'note' => ['nullable', 'string', 'max:2000']]);
        $question = $this->question($actor['organization_id'], $questionId);
        abort_unless($question, 404);
        $newState = match ($data['action']) {
            'hide' => 'hidden', 'restore', 'reopen' => 'open', 'close' => 'closed',
        };
        $updates = ['question_status' => $newState, 'updated_at' => now()];
        if ($data['action'] === 'close') {
            $updates += ['closed_at' => now(), 'closed_by_user_id' => $actor['user_id']];
        }
        if (in_array($data['action'], ['restore', 'reopen'], true)) {
            $updates += ['closed_at' => null, 'closed_by_user_id' => null];
        }
        DB::transaction(function () use ($actor, $question, $questionId, $data, $updates, $newState): void {
            DB::table('questions')->where('id', $questionId)->lockForUpdate()->first();
            DB::table('questions')->where('id', $questionId)->update($updates);
            if ($data['action'] === 'hide') app(\App\Services\RewardEligibility::class)->cancelContent($actor['organization_id'], 'question', $questionId, 'content_hidden');
            DB::table('learning_moderation_actions')->insert([
                'id' => (string) Str::uuid(), 'target_type' => 'question', 'target_id' => $questionId,
                'action_type' => 'learning.question.'.$data['action'], 'actor_type' => 'user', 'actor_user_id' => $actor['user_id'],
                'reason_code' => $data['reason_code'] ?? null, 'note' => $data['note'] ?? null,
                'previous_state_json' => json_encode(['question_status' => $question->question_status], JSON_THROW_ON_ERROR),
                'new_state_json' => json_encode(['question_status' => $newState], JSON_THROW_ON_ERROR), 'created_at' => now(),
            ]);
            $this->outbox->record('learning.question', $questionId, 'learning.question.'.$data['action'].'.v1', $actor, ['previous_status' => $question->question_status, 'status' => $newState]);
        });

        return response()->json(['data' => $this->question($actor['organization_id'], $questionId)]);
    }

    private function question(string $organizationId, string $questionId): ?object
    {
        return DB::table('questions')->where('organization_id', $organizationId)->where('id', $questionId)->whereNull('deleted_at')->first();
    }
}
