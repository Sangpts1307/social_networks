<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LearningAuthorization;
use App\Services\LearningOutbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LearningCourseAdminController extends Controller
{
    public function __construct(
        private readonly LearningAuthorization $authorization,
        private readonly LearningOutbox $outbox,
    ) {}

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

    private function topic(string $organizationId, string $topicId): ?object
    {
        return DB::table('knowledge_topics')->where('organization_id', $organizationId)->where('id', $topicId)->whereNull('deleted_at')->first();
    }

    private function course(string $organizationId, string $courseId): ?object
    {
        return DB::table('courses')->where('organization_id', $organizationId)->where('id', $courseId)->whereNull('deleted_at')->first();
    }
}
