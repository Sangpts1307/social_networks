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

class LearningAdminController extends Controller
{
    public function __construct(
        private readonly LearningAuthorization $authorization,
        private readonly LearningOutbox $outbox,
    ) {}

    public function taxonomy(Request $request)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.taxonomy.manage');

        return response()->json(['data' => [
            'topics' => DB::table('knowledge_topics')->where('organization_id', $actor['organization_id'])->whereNull('deleted_at')->orderBy('sort_order')->orderBy('name')->get(),
            'categories' => DB::table('knowledge_categories')->where('organization_id', $actor['organization_id'])->whereNull('deleted_at')->orderBy('sort_order')->orderBy('name')->get(),
        ]]);
    }

    public function storeTopic(Request $request)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.taxonomy.manage');
        $data = $this->topicInput($request, true);
        $this->ensureTopicCodeAvailable($actor['organization_id'], $data['code']);
        $id = (string) Str::uuid();
        $now = now();
        DB::table('knowledge_topics')->insert(['id' => $id, 'organization_id' => $actor['organization_id'], ...$data, 'created_at' => $now, 'updated_at' => $now]);
        $this->outbox->record('learning.topic', $id, 'learning.topic.created.v1', $actor, ['source_version' => 1]);

        return response()->json(['data' => $this->topic($actor['organization_id'], $id)], 201);
    }

    public function updateTopic(Request $request, string $topicId)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.taxonomy.manage');
        abort_unless($this->topic($actor['organization_id'], $topicId), 404);
        $data = $this->topicInput($request, false);
        if (isset($data['code'])) {
            $this->ensureTopicCodeAvailable($actor['organization_id'], $data['code'], $topicId);
        }
        DB::table('knowledge_topics')->where('id', $topicId)->update([...$data, 'updated_at' => now()]);
        $this->outbox->record('learning.topic', $topicId, 'learning.topic.updated.v1', $actor);

        return response()->json(['data' => $this->topic($actor['organization_id'], $topicId)]);
    }

    public function storeCategory(Request $request)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.taxonomy.manage');
        $data = $this->categoryInput($request, true);
        abort_unless($this->topic($actor['organization_id'], $data['topic_id']), 422, 'Topic is invalid.');
        $this->ensureCategoryCodeAvailable($data['topic_id'], $data['code']);
        $this->ensureCategoryParent($actor['organization_id'], $data['topic_id'], $data['parent_id'] ?? null);
        $id = (string) Str::uuid();
        $now = now();
        DB::table('knowledge_categories')->insert(['id' => $id, 'organization_id' => $actor['organization_id'], ...$data, 'created_at' => $now, 'updated_at' => $now]);
        $this->outbox->record('learning.category', $id, 'learning.category.created.v1', $actor, ['topic_id' => $data['topic_id'], 'source_version' => 1]);

        return response()->json(['data' => $this->category($actor['organization_id'], $id)], 201);
    }

    public function updateCategory(Request $request, string $categoryId)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.taxonomy.manage');
        $category = $this->category($actor['organization_id'], $categoryId);
        abort_unless($category, 404);
        $data = $this->categoryInput($request, false);
        $topicId = $data['topic_id'] ?? $category->topic_id;
        abort_unless($this->topic($actor['organization_id'], $topicId), 422, 'Topic is invalid.');
        if (isset($data['code'])) {
            $this->ensureCategoryCodeAvailable($topicId, $data['code'], $categoryId);
        }
        $this->ensureCategoryParent($actor['organization_id'], $topicId, $data['parent_id'] ?? $category->parent_id, $categoryId);
        DB::table('knowledge_categories')->where('id', $categoryId)->update([...$data, 'updated_at' => now()]);
        $this->outbox->record('learning.category', $categoryId, 'learning.category.updated.v1', $actor, ['topic_id' => $topicId]);

        return response()->json(['data' => $this->category($actor['organization_id'], $categoryId)]);
    }

    public function questions(Request $request)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.questions.moderate');
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', 'in:open,closed,hidden']]);
        $query = DB::table('questions')->leftJoin('knowledge_topics', 'knowledge_topics.id', '=', 'questions.topic_id')
            ->leftJoin('knowledge_categories', 'knowledge_categories.id', '=', 'questions.category_id')
            ->where('questions.organization_id', $actor['organization_id'])->whereNull('questions.deleted_at')
            ->select(['questions.id', 'questions.title', 'questions.body_plaintext', 'questions.question_status', 'questions.answer_count', 'questions.author_user_id', 'questions.created_at', 'questions.closed_at', 'knowledge_topics.name as topic_name', 'knowledge_categories.name as category_name']);
        if (! empty($filters['status'])) {
            $query->where('questions.question_status', $filters['status']);
        }
        if (! empty($filters['q'])) {
            $query->where('questions.title', 'like', '%'.addcslashes($filters['q'], '%_\\').'%');
        }

        return response()->json(['data' => $query->orderByDesc('questions.created_at')->limit(100)->get()]);
    }

    public function moderateQuestion(Request $request, string $questionId)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.questions.moderate');
        $data = $request->validate(['action' => ['required', 'in:hide,restore,close,reopen'], 'reason_code' => ['nullable', 'string', 'max:64'], 'note' => ['nullable', 'string', 'max:2000']]);
        $question = $this->question($actor['organization_id'], $questionId);
        abort_unless($question, 404);
        $newState = match ($data['action']) {
            'hide' => 'hidden', 'restore', 'reopen' => 'open', 'close' => 'closed',
        };
        $updates = ['question_status' => $newState, 'updated_at' => now()];
        if ($data['action'] === 'close') {
            $updates += ['closed_at' => now(), 'closed_by_user_id' => $actor['user_id']];
        }
        if (in_array($data['action'], ['restore', 'reopen'], true)) {
            $updates += ['closed_at' => null, 'closed_by_user_id' => null];
        }
        DB::transaction(function () use ($actor, $question, $questionId, $data, $updates, $newState): void {
            DB::table('questions')->where('id', $questionId)->lockForUpdate()->first();
            DB::table('questions')->where('id', $questionId)->update($updates);
            if ($data['action'] === 'hide') app(\App\Services\RewardEligibility::class)->cancelContent($actor['organization_id'], 'question', $questionId, 'content_hidden');
            DB::table('learning_moderation_actions')->insert([
                'id' => (string) Str::uuid(), 'target_type' => 'question', 'target_id' => $questionId,
                'action_type' => 'learning.question.'.$data['action'], 'actor_type' => 'user', 'actor_user_id' => $actor['user_id'],
                'reason_code' => $data['reason_code'] ?? null, 'note' => $data['note'] ?? null,
                'previous_state_json' => json_encode(['question_status' => $question->question_status], JSON_THROW_ON_ERROR),
                'new_state_json' => json_encode(['question_status' => $newState], JSON_THROW_ON_ERROR), 'created_at' => now(),
            ]);
            $this->outbox->record('learning.question', $questionId, 'learning.question.'.$data['action'].'.v1', $actor, ['previous_status' => $question->question_status, 'status' => $newState]);
        });

        return response()->json(['data' => $this->question($actor['organization_id'], $questionId)]);
    }

    public function documents(Request $request)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.documents.read');

        return response()->json(['data' => app(DocumentAccess::class)->scope($this->documentsQuery($actor['organization_id']), $actor, 'learning.documents.read')->orderByDesc('documents.updated_at')->limit(100)->get()]);
    }

    public function courses(Request $request)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.courses.manage');
        $courses = DB::table('courses')->leftJoin('knowledge_topics', 'knowledge_topics.id', '=', 'courses.topic_id')
            ->where('courses.organization_id', $actor['organization_id'])->whereNull('courses.deleted_at')
            ->select(['courses.id', 'courses.topic_id', 'courses.title', 'courses.description_markdown', 'courses.status', 'courses.published_at', 'courses.created_at', 'knowledge_topics.name as topic_name'])
            ->orderByDesc('courses.updated_at')->limit(100)->get();

        return response()->json(['data' => $courses]);
    }

    public function storeCourse(Request $request)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.courses.manage');
        $data = $request->validate(['topic_id' => ['nullable', 'uuid'], 'title' => ['required', 'string', 'max:500'], 'description_markdown' => ['nullable', 'string', 'max:50000']]);
        if (! empty($data['topic_id'])) {
            abort_unless($this->topic($actor['organization_id'], $data['topic_id']), 422, 'Topic is invalid.');
        }
        $id = (string) Str::uuid();
        DB::table('courses')->insert(['id' => $id, 'organization_id' => $actor['organization_id'], 'topic_id' => $data['topic_id'] ?? null, 'owner_user_id' => $actor['user_id'], 'title' => trim($data['title']), 'description_markdown' => $data['description_markdown'] ?? null, 'status' => 'draft', 'visibility' => 'organization', 'created_at' => now(), 'updated_at' => now()]);
        $this->outbox->record('learning.course', $id, 'learning.course.created.v1', $actor, ['source_version' => 1]);

        return response()->json(['data' => $this->course($actor['organization_id'], $id)], 201);
    }

    public function transitionCourse(Request $request, string $courseId)
    {
        $actor = $this->authorization->requirePermission($request, 'learning.courses.manage');
        $action = $request->validate(['action' => ['required', 'in:publish,close,restore']])['action'];
        $course = $this->course($actor['organization_id'], $courseId);
        abort_unless($course, 404);
        $updates = match ($action) {
            'publish' => ['status' => 'published', 'published_at' => now(), 'updated_at' => now()],
            'close' => ['status' => 'closed', 'updated_at' => now()],
            'restore' => ['status' => 'draft', 'published_at' => null, 'updated_at' => now()],
        };
        DB::table('courses')->where('id', $courseId)->update($updates);
        $this->outbox->record('learning.course', $courseId, 'learning.course.'.$action.'.v1', $actor, ['previous_status' => $course->status]);

        return response()->json(['data' => $this->course($actor['organization_id'], $courseId)]);
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

    /** @return array<string, mixed> */
    private function topicInput(Request $request, bool $required): array
    {
        $requiredRule = $required ? ['required'] : ['sometimes'];
        $data = $request->validate(['code' => [...$requiredRule, 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9_-]*$/'], 'name' => [...$requiredRule, 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string'], 'status' => ['sometimes', 'in:active,inactive'], 'sort_order' => ['sometimes', 'integer', 'min:0']]);

        return $this->trimStrings($data);
    }

    /** @return array<string, mixed> */
    private function categoryInput(Request $request, bool $required): array
    {
        $requiredRule = $required ? ['required'] : ['sometimes'];
        $data = $request->validate(['topic_id' => [...$requiredRule, 'uuid'], 'parent_id' => ['sometimes', 'nullable', 'uuid'], 'code' => [...$requiredRule, 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9_-]*$/'], 'name' => [...$requiredRule, 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string'], 'status' => ['sometimes', 'in:active,inactive'], 'sort_order' => ['sometimes', 'integer', 'min:0']]);

        return $this->trimStrings($data);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function trimStrings(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        return $data;
    }

    private function topic(string $organizationId, string $topicId): ?object
    {
        return DB::table('knowledge_topics')->where('organization_id', $organizationId)->where('id', $topicId)->whereNull('deleted_at')->first();
    }

    private function category(string $organizationId, string $categoryId): ?object
    {
        return DB::table('knowledge_categories')->where('organization_id', $organizationId)->where('id', $categoryId)->whereNull('deleted_at')->first();
    }

    private function question(string $organizationId, string $questionId): ?object
    {
        return DB::table('questions')->where('organization_id', $organizationId)->where('id', $questionId)->whereNull('deleted_at')->first();
    }

    private function document(string $organizationId, string $documentId): ?object
    {
        $request = request();
        $actor = $request->attributes->get('learning_actor');
        $permission = $request->attributes->get('learning_permission', 'learning.documents.read');

        return app(DocumentAccess::class)->scope($this->documentsQuery($organizationId), $actor, $permission,
            $permission === 'learning.documents.read' ? 'view' : 'manage')->where('documents.id', $documentId)->first();
    }

    private function course(string $organizationId, string $courseId): ?object
    {
        return DB::table('courses')->where('organization_id', $organizationId)->where('id', $courseId)->whereNull('deleted_at')->first();
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

    private function ensureCategoryParent(string $organizationId, string $topicId, ?string $parentId, ?string $categoryId = null): void
    {
        if (! $parentId) {
            return;
        } abort_unless($parentId !== $categoryId && $this->categoryBelongsToTopic($organizationId, $topicId, $parentId), 422, 'Category parent is invalid.');
    }

    private function ensureTopicCodeAvailable(string $organizationId, string $code, ?string $exceptId = null): void
    {
        $query = DB::table('knowledge_topics')->where('organization_id', $organizationId)->where('code', $code);
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        } abort_if($query->exists(), 422, 'Topic code is already used.');
    }

    private function ensureCategoryCodeAvailable(string $topicId, string $code, ?string $exceptId = null): void
    {
        $query = DB::table('knowledge_categories')->where('topic_id', $topicId)->where('code', $code);
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        } abort_if($query->exists(), 422, 'Category code is already used.');
    }
}
