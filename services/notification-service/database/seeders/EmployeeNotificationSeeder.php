<?php

namespace Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeNotificationSeeder extends Seeder
{
    public function run(): void
    {
        // These posts and channel memberships come from the social EmployeePairSeeder.
        $recipients = [
            1 => ['01a1ec21-bf62-7081-8ff2-82d9c12de715', 'Phạm Phát', 'Bàn giao ca trực vào sáng thứ Hai'],
            2 => ['30000000-0000-4000-8000-000000000005', 'SangPT', 'Đọc bảng tin bằng bàn phím'],
        ];
        $now = now();
        DB::transaction(function () use ($recipients, $now) {
            foreach ($recipients as $slot => [$user, $peer, $title]) {
                $post = sprintf('60100000-0000-4000-8000-%012d', $slot);
                $rows = [
                    ['comment', "$peer đã bình luận về bài viết của bạn", $title, 'social', 'post', $post, 8, false],
                    ['announcement', 'Tech & Architecture có thông báo mới', 'Mời các thành viên góp ý cho buổi chia sẻ kỹ thuật tuần này.', 'social', 'channel', '60000000-0000-4000-8000-000000000010', 12, false],
                    ['post_like', "$peer đã thích bài viết của bạn", $title, 'social', 'post', $post, 18, false],
                    ['post_share', "$peer đã chia sẻ bài viết của bạn", $title, 'social', 'post', $post, 27, false],
                    ['post_save', 'Đã lưu bài viết', "Bạn có thể xem lại “{$title}” trong mục bài viết đã lưu.", 'social', 'post', $post, 38, true],
                    ['profile_update_request', 'Phòng Nhân sự đề nghị cập nhật hồ sơ', 'Vui lòng kiểm tra số điện thoại và thông tin liên hệ trong hồ sơ nhân viên.', 'identity', 'user', $user, 55, true],
                ];
                foreach ($rows as [$type, $heading, $body, $service, $sourceType, $sourceId, $minutes, $read]) {
                    $created = $now->copy()->subMinutes($minutes);
                    $notification = Notification::firstOrCreate([
                        'id' => sprintf('90100000-0000-4000-8000-%012d', $slot * 100 + $minutes),
                    ], [
                        'organization_id' => '10000000-0000-4000-8000-000000000001',
                        'recipient_user_id' => $user, 'notification_type' => $type, 'title' => $heading, 'body' => $body,
                        'source_service' => $service, 'source_type' => $sourceType, 'source_id' => $sourceId,
                        'in_app_enabled' => true, 'created_at' => $created,
                        'seen_at' => $read ? $created : null, 'read_at' => $read ? $created : null,
                    ]);
                    if ($sourceType === 'post') {
                        $actor = $type === 'post_save' ? $user : $recipients[3 - $slot][0];
                        $notification->update(['payload_json' => json_encode(['actor_user_id' => $actor])]);
                    }
                }
            }
        });
    }
}
