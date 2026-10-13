<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LearningAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KnowledgeReadController extends Controller
{
    public function __construct(private readonly LearningAuthorization $authorization) {}

    public function topics(Request $request)
    {
        $organizationId = $this->authorization->requirePermission($request, 'learning.documents.read')['organization_id'];
        $topics = DB::table('knowledge_topics')
            ->where('organization_id', $organizationId)->where('status', 'active')->whereNull('deleted_at')
            ->orderBy('sort_order')->orderBy('name')->get(['id', 'code', 'name', 'description']);
        $categories = DB::table('knowledge_categories')
            ->where('organization_id', $organizationId)->where('status', 'active')->whereNull('deleted_at')
            ->orderBy('sort_order')->orderBy('name')->get(['id', 'topic_id', 'parent_id', 'code', 'name', 'description']);

        return response()->json(['data' => $topics->map(function (object $topic) use ($categories) {
            $topic->categories = $categories->where('topic_id', $topic->id)
                ->map(fn (object $category) => (array) $category)->values();

            return $topic;
        })->values()]);
    }

    public function documents(Request $request)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.documents.read');
        $validated = $request->validate(['topic_id' => ['nullable', 'uuid'], 'q' => ['nullable', 'string', 'max:120']]);
        $query = $this->publishedDocuments($actor['organization_id']);
        if (! empty($validated['topic_id'])) {
            $query->where('documents.topic_id', $validated['topic_id']);
        }
        if (! empty($validated['q'])) {
            $escaped = addcslashes($validated['q'], '%_\\');
            $query->where('documents.title', 'like', "%{$escaped}%");
        }

        return response()->json(['data' => $query->orderByDesc('documents.published_at')->get()]);
    }

    public function document(Request $request, string $documentId)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.documents.read');
        $document = $this->publishedDocuments($actor['organization_id'])->where('documents.id', $documentId)->first();
        abort_unless($document, 404);

        return response()->json(['data' => $document]);
    }

    public function download(Request $request, string $documentId)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.documents.read');
        $document = $this->publishedDocuments($actor['organization_id'])->where('documents.id', $documentId)->first();
        abort_unless($document && $document->published_version_id, 404);
        $file = DB::table('document_files')
            ->where('document_version_id', $document->published_version_id)
            ->where('scan_status', 'clean')
            ->orderBy('uploaded_at')
            ->first();
        abort_unless($file, 404);

        try {
            $url = Storage::disk(config('documents.disk'))->temporaryUrl(
                $file->object_key,
                now()->addMinutes(config('documents.signed_url_minutes')),
                ['ResponseContentDisposition' => 'attachment; filename="'.addcslashes($file->original_filename, '\\"').'"'],
            );
        } catch (\Throwable) {
            abort(503, 'Document download is temporarily unavailable.');
        }

        DB::table('document_access_events')->insert([
            'id' => (string) Str::uuid(), 'document_id' => $documentId, 'document_version_id' => $document->published_version_id,
            'user_id' => $actor['user_id'], 'event_type' => 'download', 'occurred_at' => now(),
        ]);

        return response()->json(['data' => ['url' => $url, 'expires_at' => now()->addMinutes(config('documents.signed_url_minutes'))->toIso8601String()]]);
    }

    private function publishedDocuments(string $organizationId)
    {
        return DB::table('documents')
            ->join('knowledge_topics', 'knowledge_topics.id', '=', 'documents.topic_id')
            ->leftJoin('knowledge_categories', 'knowledge_categories.id', '=', 'documents.category_id')
            ->where('documents.organization_id', $organizationId)->where('documents.document_status', 'published')
            ->where('documents.confidentiality', 'internal')->whereNotNull('documents.published_at')
            ->where('documents.published_at', '<=', now())->whereNull('documents.archived_at')->whereNull('documents.deleted_at')
            ->select(['documents.id', 'documents.topic_id', 'documents.category_id', 'documents.title',
                'documents.summary', 'documents.published_at', 'documents.current_version_id', 'documents.published_version_id',
                'knowledge_topics.name as topic_name', 'knowledge_categories.name as category_name']);
    }
}
