<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentAccess;
use App\Services\LearningAuthorization;
use App\Services\LearningOutbox;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LearningDocumentAdminController extends Controller
{
    public function __construct(
        private readonly LearningAuthorization $authorization,
        private readonly LearningOutbox $outbox,
    ) {}

    public function documents(Request $request)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.documents.read');

        return response()->json(['data' => app(DocumentAccess::class)->scope($this->documentsQuery($actor['organization_id']), $actor, 'learning.documents.read')->orderByDesc('documents.updated_at')->limit(100)->get()]);
    }

    public function storeDocument(Request $request)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.documents.create');
        $data = $request->validate([
            'topic_id' => ['required', 'uuid'], 'category_id' => ['nullable', 'uuid'], 'title' => ['required', 'string', 'max:500'],
            'org_unit_id' => ['nullable', 'uuid'],
            'summary' => ['nullable', 'string', 'max:5000'], 'version_label' => ['nullable', 'string', 'max:64'], 'change_summary' => ['nullable', 'string', 'max:5000'],
        ]);
        abort_unless($this->topic($actor['organization_id'], $data['topic_id']), 422, 'Topic is invalid.');
        if (! empty($data['category_id'])) {
            abort_unless($this->categoryBelongsToTopic($actor['organization_id'], $data['topic_id'], $data['category_id']), 422, 'Category is invalid.');
        }
        $unit = $data['org_unit_id'] ?? null;
        abort_if($unit && ! in_array($unit, $actor['authorization']['organization_unit_ids'] ?? [], true), 422, 'Phòng ban không thuộc tổ chức hoặc không còn hoạt động.');
        abort_unless(collect($actor['authorization']['grants'])->contains(fn ($g) => in_array('learning.documents.create', $g['permissions'], true)
            && ($g['scope_type'] === 'organization' || $g['scope_type'] === 'self' || ($unit && in_array($unit, $g['org_unit_ids'] ?? [], true)))), 403);
        $documentId = (string) Str::uuid();
        $versionId = (string) Str::uuid();
        DB::transaction(function () use ($actor, $data, $documentId, $versionId): void {
            $now = now();
            DB::table('documents')->insert([
                'id' => $documentId, 'organization_id' => $actor['organization_id'], 'topic_id' => $data['topic_id'], 'category_id' => $data['category_id'] ?? null,
                'org_unit_id' => $data['org_unit_id'] ?? null, 'owner_user_id' => $actor['user_id'], 'title' => trim($data['title']), 'summary' => $data['summary'] ?? null, 'document_status' => 'draft',
                'confidentiality' => 'internal', 'current_version_id' => $versionId, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('document_versions')->insert([
                'id' => $versionId, 'document_id' => $documentId, 'version_number' => 1, 'version_label' => $data['version_label'] ?? 'v1',
                'change_summary' => $data['change_summary'] ?? null, 'created_by_user_id' => $actor['user_id'], 'approval_status' => 'draft', 'created_at' => $now, 'updated_at' => $now,
            ]);
            $this->outbox->record('learning.document', $documentId, 'learning.document.created.v1', $actor, ['document_version_id' => $versionId, 'source_version' => 1]);
        });

        return response()->json(['data' => $this->document($actor['organization_id'], $documentId)], 201);
    }

    public function transitionDocument(Request $request, string $documentId)
    {
        $data = $request->validate(['action' => ['required', 'in:publish,archive,restore'], 'note' => ['nullable', 'string', 'max:2000']]);
        $actor = $this->authorization->requirePermission($request, $data['action'] === 'publish' ? 'learning.documents.publish' : 'learning.documents.archive');
        $document = $this->document($actor['organization_id'], $documentId);
        abort_unless($document, 404);
        DB::transaction(function () use ($actor, $data, $document, $documentId): void {
            $now = now();
            DB::table('documents')->where('id', $documentId)->lockForUpdate()->first();
            if ($data['action'] === 'publish') {
                abort_unless($document->current_version_id, 422, 'A document version is required before publishing.');
                $version = DB::table('document_versions')->where('id', $document->current_version_id)->first();
                abort_unless($version && $version->approval_status === 'approved', 422, 'The current version must be approved before publishing.');
                abort_unless(DB::table('document_files')->where('document_version_id', $document->current_version_id)->where('scan_status', 'clean')->exists(), 422, 'The current version needs a clean uploaded file before publishing.');
                DB::table('documents')->where('id', $documentId)->update(['document_status' => 'published', 'published_version_id' => $document->current_version_id, 'published_at' => $now, 'archived_at' => null, 'updated_at' => $now]);
                DB::table('document_reviews')->insert(['id' => (string) Str::uuid(), 'document_version_id' => $document->current_version_id, 'reviewer_user_id' => $actor['user_id'], 'review_action' => 'publish', 'note' => $data['note'] ?? null, 'reviewed_at' => $now]);
            } elseif ($data['action'] === 'archive') {
                DB::table('documents')->where('id', $documentId)->update(['document_status' => 'archived', 'archived_at' => $now, 'updated_at' => $now]);
            } else {
                DB::table('documents')->where('id', $documentId)->update(['document_status' => 'draft', 'published_at' => null, 'archived_at' => null, 'updated_at' => $now]);
            }
            if ($data['action'] !== 'publish') app(\App\Services\RewardEligibility::class)->cancelContent($actor['organization_id'], 'document', $documentId, 'content_unpublished');
            $this->outbox->record('learning.document', $documentId, 'learning.document.'.$data['action'].'.v1', $actor, ['document_version_id' => $document->current_version_id, 'previous_status' => $document->document_status]);
        });

        return response()->json(['data' => $this->document($actor['organization_id'], $documentId)]);
    }

    public function updateDocument(Request $request, string $documentId)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.documents.update');
        $document = $this->document($actor['organization_id'], $documentId);
        abort_unless($document, 404);
        $data = $request->validate([
            'topic_id' => ['sometimes', 'uuid'], 'category_id' => ['sometimes', 'nullable', 'uuid'],
            'title' => ['sometimes', 'string', 'max:500'], 'summary' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);
        $topicId = $data['topic_id'] ?? $document->topic_id;
        abort_unless($this->topic($actor['organization_id'], $topicId), 422, 'Topic is invalid.');
        if (array_key_exists('category_id', $data) && $data['category_id'] !== null) {
            abort_unless($this->categoryBelongsToTopic($actor['organization_id'], $topicId, $data['category_id']), 422, 'Category is invalid.');
        }
        if (isset($data['title'])) {
            $data['title'] = trim($data['title']);
        }
        DB::table('documents')->where('id', $documentId)->update([...$data, 'updated_at' => now()]);
        $this->outbox->record('learning.document', $documentId, 'learning.document.updated.v1', $actor, ['document_version_id' => $document->current_version_id]);

        return response()->json(['data' => $this->document($actor['organization_id'], $documentId)]);
    }

    public function uploadDocumentFile(Request $request, string $documentId)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.documents.upload');
        $request->validate(['file' => ['required', 'file', 'mimes:pdf,docx,xlsx', 'max:'.config('documents.max_upload_kilobytes')]]);
        $document = $this->document($actor['organization_id'], $documentId);
        abort_unless($document, 404);
        $file = $request->file('file');
        abort_unless($file !== null && $file->isValid(), 422, 'A valid document file is required.');

        $extension = strtolower($file->getClientOriginalExtension());
        $objectKey = null;
        try {
            $result = DB::transaction(function () use ($actor, $documentId, $file, $extension, &$objectKey): array {
                $document = DB::table('documents')->where('id', $documentId)->lockForUpdate()->first();
                abort_unless($document, 404);
                $currentVersion = DB::table('document_versions')->where('id', $document->current_version_id)->lockForUpdate()->first();
                abort_unless($currentVersion, 422, 'A document version is required before uploading.');
                $hasFile = DB::table('document_files')->where('document_version_id', $currentVersion->id)->exists();
                $versionId = $currentVersion->id;
                $versionNumber = (int) $currentVersion->version_number;
                $now = now();

                if ($hasFile) {
                    $versionId = (string) Str::uuid();
                    $versionNumber++;
                    DB::table('document_versions')->insert([
                        'id' => $versionId, 'document_id' => $documentId, 'version_number' => $versionNumber,
                        'version_label' => 'v'.$versionNumber, 'created_by_user_id' => $actor['user_id'],
                        'approval_status' => 'draft', 'supersedes_version_id' => $currentVersion->id,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                    DB::table('documents')->where('id', $documentId)->update(['current_version_id' => $versionId, 'updated_at' => $now]);
                }

                $objectKey = sprintf('organizations/%s/documents/%s/versions/%s/original.%s', $actor['organization_id'], $documentId, $versionId, $extension);
                $stored = Storage::disk(config('documents.disk'))->putFileAs(dirname($objectKey), $file, basename($objectKey));
                abort_unless($stored !== false, 503, 'Document storage is unavailable.');
                $scanStatus = config('documents.trust_local_uploads') ? 'clean' : 'pending';
                DB::table('document_files')->insert([
                    'id' => (string) Str::uuid(), 'document_version_id' => $versionId, 'object_key' => $objectKey,
                    'original_filename' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size_bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()),
                    'scan_status' => $scanStatus, 'extraction_status' => 'pending', 'preview_status' => 'pending',
                    'uploaded_at' => $now, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $this->outbox->record('learning.document', $documentId, 'learning.document.file_uploaded.v1', $actor, [
                    'document_version_id' => $versionId, 'version_number' => $versionNumber, 'scan_status' => $scanStatus,
                ]);

                return ['version_id' => $versionId, 'version_number' => $versionNumber, 'scan_status' => $scanStatus];
            });
        } catch (\Throwable $exception) {
            if ($objectKey !== null) {
                Storage::disk(config('documents.disk'))->delete($objectKey);
            }

            throw $exception;
        }

        return response()->json(['data' => [...$result, 'document' => $this->document($actor['organization_id'], $documentId)]], 201);
    }

    public function reviewDocument(Request $request, string $documentId)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.documents.review');
        $data = $request->validate(['action' => ['required', 'in:approve,reject'], 'note' => ['nullable', 'string', 'max:2000']]);
        $document = $this->document($actor['organization_id'], $documentId);
        abort_unless($document && $document->current_version_id, 404);
        abort_unless(DB::table('document_files')->where('document_version_id', $document->current_version_id)->exists(), 422, 'Upload a document file before review.');
        $now = now();
        DB::transaction(function () use ($actor, $data, $document, $now): void {
            DB::table('document_versions')->where('id', $document->current_version_id)->update([
                'approval_status' => $data['action'] === 'approve' ? 'approved' : 'rejected',
                'approved_by_user_id' => $data['action'] === 'approve' ? $actor['user_id'] : null,
                'approved_at' => $data['action'] === 'approve' ? $now : null,
                'updated_at' => $now,
            ]);
            DB::table('document_reviews')->insert(['id' => (string) Str::uuid(), 'document_version_id' => $document->current_version_id, 'reviewer_user_id' => $actor['user_id'], 'review_action' => $data['action'], 'note' => $data['note'] ?? null, 'reviewed_at' => $now]);
            $this->outbox->record('learning.document', $document->id, 'learning.document.reviewed.v1', $actor, ['document_version_id' => $document->current_version_id, 'action' => $data['action'], 'recipient_user_ids' => [DB::table('documents')->where('id', $document->id)->value('owner_user_id')]]);
        });

        return response()->json(['data' => $this->document($actor['organization_id'], $documentId)]);
    }

    public function accessRules(Request $request, string $documentId)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.documents.access.manage');
        abort_unless($this->document($actor['organization_id'], $documentId), 404);

        return response()->json(['data' => DB::table('document_access_rules')->where('document_id', $documentId)->orderBy('created_at')->get()]);
    }

    public function syncAccessRules(Request $request, string $documentId)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.documents.access.manage');
        abort_unless($this->document($actor['organization_id'], $documentId), 404);
        $data = $request->validate([
            'rules' => ['present', 'array', 'max:100'],
            'rules.*.principal_type' => ['required', 'in:organization,department,role,user'],
            'rules.*.principal_id' => ['nullable', 'uuid'],
            'rules.*.effect' => ['sometimes', 'in:allow,deny'],
            'rules.*.access_level' => ['required', 'in:view,download,manage'],
            'rules.*.starts_at' => ['nullable', 'date'],
            'rules.*.ends_at' => ['nullable', 'date'],
        ]);
        foreach ($data['rules'] as $rule) {
            abort_if(! empty($rule['starts_at']) && ! empty($rule['ends_at']) && Carbon::parse($rule['ends_at'])->lessThanOrEqualTo(Carbon::parse($rule['starts_at'])), 422, 'Ngày kết thúc phải sau ngày bắt đầu.');
            abort_if($rule['principal_type'] === 'organization' && ! empty($rule['principal_id']) && $rule['principal_id'] !== $actor['organization_id'], 422, 'Tổ chức không hợp lệ.');
            abort_if($rule['principal_type'] === 'department' && ! in_array($rule['principal_id'] ?? '', $actor['authorization']['organization_unit_ids'] ?? [], true), 422, 'Phòng ban không hợp lệ.');
            abort_unless($rule['principal_type'] === 'organization' || ! empty($rule['principal_id']), 422, 'principal_id is required for department, role, and user rules.');
        }
        DB::transaction(function () use ($actor, $documentId, $data): void {
            DB::table('document_access_rules')->where('document_id', $documentId)->delete();
            $now = now();
            foreach ($data['rules'] as $rule) {
                DB::table('document_access_rules')->insert([
                    'id' => (string) Str::uuid(), 'document_id' => $documentId, 'principal_type' => $rule['principal_type'],
                    'effect' => $rule['effect'] ?? 'allow', 'principal_id' => $rule['principal_id'] ?? null, 'access_level' => $rule['access_level'],
                    'granted_by_user_id' => $actor['user_id'], 'starts_at' => ! empty($rule['starts_at']) ? Carbon::parse($rule['starts_at'])->utc()->toDateTimeString() : null,
                    'ends_at' => ! empty($rule['ends_at']) ? Carbon::parse($rule['ends_at'])->utc()->toDateTimeString() : null, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $this->outbox->record('learning.document', $documentId, 'learning.document.access_rules_updated.v1', $actor, ['rule_count' => count($data['rules'])]);
        });

        return response()->json(['data' => DB::table('document_access_rules')->where('document_id', $documentId)->orderBy('created_at')->get()]);
    }

    private function topic(string $organizationId, string $topicId): ?object
    {
        return DB::table('knowledge_topics')->where('organization_id', $organizationId)->where('id', $topicId)->whereNull('deleted_at')->first();
    }

    private function document(string $organizationId, string $documentId): ?object
    {
        $request = request();
        $actor = $request->attributes->get('learning_actor');
        $permission = $request->attributes->get('learning_permission', 'learning.documents.read');

        return app(DocumentAccess::class)->scope($this->documentsQuery($organizationId), $actor, $permission,
            $permission === 'learning.documents.read' ? 'view' : 'manage')->where('documents.id', $documentId)->first();
    }

    private function documentsQuery(string $organizationId): Builder
    {
        return DB::table('documents')
            ->join('knowledge_topics', 'knowledge_topics.id', '=', 'documents.topic_id')
            ->leftJoin('knowledge_categories', 'knowledge_categories.id', '=', 'documents.category_id')
            ->leftJoin('document_versions as current_version', 'current_version.id', '=', 'documents.current_version_id')
            ->leftJoin('document_files as current_file', 'current_file.document_version_id', '=', 'current_version.id')
            ->where('documents.organization_id', $organizationId)
            ->whereNull('documents.deleted_at')
            ->select([
                'documents.org_unit_id', 'documents.id', 'documents.topic_id', 'documents.category_id', 'documents.title', 'documents.summary',
                'documents.document_status', 'documents.current_version_id', 'documents.published_version_id',
                'documents.published_at', 'documents.archived_at', 'documents.created_at', 'documents.updated_at',
                'knowledge_topics.name as topic_name', 'knowledge_categories.name as category_name',
                'current_version.version_number as current_version_number', 'current_version.approval_status as current_version_approval_status',
                'current_file.id as current_file_id', 'current_file.original_filename as current_filename',
                'current_file.mime_type as current_file_mime_type', 'current_file.size_bytes as current_file_size_bytes',
                'current_file.scan_status as current_file_scan_status',
            ]);
    }

    private function categoryBelongsToTopic(string $organizationId, string $topicId, string $categoryId): bool
    {
        return DB::table('knowledge_categories')->where('organization_id', $organizationId)->where('topic_id', $topicId)->where('id', $categoryId)->whereNull('deleted_at')->exists();
    }
}
