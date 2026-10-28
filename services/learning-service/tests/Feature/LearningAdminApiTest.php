<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearningAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private const ORGANIZATION = '10000000-0000-4000-8000-000000000001';

    private const USER = '30000000-0000-4000-8000-000000000001';

    private const KEY = 'learning-test-gateway-key-1234567890';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.gateway_key', self::KEY);
        Storage::fake('testing-documents');
        config()->set('documents.disk', 'testing-documents');
        config()->set('documents.trust_local_uploads', true);
    }

    public function test_taxonomy_document_lifecycle_and_question_moderation_are_permissioned_and_emitted(): void
    {
        $this->getJson('/api/v1/admin/taxonomy', $this->headers())->assertForbidden();

        $topic = $this->postJson('/api/v1/admin/topics', ['code' => 'engineering', 'name' => 'Kỹ thuật', 'description' => 'Nền tảng kỹ thuật'], $this->headers('learning.taxonomy.manage'))->assertCreated()->json('data');
        $category = $this->postJson('/api/v1/admin/categories', ['topic_id' => $topic['id'], 'code' => 'backend', 'name' => 'Backend'], $this->headers('learning.taxonomy.manage'))->assertCreated()->json('data');
        $this->getJson('/api/v1/admin/taxonomy', $this->headers('learning.taxonomy.manage'))->assertOk()->assertJsonCount(1, 'data.topics')->assertJsonCount(1, 'data.categories');

        $document = $this->postJson('/api/v1/admin/documents', ['topic_id' => $topic['id'], 'category_id' => $category['id'], 'title' => 'Quy ước API', 'summary' => 'Tài liệu backend'], $this->headers('learning.documents.create'))->assertCreated()->json('data');
        $this->getJson('/api/v1/knowledge-items', $this->headers('learning.documents.read'))->assertOk()->assertJsonCount(0, 'data');
        $this->post("/api/v1/admin/documents/{$document['id']}/files", ['file' => UploadedFile::fake()->create('quy-uoc-api.pdf', 32, 'application/pdf')], $this->headers('learning.documents.upload'))->assertCreated()->assertJsonPath('data.version_number', 1);
        $this->postJson("/api/v1/admin/documents/{$document['id']}/review", ['action' => 'approve'], $this->headers('learning.documents.review'))->assertOk()->assertJsonPath('data.current_version_approval_status', 'approved');

        $course = $this->postJson('/api/v1/admin/courses', ['topic_id' => $topic['id'], 'title' => 'Nhập môn API'], $this->headers('learning.courses.manage'))->assertCreated()->json('data');
        $this->postJson("/api/v1/admin/courses/{$course['id']}/lifecycle", ['action' => 'publish'], $this->headers('learning.courses.manage'))->assertOk()->assertJsonPath('data.status', 'published');
        $this->getJson('/api/v1/courses', $this->headers())->assertOk()->assertJsonPath('data.0.id', $course['id']);
        $this->postJson("/api/v1/admin/documents/{$document['id']}/lifecycle", ['action' => 'publish'], $this->headers('learning.documents.publish'))->assertOk()->assertJsonPath('data.document_status', 'published');
        $this->getJson('/api/v1/knowledge-items', $this->headers('learning.documents.read'))->assertOk()->assertJsonPath('data.0.id', $document['id']);
        $this->postJson("/api/v1/admin/documents/{$document['id']}/lifecycle", ['action' => 'archive'], $this->headers('learning.documents.archive'))->assertOk()->assertJsonPath('data.document_status', 'archived');
        $this->getJson('/api/v1/knowledge-items', $this->headers('learning.documents.read'))->assertOk()->assertJsonCount(0, 'data');

        $questionId = '60000000-0000-4000-8000-000000000001';
        DB::table('questions')->insert(['id' => $questionId, 'organization_id' => self::ORGANIZATION, 'topic_id' => $topic['id'], 'category_id' => $category['id'], 'author_user_id' => self::USER, 'title' => 'Làm sao kiểm duyệt?', 'body_plaintext' => 'Nội dung câu hỏi', 'question_status' => 'open', 'visibility' => 'organization', 'created_at' => now(), 'updated_at' => now()]);
        $this->postJson("/api/v1/admin/questions/{$questionId}/moderation", ['action' => 'hide', 'reason_code' => 'spam'], $this->headers('learning.questions.moderate'))->assertOk()->assertJsonPath('data.question_status', 'hidden');
        $this->getJson('/api/v1/questions', $this->headers())->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/admin/questions?status=hidden', $this->headers('learning.questions.moderate'))->assertOk()->assertJsonPath('data.0.id', $questionId);
        $this->assertSame(10, DB::table('outbox_events')->count());
        $this->assertDatabaseHas('learning_moderation_actions', ['target_id' => $questionId, 'action_type' => 'learning.question.hide']);
    }

    /** @return array<string, string> */
    private function headers(string $permissions = ''): array
    {
        return ['X-Learning-Gateway-Key' => self::KEY, 'X-Authenticated-User-Id' => self::USER, 'X-Authenticated-Organization-Id' => self::ORGANIZATION, 'X-Authorization-Context' => json_encode(['version' => 1, 'org_unit_ids' => [], 'grants' => [['role_id' => self::USER, 'scope_type' => 'organization', 'permissions' => explode(' ', $permissions)]]]), 'X-Authenticated-Permissions' => $permissions];
    }
}
