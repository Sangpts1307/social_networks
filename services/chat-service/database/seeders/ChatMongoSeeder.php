<?php

namespace Database\Seeders;

use App\Models\ChatReadModel;
use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use Illuminate\Database\Seeder;

class ChatMongoSeeder extends Seeder
{
    public function run(): void
    {
        $organizationId = '10000000-0000-4000-8000-000000000001';
        $expertUserId = '30000000-0000-4000-8000-000000000002';
        $memberUserId = '30000000-0000-4000-8000-000000000003';
        $conversationId = '80000000-0000-4000-8000-000000000001';

        Conversation::query()->updateOrCreate(
            ['_id' => $conversationId],
            ['organizationId' => $organizationId, 'type' => 'direct', 'directKey' => "$expertUserId:$memberUserId", 'createdByUserId' => $expertUserId, 'status' => 'active', 'memberCount' => 2],
        );
        foreach ([$expertUserId, $memberUserId] as $userId) {
            ConversationMember::query()->updateOrCreate(
                ['conversationId' => $conversationId, 'userId' => $userId],
                ['organizationId' => $organizationId, 'memberRole' => $userId === $expertUserId ? 'owner' : 'member', 'membershipStatus' => 'active', 'joinedAt' => now(), 'unreadCount' => 0, 'isPinned' => false],
            );
        }
        Message::query()->updateOrCreate(
            ['conversationId' => $conversationId, 'clientMessageId' => '80000000-0000-4000-8000-000000000002'],
            ['_id' => '80000000-0000-4000-8000-000000000003', 'organizationId' => $organizationId, 'senderUserId' => $expertUserId, 'messageType' => 'text', 'bodyMarkdown' => 'Chào mừng đến Chat nội bộ.', 'bodyPlaintext' => 'Chào mừng đến Chat nội bộ.', 'attachments' => [], 'mentions' => [], 'sentAt' => now(), 'metadata' => ['schemaVersion' => 1]],
        );
        ChatReadModel::query()->updateOrCreate(
            ['_id' => $expertUserId],
            ['organizationId' => $organizationId, 'displayName' => 'Trần Chuyên Gia', 'jobTitle' => 'Chuyên gia tri thức', 'sourceVersion' => 'identity-seed-v1', 'syncedAt' => now()],
        );
    }
}
