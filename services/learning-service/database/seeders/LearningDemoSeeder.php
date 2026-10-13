<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Course;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeTopic;
use App\Models\Question;
use App\Models\RewardRule;
use Illuminate\Database\Seeder;

class LearningDemoSeeder extends Seeder
{
    public function run(): void
    {
        $organizationId = '10000000-0000-4000-8000-000000000001';
        $expertUserId = '30000000-0000-4000-8000-000000000002';
        $memberUserId = '30000000-0000-4000-8000-000000000003';

        $topic = KnowledgeTopic::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'code' => 'engineering'],
            ['id' => '70000000-0000-4000-8000-000000000001', 'name' => 'Kỹ thuật', 'description' => 'Tri thức kỹ thuật nội bộ', 'status' => 'active'],
        );
        $category = KnowledgeCategory::query()->updateOrCreate(
            ['topic_id' => $topic->id, 'code' => 'backend'],
            ['id' => '70000000-0000-4000-8000-000000000002', 'organization_id' => $organizationId, 'name' => 'Backend', 'status' => 'active'],
        );
        $question = Question::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'title' => 'Knowledge Graph dùng để làm gì?'],
            ['id' => '70000000-0000-4000-8000-000000000003', 'topic_id' => $topic->id, 'category_id' => $category->id, 'author_user_id' => $memberUserId, 'body_markdown' => 'Cần tài liệu khởi đầu.', 'body_plaintext' => 'Cần tài liệu khởi đầu.', 'question_status' => 'open'],
        );
        Answer::query()->updateOrCreate(
            ['question_id' => $question->id, 'author_user_id' => $expertUserId],
            ['id' => '70000000-0000-4000-8000-000000000004', 'organization_id' => $organizationId, 'body_markdown' => 'Knowledge Graph biểu diễn thực thể và quan hệ để khám phá tri thức.', 'body_plaintext' => 'Knowledge Graph biểu diễn thực thể và quan hệ để khám phá tri thức.', 'answer_status' => 'published'],
        );
        $document = Document::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'title' => 'Khởi đầu với Knowledge Graph'],
            ['id' => '70000000-0000-4000-8000-000000000005', 'topic_id' => $topic->id, 'category_id' => $category->id, 'owner_user_id' => $expertUserId, 'summary' => 'Tài liệu demo của Learning Service.', 'document_status' => 'published', 'confidentiality' => 'internal', 'published_at' => now()],
        );
        $version = DocumentVersion::query()->updateOrCreate(
            ['document_id' => $document->id, 'version_number' => 1],
            ['id' => '70000000-0000-4000-8000-000000000006', 'version_label' => 'v1', 'created_by_user_id' => $expertUserId, 'approval_status' => 'published', 'approved_by_user_id' => $expertUserId, 'approved_at' => now()],
        );
        $document->update(['current_version_id' => $version->id]);
        Course::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'title' => 'Nhập môn tri thức nội bộ'],
            ['id' => '70000000-0000-4000-8000-000000000007', 'topic_id' => $topic->id, 'owner_user_id' => $expertUserId, 'status' => 'draft', 'visibility' => 'organization'],
        );
        RewardRule::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'code' => 'qa.answer.accepted'],
            ['id' => '70000000-0000-4000-8000-000000000008', 'trigger_event_type' => 'learning.answer.accepted.v1', 'points_delta' => 10, 'coins_delta' => 0, 'status' => 'draft'],
        );
    }
}
