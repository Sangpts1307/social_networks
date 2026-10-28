<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionController extends Controller
{
    private const PAGE_SIZE = 20;

    public function index(Request $request)
    {
        $validated = $request->validate([
            'topic_id' => ['nullable', 'uuid'],
            'category_id' => ['nullable', 'uuid'],
            'q' => ['nullable', 'string', 'max:120'],
            'cursor' => ['nullable', 'string', 'max:512'],
        ]);
        $query = $this->questions($this->organizationId($request));
        if (! empty($validated['topic_id'])) {
            $query->where('questions.topic_id', $validated['topic_id']);
        }
        if (! empty($validated['category_id'])) {
            $query->where('questions.category_id', $validated['category_id']);
        }
        if (! empty($validated['q'])) {
            $escaped = addcslashes($validated['q'], '%_\\');
            $query->where('questions.title', 'like', "%{$escaped}%");
        }
        if (! empty($validated['cursor'])) {
            $cursor = $this->decodeCursor($validated['cursor']);
            $query->where(function (Builder $builder) use ($cursor): void {
                $builder->where('questions.created_at', '<', $cursor['created_at'])
                    ->orWhere(function (Builder $builder) use ($cursor): void {
                        $builder->where('questions.created_at', '=', $cursor['created_at'])
                            ->where('questions.id', '<', $cursor['id']);
                    });
            });
        }

        $items = $query->orderByDesc('questions.created_at')->orderByDesc('questions.id')->limit(self::PAGE_SIZE + 1)->get();
        $next = null;
        if ($items->count() > self::PAGE_SIZE) {
            $items->pop();
            $last = $items->last();
            $next = $this->encodeCursor($last->created_at, $last->id);
        }

        $items->each(fn (object $item) => $this->normalizeScore($item));

        return response()->json(['data' => $items->values(), 'meta' => ['next_cursor' => $next]]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'topic_id' => ['required', 'uuid'],
            'category_id' => ['required', 'uuid'],
            'title' => ['required', 'string', 'min:1', 'max:500'],
            'body_markdown' => ['required', 'string', 'min:1', 'max:50000'],
        ]);
        abort_unless(trim(strip_tags($validated['title'])) !== '' && $this->plainText($validated['body_markdown']) !== '', 422, 'Question title and body cannot be blank.');
        $actor = $this->actor($request);
        abort_unless($this->validCategory($actor['organization_id'], $validated['topic_id'], $validated['category_id']), 422, 'Topic or category is invalid.');

        $id = (string) Str::uuid();
        $now = now();
        DB::table('questions')->insert([
            'id' => $id,
            'organization_id' => $actor['organization_id'],
            'topic_id' => $validated['topic_id'],
            'category_id' => $validated['category_id'],
            'author_user_id' => $actor['user_id'],
            'title' => trim($validated['title']),
            'body_markdown' => $validated['body_markdown'],
            'body_plaintext' => $this->plainText($validated['body_markdown']),
            'question_status' => 'open',
            'visibility' => 'organization',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json(['data' => $this->question($actor['organization_id'], $id)], 201);
    }

    public function show(Request $request, string $questionId)
    {
        $organizationId = $this->organizationId($request);
        $question = $this->question($organizationId, $questionId);
        abort_unless($question, 404);
        $answers = $this->answers($questionId);
        $question->answers = $answers;
        $question->best_answer_id = $answers->first()?->id;

        return response()->json(['data' => $question]);
    }

    public function storeAnswer(Request $request, string $questionId)
    {
        $validated = $request->validate(['body_markdown' => ['required', 'string', 'min:1', 'max:50000']]);
        abort_unless($this->plainText($validated['body_markdown']) !== '', 422, 'Answer cannot be blank.');
        $actor = $this->actor($request);
        $question = $this->question($actor['organization_id'], $questionId);
        abort_unless($question, 404);
        abort_unless($question->question_status === 'open', 422, 'Question is not open for answers.');

        return DB::transaction(function () use ($actor, $questionId, $validated) {
            $question = DB::table('questions')->where('id', $questionId)->whereNull('deleted_at')->where('question_status', 'open')->lockForUpdate()->first();
            abort_unless($question, 409, 'Question is unavailable.');
        $id = (string) Str::uuid();
        $now = now();
        DB::table('answers')->insert([
            'id' => $id,
            'organization_id' => $actor['organization_id'],
            'question_id' => $questionId,
            'author_user_id' => $actor['user_id'],
            'body_markdown' => $validated['body_markdown'],
            'body_plaintext' => $this->plainText($validated['body_markdown']),
            'answer_status' => 'published',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('questions')->where('id', $questionId)->increment('answer_count');
        app(\App\Services\LearningOutbox::class)->record('answer', $id, 'learning.answer.created.v1', $actor, [
            'question_id' => $questionId, 'recipient_user_ids' => $question->author_user_id === $actor['user_id'] ? [] : [$question->author_user_id],
        ]);

        return response()->json(['data' => $this->answer($actor['organization_id'], $id)], 201);
        }, 3);
    }

    public function updateAnswer(Request $request, string $answerId)
    {
        $validated = $request->validate(['body_markdown' => ['required', 'string', 'min:1', 'max:50000']]);
        abort_unless($this->plainText($validated['body_markdown']) !== '', 422, 'Answer cannot be blank.');
        $actor = $this->actor($request);
        $answer = $this->answer($actor['organization_id'], $answerId);
        abort_unless($answer, 404);
        abort_unless($answer->author_user_id === $actor['user_id'], 403);
        DB::table('answers')->where('id', $answerId)->update([
            'body_markdown' => $validated['body_markdown'],
            'body_plaintext' => $this->plainText($validated['body_markdown']),
            'edited_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['data' => $this->answer($actor['organization_id'], $answerId)]);
    }

    public function destroyAnswer(Request $request, string $answerId)
    {
        $actor = $this->actor($request);
        $answer = $this->answer($actor['organization_id'], $answerId);
        abort_unless($answer, 404);
        abort_unless($answer->author_user_id === $actor['user_id'], 403);
        DB::transaction(function () use ($answerId, $answer): void {
            $parent = DB::table('questions')->where('id', $answer->question_id)->whereNull('deleted_at')->whereIn('question_status', ['open', 'closed'])->lockForUpdate()->first();
            abort_unless($parent, 404);
            $answer = DB::table('answers')->where('id', $answerId)->whereNull('deleted_at')->lockForUpdate()->first();
            if (!$answer) return;
            DB::table('answers')->where('id', $answerId)->update(['deleted_at' => now(), 'updated_at' => now()]);
            app(\App\Services\RewardEligibility::class)->cancelContent($answer->organization_id, 'answer', $answerId, 'content_deleted');
            DB::table('questions')->where('id', DB::table('answers')->where('id', $answerId)->value('question_id'))
                ->where('answer_count', '>', 0)->decrement('answer_count');
        }, 3);

        return response()->noContent();
    }

    public function voteQuestion(Request $request, string $questionId)
    {
        $actor = $this->actor($request);
        abort_unless($this->question($actor['organization_id'], $questionId), 404);
        $this->vote($request, 'question', $questionId, $actor['user_id']);

        return response()->json(['data' => $this->question($actor['organization_id'], $questionId)]);
    }

    public function voteAnswer(Request $request, string $answerId)
    {
        $actor = $this->actor($request);
        abort_unless($this->answer($actor['organization_id'], $answerId), 404);
        $this->vote($request, 'answer', $answerId, $actor['user_id']);

        return response()->json(['data' => $this->answer($actor['organization_id'], $answerId)]);
    }

    private function vote(Request $request, string $targetType, string $targetId, string $userId): void
    {
        $value = (int) $request->validate(['value' => ['required', 'integer', 'in:-1,0,1']])['value'];
        DB::transaction(function () use ($targetType, $targetId, $userId, $value): void {
            $target = DB::table($targetType === 'question' ? 'questions' : 'answers')->where('id', $targetId)->whereNull('deleted_at')->lockForUpdate()->first();
            abort_unless($target, 404);
            if ((int) $value !== 1) app(\App\Services\RewardEligibility::class)->cancelVote($target->organization_id, $targetType, $targetId, $userId);
            $priorVote = DB::table('qa_votes')->where('target_type', $targetType)->where('target_id', $targetId)->where('user_id', $userId)->value('vote_value');
            if ((int) $value === 1 && (int) $priorVote !== 1) {
                $topicId = $targetType === 'question' ? $target->topic_id : DB::table('questions')->where('id', $target->question_id)->value('topic_id');
                app(\App\Services\RewardEngine::class)->award([
                    'organization_id' => $target->organization_id, 'actor_user_id' => $userId, 'recipient_user_id' => $target->author_user_id,
                    'source_type' => $targetType, 'source_id' => $targetId, 'topic_id' => $topicId,
                    'event_id' => (string) Str::uuid(), 'event_type' => 'learning.'.$targetType.'.upvoted', 'producer' => 'learning-service',
                ]);
            }
            $existing = DB::table('qa_votes')->where('target_type', $targetType)->where('target_id', $targetId)->where('user_id', $userId);
            if ($value === 0) {
                $existing->delete();

                return;
            }
            if ($existing->exists()) {
                $existing->update(['vote_value' => $value, 'updated_at' => now()]);

                return;
            }
            DB::table('qa_votes')->insert(['id' => (string) Str::uuid(), 'target_type' => $targetType, 'target_id' => $targetId, 'user_id' => $userId, 'vote_value' => $value, 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    private function questions(string $organizationId): Builder
    {
        return DB::table('questions')->where('questions.organization_id', $organizationId)->where('questions.visibility', 'organization')->whereIn('questions.question_status', ['open', 'closed'])->whereNull('questions.deleted_at')
            ->select(['questions.id', 'questions.topic_id', 'questions.category_id', 'questions.author_user_id', 'questions.title', 'questions.body_plaintext', 'questions.question_status', 'questions.answer_count', 'questions.created_at', 'questions.updated_at'])
            ->selectRaw("COALESCE((SELECT SUM(vote_value) FROM qa_votes WHERE target_type = 'question' AND target_id = questions.id), 0) AS score");
    }

    private function question(string $organizationId, string $questionId): ?object
    {
        return $this->normalizeScore($this->questions($organizationId)->where('questions.id', $questionId)->first());
    }

    private function answers(string $questionId)
    {
        return DB::table('answers')->where('answers.question_id', $questionId)->where('answers.answer_status', 'published')->whereNull('answers.deleted_at')
            ->select(['answers.id', 'answers.question_id', 'answers.author_user_id', 'answers.body_markdown', 'answers.body_plaintext', 'answers.created_at', 'answers.edited_at'])
            ->selectRaw("COALESCE((SELECT SUM(vote_value) FROM qa_votes WHERE target_type = 'answer' AND target_id = answers.id), 0) AS score")
            ->orderByDesc('score')->orderBy('answers.created_at')->orderBy('answers.id')->get()
            ->each(fn (object $answer) => $this->normalizeScore($answer));
    }

    private function answer(string $organizationId, string $answerId): ?object
    {
        $answer = DB::table('answers')->join('questions', 'questions.id', '=', 'answers.question_id')
            ->where('questions.organization_id', $organizationId)->where('questions.visibility', 'organization')->whereIn('questions.question_status', ['open', 'closed'])->where('answers.answer_status', 'published')->whereNull('questions.deleted_at')->whereNull('answers.deleted_at')->where('answers.id', $answerId)
            ->select(['answers.id', 'answers.question_id', 'answers.author_user_id', 'answers.body_markdown', 'answers.body_plaintext', 'answers.created_at', 'answers.edited_at'])
            ->selectRaw("COALESCE((SELECT SUM(vote_value) FROM qa_votes WHERE target_type = 'answer' AND target_id = answers.id), 0) AS score")->first();

        return $this->normalizeScore($answer);
    }

    private function validCategory(string $organizationId, string $topicId, string $categoryId): bool
    {
        return DB::table('knowledge_categories')->join('knowledge_topics', 'knowledge_topics.id', '=', 'knowledge_categories.topic_id')
            ->where('knowledge_categories.id', $categoryId)->where('knowledge_categories.topic_id', $topicId)
            ->where('knowledge_categories.organization_id', $organizationId)->where('knowledge_topics.organization_id', $organizationId)
            ->where('knowledge_categories.status', 'active')->where('knowledge_topics.status', 'active')
            ->whereNull('knowledge_categories.deleted_at')->whereNull('knowledge_topics.deleted_at')->exists();
    }

    /** @return array{user_id:string,organization_id:string} */
    private function actor(Request $request): array
    {
        return $request->attributes->get('learning_actor');
    }

    private function organizationId(Request $request): string
    {
        return $this->actor($request)['organization_id'];
    }

    private function plainText(string $markdown): string
    {
        return trim(strip_tags($markdown));
    }

    private function normalizeScore(?object $item): ?object
    {
        if ($item !== null && isset($item->score)) {
            $item->score = (int) $item->score;
        }

        return $item;
    }

    /** @return array{created_at:string,id:string} */
    private function decodeCursor(string $cursor): array
    {
        $value = json_decode((string) base64_decode($cursor, true), true);
        abort_unless(is_array($value) && is_string($value['created_at'] ?? null) && is_string($value['id'] ?? null) && Str::isUuid($value['id']), 422, 'Cursor is invalid.');

        return $value;
    }

    private function encodeCursor(string $createdAt, string $id): string
    {
        return base64_encode(json_encode(['created_at' => $createdAt, 'id' => $id], JSON_THROW_ON_ERROR));
    }
}
