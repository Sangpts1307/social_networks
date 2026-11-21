<?php

namespace Database\Seeders;

use App\Http\Controllers\Api\ChatMessageController;
use App\Models\ChatReadModel;
use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeePairSeeder extends Seeder
{
    public function run(): void
    {
        $org = '10000000-0000-4000-8000-000000000001';
        $sang = '01a1ec21-bf62-7081-8ff2-82d9c12de715';
        $phat = '30000000-0000-4000-8000-000000000005';
        $qa = '30000000-0000-4000-8000-000000000004';
        foreach ([$sang => 'SangPT', $phat => 'Phạm Phát'] as $id => $name) {
            ChatReadModel::updateOrCreate(['_id' => $id], ['organizationId' => $org, 'displayName' => $name]);
        }
        $chats = [
            [1, null, [$sang, $phat], [
                [$sang, 'Phát ơi, mình đã đăng bản ghi chú bàn giao ca trực. Bạn xem giúp nhé.'],
                [$phat, 'Mình vừa đọc và để lại bình luận rồi. Sang có rảnh chiều nay để chạy thử cùng mình không?'],
                [$sang, 'Được nhé, 15 giờ mình vào phòng họp nhỏ.'],
                [$phat, 'Hẹn Sang lúc 15 giờ, mình sẽ chuẩn bị máy thử.'],
            ]],
            [2, 'Kiểm thử bảng tin', [$phat, $sang, $qa], [
                [$phat, 'Nhóm mình dùng cuộc trò chuyện này để ghi nhận kết quả thử bảng tin nhé.'],
                [$qa, 'Mình phụ trách thử trên màn hình nhỏ.'],
                [$sang, 'Mình sẽ kiểm tra điều hướng bằng bàn phím và gửi nhận xét ở đây.'],
            ]],
            [3, 'Chạy bộ sau giờ làm', [$sang, $phat, $qa], [
                [$sang, 'Mình gửi lại điểm hẹn: cổng chính công viên, 17h45 thứ Năm.'],
                [$phat, 'Mình sẽ đợi ở ghế đá cạnh cổng.'],
                [$qa, 'Cho mình nhập nhóm đi bộ đoạn cuối nhé!'],
            ]],
        ];
        foreach ($chats as [$number, $title, $users, $messages]) {
            $id = sprintf('80100000-0000-4000-8000-%012d', $number);
            $key = $title === null ? ['organizationId' => $org, 'directKey' => "$sang:$phat"] : ['_id' => $id];
            $conversation = DB::connection('mongodb')->transaction(function () use ($key, $id, $org, $title, $users) {
                $conversation = Conversation::firstOrCreate($key, ['_id' => $id, 'organizationId' => $org,
                    'type' => $title === null ? 'direct' : 'group', 'title' => $title, 'createdByUserId' => $users[0],
                    'status' => 'active', 'memberCount' => count($users), 'writeVersion' => 0, 'createdAt' => now(), 'updatedAt' => now()]);
                foreach ($users as $user) {
                    ConversationMember::firstOrCreate(['conversationId' => $conversation->getKey(), 'userId' => $user],
                        ['organizationId' => $org, 'memberRole' => $user === $users[0] ? 'owner' : 'member',
                            'membershipStatus' => 'active', 'unreadCount' => 0, 'joinedAt' => now()]);
                }
                return $conversation;
            });
            foreach ($messages as $index => [$sender, $body]) {
                $messageId = sprintf('80200000-0000-4000-8000-%012d', $number * 100 + $index);
                if (Message::where('conversationId', $conversation->getKey())->where('clientMessageId', $messageId)->exists()) {
                    continue;
                }
                $request = Request::create('/', 'POST', ['client_message_id' => $messageId, 'body_markdown' => $body]);
                $request->attributes->set('chat_actor', ['organization_id' => $org, 'user_id' => $sender]);
                app(ChatMessageController::class)->send($request, $conversation->getKey());
            }
        }
    }
}
