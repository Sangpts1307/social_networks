<?php

namespace Database\Seeders;

use App\Models\ChatReadModel;
use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChatMongoSeeder extends Seeder
{
    public function run(): void
    {
        $organizationId = '10000000-0000-4000-8000-000000000001';

        $adminUserId = '30000000-0000-4000-8000-000000000001';
        $hrUserId = '30000000-0000-4000-8000-000000000002';
        $leaderUserId = '30000000-0000-4000-8000-000000000003';
        $qaUserId = '30000000-0000-4000-8000-000000000004';
        $memberUserId = '30000000-0000-4000-8000-000000000005';

        $users = [
            $adminUserId => ['name' => 'Nguyễn Quản Trị', 'title' => 'Quản trị viên hệ thống'],
            $hrUserId => ['name' => 'Lê Phương Thảo', 'title' => 'Quản lý Nhân sự'],
            $leaderUserId => ['name' => 'Võ Thành Luân', 'title' => 'Trưởng nhóm AI & Backend'],
            $qaUserId => ['name' => 'Phạm Phát', 'title' => 'Solutions Architect & QA'],
            $memberUserId => ['name' => 'Thành viên', 'title' => 'Kỹ sư phần mềm'],
        ];

        foreach ($users as $uid => $meta) {
            ChatReadModel::query()->updateOrCreate(
                ['_id' => $uid],
                [
                    'organizationId' => $organizationId,
                    'displayName' => $meta['name'],
                    'jobTitle' => $meta['title'],
                    'sourceVersion' => 'identity-seed-v2',
                    'syncedAt' => now(),
                ]
            );
        }

        // 1. Direct conversations
        $directPairs = [
            [
                'id' => '80000000-0000-4000-8000-000000000001',
                'u1' => $leaderUserId,
                'u2' => $memberUserId,
                'messages' => [
                    ['sender' => $leaderUserId, 'text' => 'Chào bạn! Bạn có tài liệu mô tả kiến trúc Knowledge Graph mới cập nhật không?'],
                    ['sender' => $memberUserId, 'text' => 'Chào anh Luân, tài liệu đã được lưu trong Base Library mục Backend/AI nhé.'],
                    ['sender' => $leaderUserId, 'text' => 'Đồ thị tri thức đã đồng bộ thêm 12 nodes chuyên môn mới rồi nhé!'],
                    [
                        'sender' => $leaderUserId,
                        'text' => 'Gửi bạn bản tài liệu đặc tả sơ đồ ontology phân tầng tri thức và file đính kèm:',
                        'attachments' => [
                            ['id' => (string) Str::uuid(), 'filename' => 'Kien_truc_Knowledge_Graph_v2.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 3565158],
                        ],
                    ],
                    ['sender' => $memberUserId, 'text' => 'Cảm ơn anh! Em đã tải file về xem và tích hợp vào search engine ngay.'],
                ],
            ],
            [
                'id' => '80000000-0000-4000-8000-000000000002',
                'u1' => $qaUserId,
                'u2' => $memberUserId,
                'messages' => [
                    ['sender' => $qaUserId, 'text' => 'Đã hoàn tất cấu hình gateway proxy port 8000 và test tải.'],
                    ['sender' => $memberUserId, 'text' => 'Hiệu năng thế nào anh? Có bị nghẽn ở middleware RBAC không anh?'],
                    [
                        'sender' => $qaUserId,
                        'text' => 'Latency p95 chỉ khoảng 18ms, throughput đạt hơn 3500 req/s. Mình gửi bảng benchmark:',
                        'attachments' => [
                            ['id' => (string) Str::uuid(), 'filename' => 'Gateway_Performance_Benchmark.xlsx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'size_bytes' => 1258291],
                        ],
                    ],
                    ['sender' => $memberUserId, 'text' => 'Tuyệt vời anh! Vậy mình có thể triển khai lên staging hôm nay.'],
                ],
            ],
            [
                'id' => '80000000-0000-4000-8000-000000000003',
                'u1' => $hrUserId,
                'u2' => $memberUserId,
                'messages' => [
                    ['sender' => $hrUserId, 'text' => 'Chào bạn, nhớ gửi danh sách nhân sự tham gia hội thảo tuần này nhé.'],
                    ['sender' => $memberUserId, 'text' => 'Chào chị Thảo, team Kỹ thuật gồm 6 bạn đăng ký tham gia hội thảo tuần này ạ.'],
                    [
                        'sender' => $hrUserId,
                        'text' => 'Chị gửi bạn lịch trình chi tiết và sơ đồ hội thảo nhé:',
                        'attachments' => [
                            ['id' => (string) Str::uuid(), 'filename' => 'Ke_hoach_Hoi_thao_AI_2026.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 2202009],
                        ],
                    ],
                    ['sender' => $memberUserId, 'text' => 'Dạ em nhận được rồi ạ, em báo lại các bạn ngay.'],
                ],
            ],
            [
                'id' => '80000000-0000-4000-8000-000000000004',
                'u1' => $adminUserId,
                'u2' => $memberUserId,
                'messages' => [
                    ['sender' => $adminUserId, 'text' => 'Hệ thống xác thực và RBAC matrix đã đồng bộ hoàn tất trên toàn bộ các services.'],
                    ['sender' => $memberUserId, 'text' => 'Dạ vâng anh, phân hệ Message và Newsfeed đã nhận diện đúng các quyền tương ứng.'],
                ],
            ],
            [
                'id' => '80000000-0000-4000-8000-000000000005',
                'u1' => $leaderUserId,
                'u2' => $qaUserId,
                'messages' => [
                    ['sender' => $leaderUserId, 'text' => 'Phát ơi, kiểm tra giúp mình query Cypher trên cụm Neo4j nhé.'],
                    ['sender' => $qaUserId, 'text' => 'Mình đang profiling rồi, index đã được tối ưu hóa theo node ID.'],
                ],
            ],
        ];

        foreach ($directPairs as $dp) {
            $convId = $dp['id'];
            $directKey = $dp['u1'] < $dp['u2'] ? "{$dp['u1']}:{$dp['u2']}" : "{$dp['u2']}:{$dp['u1']}";
            Conversation::query()->updateOrCreate(
                ['_id' => $convId],
                [
                    'organizationId' => $organizationId,
                    'type' => 'direct',
                    'directKey' => $directKey,
                    'createdByUserId' => $dp['u1'],
                    'status' => 'active',
                    'memberCount' => 2,
                    'updatedAt' => now(),
                ]
            );

            foreach ([$dp['u1'], $dp['u2']] as $u) {
                ConversationMember::query()->updateOrCreate(
                    ['conversationId' => $convId, 'userId' => $u],
                    [
                        'organizationId' => $organizationId,
                        'memberRole' => $u === $dp['u1'] ? 'owner' : 'member',
                        'membershipStatus' => 'active',
                        'joinedAt' => now()->subDays(5),
                        'unreadCount' => 0,
                        'isPinned' => false,
                    ]
                );
            }

            foreach ($dp['messages'] as $idx => $m) {
                $clientMsgId = (string) Str::uuid();
                $msgId = (string) Str::uuid();
                Message::query()->updateOrCreate(
                    ['conversationId' => $convId, 'bodyPlaintext' => $m['text']],
                    [
                        '_id' => $msgId,
                        'organizationId' => $organizationId,
                        'conversationId' => $convId,
                        'clientMessageId' => $clientMsgId,
                        'senderUserId' => $m['sender'],
                        'messageType' => 'text',
                        'bodyMarkdown' => $m['text'],
                        'bodyPlaintext' => $m['text'],
                        'attachments' => $m['attachments'] ?? [],
                        'mentions' => [],
                        'sentAt' => now()->subMinutes((count($dp['messages']) - $idx) * 10),
                        'metadata' => ['schemaVersion' => 1],
                    ]
                );
            }
        }

        // 2. Group conversations
        $groupConvs = [
            [
                'id' => '80000000-0000-4000-8000-000000000010',
                'title' => 'Ban Kỹ thuật & Kiến trúc Core',
                'owner' => $qaUserId,
                'members' => [$qaUserId, $leaderUserId, $memberUserId, $adminUserId],
                'messages' => [
                    ['sender' => $qaUserId, 'text' => 'Chào cả nhóm, chúng ta họp nhanh 15 phút về kiến trúc microservices và phân tán dữ liệu nhé.'],
                    ['sender' => $leaderUserId, 'text' => 'Module AI Knowledge đã kết nối mượt mà qua gRPC, độ trễ vector search chỉ 25ms.'],
                    [
                        'sender' => $qaUserId,
                        'text' => 'Mình vừa gửi bản kiến trúc hệ thống mới nhất, cả team xem qua nhé!',
                        'attachments' => [
                            ['id' => (string) Str::uuid(), 'filename' => 'Kien_truc_Microservices_Core_System_2026.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 4823449],
                        ],
                    ],
                    ['sender' => $memberUserId, 'text' => 'Dạ em đã tải bản PDF về nghiên cứu rồi ạ, sơ đồ kết nối API Gateway rất rõ ràng.'],
                ],
            ],
            [
                'id' => '80000000-0000-4000-8000-000000000011',
                'title' => 'Dự án AI & Knowledge Graph',
                'owner' => $leaderUserId,
                'members' => [$leaderUserId, $memberUserId, $qaUserId],
                'messages' => [
                    ['sender' => $leaderUserId, 'text' => 'Đã import thêm 500 bài viết tri thức vào Neo4j và vector database.'],
                    ['sender' => $qaUserId, 'text' => 'Giao diện đồ thị tri thức trên web hiển thị các node và mối liên kết rất trực quan!'],
                    ['sender' => $memberUserId, 'text' => 'Thuật toán tìm kiếm ngữ nghĩa semantic search phản hồi dưới 50ms, độ chính xác cao.'],
                ],
            ],
            [
                'id' => '80000000-0000-4000-8000-000000000012',
                'title' => 'Ban Nhân sự & Văn hóa Openhello',
                'owner' => $hrUserId,
                'members' => [$hrUserId, $memberUserId, $adminUserId, $leaderUserId],
                'messages' => [
                    [
                        'sender' => $hrUserId,
                        'text' => 'Chúc mừng sinh nhật các thành viên có ngày sinh trong tháng này!',
                        'attachments' => [
                            ['id' => (string) Str::uuid(), 'filename' => 'Lich_Sinh_Nhat_Cong_Ty_Thang_11.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 1572864],
                        ],
                    ],
                    ['sender' => $hrUserId, 'text' => 'Chiều nay 16h có tiệc trà nhẹ tại sảnh tầng 3 nhé cả nhà ơi 🎉 Mời mọi người tham gia đông đủ!'],
                    ['sender' => $memberUserId, 'text' => 'Cảm ơn Ban Nhân sự nhiều ạ! Team Kỹ thuật sẽ có mặt đúng giờ.'],
                ],
            ],
        ];

        foreach ($groupConvs as $gc) {
            $convId = $gc['id'];
            Conversation::query()->updateOrCreate(
                ['_id' => $convId],
                [
                    'organizationId' => $organizationId,
                    'type' => 'group',
                    'title' => $gc['title'],
                    'createdByUserId' => $gc['owner'],
                    'status' => 'active',
                    'memberCount' => count($gc['members']),
                    'updatedAt' => now(),
                ]
            );

            foreach ($gc['members'] as $u) {
                ConversationMember::query()->updateOrCreate(
                    ['conversationId' => $convId, 'userId' => $u],
                    [
                        'organizationId' => $organizationId,
                        'memberRole' => $u === $gc['owner'] ? 'owner' : 'member',
                        'membershipStatus' => 'active',
                        'joinedAt' => now()->subDays(7),
                        'unreadCount' => 0,
                        'isPinned' => false,
                    ]
                );
            }

            foreach ($gc['messages'] as $idx => $m) {
                $clientMsgId = (string) Str::uuid();
                $msgId = (string) Str::uuid();
                Message::query()->updateOrCreate(
                    ['conversationId' => $convId, 'bodyPlaintext' => $m['text']],
                    [
                        '_id' => $msgId,
                        'organizationId' => $organizationId,
                        'conversationId' => $convId,
                        'clientMessageId' => $clientMsgId,
                        'senderUserId' => $m['sender'],
                        'messageType' => 'text',
                        'bodyMarkdown' => $m['text'],
                        'bodyPlaintext' => $m['text'],
                        'attachments' => $m['attachments'] ?? [],
                        'mentions' => [],
                        'sentAt' => now()->subMinutes((count($gc['messages']) - $idx) * 15),
                        'metadata' => ['schemaVersion' => 1],
                    ]
                );
            }
        }
    }
}
