<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KnowledgeDocumentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $root = env('LEARNING_DEMO_DOCUMENT_ROOT', dirname(base_path(), 3).'/knowledge_learning');
        $catalog = json_decode(file_get_contents($root.'/catalog.json'), true, flags: JSON_THROW_ON_ERROR);
        $codes = ['KB-GOV-001', 'KB-GOV-012', 'KB-HR-001', 'KB-HR-002', 'KB-IT-001', 'KB-IT-002', 'KB-SEC-001', 'KB-SEC-002', 'KB-FIN-001', 'KB-FIN-002', 'KB-PRC-001', 'KB-SAL-001', 'KB-CS-001', 'KB-MKT-001', 'KB-OPS-001', 'KB-PM-001', 'KB-DATA-001', 'KB-AI-001', 'KB-LEG-001', 'KB-HSE-001'];
        $records = collect($catalog['documents'])->keyBy('document_code');
        $organizationId = '10000000-0000-4000-8000-000000000001';
        $userId = '30000000-0000-4000-8000-000000000002';
        foreach ($codes as $index => $code) {
            $item = $records->get($code);
            if (! $item) throw new \RuntimeException("Missing catalog entry: {$code}");
            $source = realpath($root.'/'.$item['relative_path']);
            if (! $source || ! str_starts_with($source, realpath($root).DIRECTORY_SEPARATOR)) throw new \RuntimeException("Missing source file: {$code}");
            $topicCode = 'kb-'.strtolower(explode('-', $code)[1]);
            $topicId = DB::table('knowledge_topics')->where('organization_id', $organizationId)->where('code', $topicCode)->value('id');
            if (! $topicId) {
                $topicId = (string) Str::uuid();
                DB::table('knowledge_topics')->insert(['id' => $topicId, 'organization_id' => $organizationId, 'code' => $topicCode, 'name' => $item['department'], 'description' => 'Tài liệu từ kho tri thức ĐATN', 'status' => 'active', 'sort_order' => $index + 1, 'created_at' => now(), 'updated_at' => now()]);
            }
            $number = $index + 1;
            $documentId = sprintf('73000000-0000-4000-8000-%012d', $number);
            $versionId = sprintf('74000000-0000-4000-8000-%012d', $number);
            $filename = basename($source);
            $key = "organizations/{$organizationId}/documents/{$documentId}/versions/{$versionId}/original.docx";
            if (! Storage::disk(config('documents.disk'))->put($key, file_get_contents($source))) throw new \RuntimeException("Cannot store {$code}");
            if (! DB::table('documents')->where('id', $documentId)->exists()) {
                DB::table('documents')->insert(['id' => $documentId, 'organization_id' => $organizationId, 'topic_id' => $topicId, 'category_id' => null, 'org_unit_id' => null, 'owner_user_id' => $userId, 'title' => $code.' · '.$item['title'], 'summary' => $item['title'].' — '.$item['department'], 'document_status' => 'draft', 'confidentiality' => 'internal', 'current_version_id' => $versionId, 'published_version_id' => null, 'published_at' => null, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('document_versions')->insert(['id' => $versionId, 'document_id' => $documentId, 'version_number' => 1, 'version_label' => $item['version'], 'created_by_user_id' => $userId, 'approval_status' => 'approved', 'approved_by_user_id' => $userId, 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('documents')->where('id', $documentId)->update(['document_status' => 'draft', 'published_version_id' => null, 'published_at' => null]);
            if (! DB::table('document_files')->where('document_version_id', $versionId)->exists()) {
                DB::table('document_files')->insert(['id' => (string) Str::uuid(), 'document_version_id' => $versionId, 'object_key' => $key, 'original_filename' => $filename, 'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'size_bytes' => filesize($source), 'sha256' => hash_file('sha256', $source), 'scan_status' => 'clean', 'extraction_status' => 'pending', 'preview_status' => 'pending', 'uploaded_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        // Remove only the 18 synthetic records created by the earlier demo seeder.
        $oldIds = array_map(fn ($i) => sprintf('71000000-0000-4000-8000-%012d', $i), range(1, 18));
        $oldVersions = DB::table('document_versions')->whereIn('document_id', $oldIds)->pluck('id');
        $oldKeys = DB::table('document_files')->whereIn('document_version_id', $oldVersions)->pluck('object_key')->all();
        DB::table('document_files')->whereIn('document_version_id', $oldVersions)->delete();
        DB::table('documents')->whereIn('id', $oldIds)->update(['current_version_id' => null, 'published_version_id' => null]);
        DB::table('document_versions')->whereIn('id', $oldVersions)->delete();
        DB::table('documents')->whereIn('id', $oldIds)->delete();
        Storage::disk(config('documents.disk'))->delete($oldKeys);
        DB::table('knowledge_topics')->where('organization_id', $organizationId)->where('code', 'like', 'demo-%')->whereNotIn('id', DB::table('documents')->select('topic_id'))->delete();
    }
}
