<?php

namespace Tests\Feature;

use App\Auth\RemoteIdentityUser;
use App\Services\LearningGatewayClient;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class KnowledgeDocumentUploadControllerTest extends TestCase
{
    public function test_an_authorized_admin_uploads_directly_to_the_learning_gateway(): void
    {
        $gateway = Mockery::mock(LearningGatewayClient::class);
        $this->app->instance(LearningGatewayClient::class, $gateway);

        $documentId = '639e1de1-3eb3-4bb6-af25-fc89020fbab5';
        $gateway->shouldReceive('upload')
            ->once()
            ->withArgs(fn (string $path, string $token, UploadedFile $file): bool => $path === "documents/{$documentId}/files"
                && $token === 'test-token'
                && $file->getClientOriginalName() === 'quy-uoc-api.pdf')
            ->andReturn(['data' => ['version_number' => 2]]);

        $user = new RemoteIdentityUser([
            'id' => 'remote-admin-id',
            'email' => 'admin@example.test',
            'display_name' => 'Admin',
            'must_change_password' => false,
            'permissions' => ['learning.documents.upload'],
        ]);

        $response = $this->withSession(['admin.identity_token' => 'test-token'])
            ->actingAs($user, 'admin')
            ->post(route('admin.knowledge-documents.upload'), [
                'document_id' => $documentId,
                'document_file' => UploadedFile::fake()->create('quy-uoc-api.pdf', 32, 'application/pdf'),
            ]);

        $response->assertRedirect(route('filament.admin.pages.knowledge-documents'));
        $response->assertSessionHas('knowledge_documents_upload_success', 'Đã tải tệp lên phiên bản v2.');
    }
}
