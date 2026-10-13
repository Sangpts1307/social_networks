<?php

namespace App\Http\Controllers;

use App\Auth\RemoteIdentityUser;
use App\Services\LearningGatewayClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KnowledgeDocumentUploadController extends Controller
{
    public function __invoke(Request $request, LearningGatewayClient $learning): RedirectResponse
    {
        $user = $request->user('admin');

        abort_unless($user instanceof RemoteIdentityUser && $user->can('learning.documents.upload'), 403);

        $validated = $request->validate([
            'document_id' => ['required', 'uuid'],
            'document_file' => ['required', 'file', 'mimes:pdf,docx,xlsx', 'max:51200'],
        ]);

        $token = (string) $request->session()->get('admin.identity_token');
        $response = $learning->upload(
            "documents/{$validated['document_id']}/files",
            $token,
            $request->file('document_file'),
        );

        if ($response === null) {
            return to_route('filament.admin.pages.knowledge-documents')
                ->with('knowledge_documents_upload_error', 'Không thể tải tệp lên. Vui lòng thử lại.');
        }

        $version = $response['data']['version_number'] ?? null;

        return to_route('filament.admin.pages.knowledge-documents')
            ->with('knowledge_documents_upload_success', $version ? "Đã tải tệp lên phiên bản v{$version}." : 'Đã tải tệp lên.');
    }
}
