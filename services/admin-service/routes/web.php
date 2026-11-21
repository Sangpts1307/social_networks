<?php

use App\Http\Controllers\KnowledgeDocumentUploadController;
use App\Http\Controllers\SsoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/sso', SsoController::class)->name('admin.sso');
Route::get('/sso', SsoController::class)->name('sso');

Route::post('/admin/knowledge-documents/upload', KnowledgeDocumentUploadController::class)
    ->middleware('auth:admin')
    ->name('admin.knowledge-documents.upload');

Route::get('/admin/knowledge-graph-demo', function () {
    if (! auth('admin')->check()) return redirect('/admin/login');
    abort_unless(auth('admin')->user()?->can('learning.documents.read'), 403);
    return view('knowledge-graph-demo');
})->name('admin.knowledge-graph-demo');

Route::get('/admin/knowledge-graph-demo/data', function () {
    abort_unless(auth('admin')->check(), 401);
    abort_unless(auth('admin')->user()?->can('learning.documents.read'), 403);
    $path = resource_path('data/knowledge_graph_demo.json');
    abort_unless(is_file($path), 404);
    $graph = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    $documents = app(\App\Services\LearningGatewayClient::class)->get('documents', (string) session('admin.identity_token'))['data'] ?? [];
    $managed = [];
    foreach ($documents as $document) {
        if (preg_match('/^(KB-[A-Z]+-\d+) · /u', $document['title'] ?? '', $match)) $managed[$match[1]] = $document['id'];
    }
    foreach ($graph['nodes'] as &$node) {
        if (($node['type'] ?? '') === 'document' && isset($managed[$node['code'] ?? ''])) $node['managed_document_id'] = $managed[$node['code']];
    }
    unset($node);
    return response()->json($graph)->header('Cache-Control', 'no-store, max-age=0');
})->name('admin.knowledge-graph-demo.data');

Route::get('/admin/knowledge-documents/{documentId}/file', function (string $documentId) {
    abort_unless(auth('admin')->user()?->can('learning.documents.read'), 403);
    abort_unless(\Illuminate\Support\Str::isUuid($documentId), 404);
    $disposition = request()->query('download') === '1' ? 'attachment' : 'inline';
    $result = app(\App\Services\LearningGatewayClient::class)->get("documents/{$documentId}/file-url?disposition={$disposition}", (string) session('admin.identity_token'));
    abort_unless(isset($result['data']['url']), 404);
    return redirect()->away($result['data']['url']);
})->middleware('auth:admin')->name('admin.knowledge-documents.file');
