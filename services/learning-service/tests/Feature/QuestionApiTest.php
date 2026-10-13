<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionApiTest extends TestCase
{
    use RefreshDatabase;

    private const ORGANIZATION_A = '10000000-0000-4000-8000-000000000001';

    private const ORGANIZATION_B = '10000000-0000-4000-8000-000000000099';

    private const AUTHOR = '30000000-0000-4000-8000-000000000001';

    private const MEMBER = '30000000-0000-4000-8000-000000000002';

    private const OTHER_MEMBER = '30000000-0000-4000-8000-000000000003';

    private const GATEWAY_KEY = 'learning-test-gateway-key-1234567890';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.gateway_key', self::GATEWAY_KEY);
    }

    public function test_question_answer_vote_and_best_answer_are_organization_scoped(): void
    {
        [$topicId, $categoryId] = $this->seedTaxonomy(self::ORGANIZATION_A);
        [, $foreignCategoryId] = $this->seedTaxonomy(self::ORGANIZATION_B);

        $this->postJson('/api/v1/questions', [
            'topic_id' => $topicId, 'category_id' => $foreignCategoryId, 'title' => 'Không hợp lệ', 'body_markdown' => 'Sai tổ chức',
        ], $this->headers())->assertUnprocessable();

        $question = $this->postJson('/api/v1/questions', [
            'topic_id' => $topicId, 'category_id' => $categoryId, 'title' => 'Cách dựng projection?', 'body_markdown' => '**Cần** provenance.',
        ], $this->headers())->assertCreated()->assertJsonPath('data.author_user_id', self::AUTHOR)->json('data');
        $questionId = $question['id'];

        $this->getJson('/api/v1/questions', $this->headers(self::MEMBER, self::ORGANIZATION_B))->assertOk()->assertJsonCount(0, 'data');
        $answerA = $this->postJson("/api/v1/questions/{$questionId}/answers", ['body_markdown' => 'Dùng outbox versioned.'], $this->headers(self::MEMBER))->assertCreated()->json('data');
        $answerB = $this->postJson("/api/v1/questions/{$questionId}/answers", ['body_markdown' => 'Ghi provenance trên relation.'], $this->headers(self::OTHER_MEMBER))->assertCreated()->json('data');

        $this->putJson("/api/v1/answers/{$answerB['id']}/votes", ['value' => 1], $this->headers())->assertOk()->assertJsonPath('data.score', 1);
        $this->putJson("/api/v1/answers/{$answerA['id']}/votes", ['value' => 1], $this->headers(self::OTHER_MEMBER))->assertOk();
        $this->putJson("/api/v1/answers/{$answerA['id']}/votes", ['value' => 1], $this->headers(self::AUTHOR))->assertOk()->assertJsonPath('data.score', 2);
        $this->putJson("/api/v1/answers/{$answerA['id']}/votes", ['value' => 1], $this->headers(self::AUTHOR))->assertOk()->assertJsonPath('data.score', 2);
        $this->assertSame(1, DB::table('qa_votes')->where('target_type', 'answer')->where('target_id', $answerA['id'])->where('user_id', self::AUTHOR)->count());
        $this->putJson("/api/v1/answers/{$answerA['id']}/votes", ['value' => 0], $this->headers(self::AUTHOR))->assertOk()->assertJsonPath('data.score', 1);

        $detail = $this->getJson("/api/v1/questions/{$questionId}", $this->headers())->assertOk()->json('data');
        $expectedBest = min($answerA['id'], $answerB['id']);
        $this->assertSame($expectedBest, $detail['best_answer_id']);
        $this->assertSame(2, $detail['answer_count']);
        $this->assertSame($expectedBest, $detail['answers'][0]['id']);
        $this->assertSame(1, $detail['answers'][0]['score']);

        $this->patchJson("/api/v1/answers/{$answerA['id']}", ['body_markdown' => 'Không được sửa hộ.'], $this->headers(self::AUTHOR))->assertForbidden();
        $this->patchJson("/api/v1/answers/{$answerA['id']}", ['body_markdown' => 'Projection dùng outbox versioned.'], $this->headers(self::MEMBER))->assertOk()->assertJsonPath('data.body_plaintext', 'Projection dùng outbox versioned.');
        $this->deleteJson("/api/v1/answers/{$answerA['id']}", [], $this->headers(self::MEMBER))->assertNoContent();
        $this->getJson("/api/v1/questions/{$questionId}", $this->headers())->assertOk()->assertJsonPath('data.answer_count', 1)->assertJsonPath('data.best_answer_id', $answerB['id']);
    }

    public function test_question_list_uses_a_stable_opaque_cursor(): void
    {
        [$topicId, $categoryId] = $this->seedTaxonomy(self::ORGANIZATION_A);
        for ($position = 0; $position < 21; $position++) {
            DB::table('questions')->insert([
                'id' => '72000000-0000-4000-8000-'.sprintf('%012d', $position),
                'organization_id' => self::ORGANIZATION_A,
                'topic_id' => $topicId,
                'category_id' => $categoryId,
                'author_user_id' => self::AUTHOR,
                'title' => "Question {$position}",
                'body_plaintext' => 'Cursor test',
                'question_status' => 'open',
                'visibility' => 'organization',
                'answer_count' => 0,
                'created_at' => now()->subMinutes($position),
                'updated_at' => now(),
            ]);
        }

        $first = $this->getJson('/api/v1/questions?topic_id='.$topicId, $this->headers())->assertOk()->json();
        $this->assertCount(20, $first['data']);
        $this->assertIsString($first['meta']['next_cursor']);
        $second = $this->getJson('/api/v1/questions?cursor='.urlencode($first['meta']['next_cursor']), $this->headers())->assertOk()->json();
        $this->assertCount(1, $second['data']);
        $this->assertNotContains($second['data'][0]['id'], array_column($first['data'], 'id'));
        $this->assertNull($second['meta']['next_cursor']);
    }

    /** @return array{string, string} */
    private function seedTaxonomy(string $organizationId): array
    {
        $suffix = substr(hash('sha256', $organizationId), 0, 12);
        $topicId = '70000000-0000-4000-8000-'.$suffix;
        $categoryId = '71000000-0000-4000-8000-'.$suffix;
        DB::table('knowledge_topics')->insert(['id' => $topicId, 'organization_id' => $organizationId, 'code' => "topic-{$suffix}", 'name' => 'Kỹ thuật', 'status' => 'active', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('knowledge_categories')->insert(['id' => $categoryId, 'organization_id' => $organizationId, 'topic_id' => $topicId, 'code' => "category-{$suffix}", 'name' => 'Nền tảng', 'status' => 'active', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);

        return [$topicId, $categoryId];
    }

    /** @return array<string, string> */
    private function headers(string $userId = self::AUTHOR, string $organizationId = self::ORGANIZATION_A): array
    {
        return ['X-Learning-Gateway-Key' => self::GATEWAY_KEY, 'X-Authenticated-User-Id' => $userId, 'X-Authenticated-Organization-Id' => $organizationId];
    }
}
