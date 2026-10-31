<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ResponseAPI;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseReadController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = (string) $request->attributes->get('learning_actor')['organization_id'];
        $courses = DB::table('courses')->leftJoin('knowledge_topics', 'knowledge_topics.id', '=', 'courses.topic_id')
            ->where('courses.organization_id', $organizationId)->where('courses.status', 'published')->whereNotNull('courses.published_at')->whereNull('courses.deleted_at')
            ->select(['courses.id', 'courses.topic_id', 'courses.title', 'courses.description_markdown', 'courses.published_at', 'knowledge_topics.name as topic_name'])
            ->orderByDesc('courses.published_at')->get();

        return ResponseAPI::success($courses);
    }
}
