<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function test_document_allow_list_deny_expiry_and_view_only_download(): void
    {
        [, $id] = $this->seedKnowledge(self::ORGANIZATION_A, 'Private', 'HR document');
        $version = (string) Str::uuid();
        DB::table('document_versions')->insert(['id' => $version, 'document_id' => $id, 'version_number' => 1, 'created_by_user_id' => self::USER_A]);
        DB::table('documents')->where('id', $id)->update(['published_version_id' => $version]);
        DB::table('document_files')->insert(['id' => (string) Str::uuid(), 'document_version_id' => $version, 'object_key' => 'private.pdf', 'original_filename' => 'private.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 10, 'scan_status' => 'clean']);
        Storage::shouldReceive('disk')->andReturn(new class
        {
            public function temporaryUrl(...$arguments)
            {
                return 'https://storage.example.test/signed';
            }
        });
        $rule = ['id' => (string) Str::uuid(), 'document_id' => $id, 'principal_type' => 'user', 'principal_id' => self::USER_A, 'access_level' => 'view', 'effect' => 'allow'];
        DB::table('document_access_rules')->insert($rule);
        $this->getJson("/api/v1/knowledge-items/{$id}", $this->headers())->assertOk();
        $this->getJson("/api/v1/knowledge-items/{$id}/download", $this->headers())->assertNotFound();
        DB::table('document_access_rules')->where('id', $rule['id'])->update(['access_level' => 'download']);
        $this->getJson("/api/v1/knowledge-items/{$id}/download", $this->headers())->assertOk()->assertJsonPath('data.url', 'https://storage.example.test/signed');
        DB::table('document_access_rules')->where('id', $rule['id'])->update(['ends_at' => now()->subMinute()]);
        $this->getJson('/api/v1/knowledge-items', $this->headers())->assertOk()->assertJsonCount(0, 'data');
        DB::table('document_access_rules')->where('id', $rule['id'])->update(['ends_at' => null]);
        DB::table('document_access_rules')->insert([...$rule, 'id' => (string) Str::uuid(), 'effect' => 'deny']);
        $this->getJson("/api/v1/knowledge-items/{$id}", $this->headers())->assertNotFound();
        $this->getJson('/api/v1/admin/documents', $this->headers())->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_flat_permissions_without_scope_context_are_rejected(): void
    {
        $headers = $this->headers();
        unset($headers['X-Authorization-Context']);
        $this->getJson('/api/v1/knowledge-items', $headers)->assertUnauthorized();
    }

    public function test_role_deny_applies_even_when_a_different_role_grants_read(): void
    {
        [, $id] = $this->seedKnowledge(self::ORGANIZATION_A, 'Role policy', 'Role denied document');
        $denyRole = '80000000-0000-4000-8000-000000000001';
        DB::table('document_access_rules')->insert(['id' => (string) Str::uuid(), 'document_id' => $id, 'principal_type' => 'role', 'principal_id' => $denyRole, 'access_level' => 'view', 'effect' => 'deny']);
        $headers = $this->headers();
        $context = json_decode($headers['X-Authorization-Context'], true);
        $context['grants'][] = ['role_id' => $denyRole, 'scope_type' => 'organization', 'permissions' => ['learning.documents.update']];
        $headers['X-Authorization-Context'] = json_encode($context);
        $this->getJson("/api/v1/knowledge-items/{$id}", $headers)->assertNotFound();
    }

    public function test_department_membership_and_action_scope_are_both_required(): void
    {
        [, $id] = $this->seedKnowledge(self::ORGANIZATION_A, 'Scoped', 'Department document');
        $unit = '90000000-0000-4000-8000-000000000001';
        $other = '90000000-0000-4000-8000-000000000002';
        DB::table('documents')->where('id', $id)->update(['org_unit_id' => $unit]);
        DB::table('document_access_rules')->insert(['id' => (string) Str::uuid(), 'document_id' => $id, 'principal_type' => 'department', 'principal_id' => $unit, 'access_level' => 'download', 'effect' => 'allow']);
        $context = ['version' => 1, 'org_unit_ids' => [$unit], 'grants' => [['role_id' => self::USER_A, 'scope_type' => 'org_unit', 'org_unit_ids' => [$unit], 'permissions' => ['learning.documents.read']]]];
        $headers = array_replace($this->headers(), ['X-Authorization-Context' => json_encode($context)]);
        $this->getJson("/api/v1/knowledge-items/{$id}", $headers)->assertOk();
        $context['grants'][0]['org_unit_ids'] = [$other];
        $this->getJson("/api/v1/knowledge-items/{$id}", array_replace($headers, ['X-Authorization-Context' => json_encode($context)]))->assertNotFound();
        $context['grants'][0]['org_unit_ids'] = [$unit];
        $context['org_unit_ids'] = [$other];
        $this->getJson("/api/v1/knowledge-items/{$id}", array_replace($headers, ['X-Authorization-Context' => json_encode($context)]))->assertNotFound();
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
        return ['X-Learning-Gateway-Key' => self::GATEWAY_KEY, 'X-Authenticated-User-Id' => self::USER_A, 'X-Authenticated-Organization-Id' => self::ORGANIZATION_A, 'X-Authorization-Context' => json_encode(['version' => 1, 'org_unit_ids' => [], 'grants' => [['role_id' => self::USER_A, 'scope_type' => 'organization', 'permissions' => ['learning.documents.read']]]]), 'X-Authenticated-Permissions' => 'learning.documents.read'];
    }
}
