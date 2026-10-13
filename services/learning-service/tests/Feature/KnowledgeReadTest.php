<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KnowledgeReadTest extends TestCase
{
    use RefreshDatabase;

    private const ORGANIZATION_A = '10000000-0000-4000-8000-000000000001';

    private const ORGANIZATION_B = '10000000-0000-4000-8000-000000000099';

    private const USER_A = '30000000-0000-4000-8000-000000000001';

    private const GATEWAY_KEY = 'learning-test-gateway-key-1234567890';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.gateway_key', self::GATEWAY_KEY);
    }

    public function test_gateway_context_is_required(): void
    {
        $this->getJson('/api/v1/knowledge/topics')->assertUnauthorized();
    }

    public function test_topics_and_published_documents_are_scoped_to_organization(): void
    {
        [$topicA, $publishedA] = $this->seedKnowledge(self::ORGANIZATION_A, 'Kỹ thuật A', 'Tài liệu đã publish');
        [, $publishedB] = $this->seedKnowledge(self::ORGANIZATION_B, 'Kỹ thuật B', 'Tài liệu tổ chức khác');
        $this->seedKnowledge(self::ORGANIZATION_A, 'Kỹ thuật A', 'Tài liệu nháp', 'draft');

        $this->getJson('/api/v1/knowledge/topics', $this->headers())
            ->assertOk()->assertJsonPath('data.0.id', $topicA)->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/knowledge-items', $this->headers())
            ->assertOk()->assertJsonPath('data.0.id', $publishedA)->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/knowledge-items/{$publishedB}", $this->headers())->assertNotFound();
    }

    /** @return array{string, string} */
    private function seedKnowledge(string $organizationId, string $topicName, string $documentTitle, string $status = 'published'): array
    {
        $topicSuffix = substr(hash('sha256', $organizationId.$topicName), 0, 12);
        $documentSuffix = substr(hash('sha256', $organizationId.$documentTitle), 0, 12);
        $topicId = '70000000-0000-4000-8000-'.$topicSuffix;
        $categoryId = '71000000-0000-4000-8000-'.$documentSuffix;
        $documentId = '72000000-0000-4000-8000-'.$documentSuffix;
        DB::table('knowledge_topics')->updateOrInsert(['id' => $topicId], ['organization_id' => $organizationId, 'code' => strtolower(str_replace(' ', '-', $topicName)), 'name' => $topicName, 'status' => 'active', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('knowledge_categories')->insert(['id' => $categoryId, 'organization_id' => $organizationId, 'topic_id' => $topicId, 'code' => 'base-'.$documentSuffix, 'name' => 'Nền tảng', 'status' => 'active', 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('documents')->insert(['id' => $documentId, 'organization_id' => $organizationId, 'topic_id' => $topicId, 'category_id' => $categoryId, 'owner_user_id' => self::USER_A, 'title' => $documentTitle, 'summary' => 'Nguồn tri thức kiểm thử.', 'document_status' => $status, 'confidentiality' => 'internal', 'published_at' => $status === 'published' ? now() : null, 'created_at' => now(), 'updated_at' => now()]);

        return [$topicId, $documentId];
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return ['X-Learning-Gateway-Key' => self::GATEWAY_KEY, 'X-Authenticated-User-Id' => self::USER_A, 'X-Authenticated-Organization-Id' => self::ORGANIZATION_A, 'X-Authenticated-Permissions' => 'learning.documents.read'];
    }
}
