<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LearningAuthorization;
use App\Services\LearningOutbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LearningTaxonomyController extends Controller
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
