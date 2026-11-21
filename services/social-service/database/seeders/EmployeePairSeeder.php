<?php

namespace Database\Seeders;

use App\Models\ChannelMember;
use App\Models\Post;
use App\Models\PostAttachment;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\PostStat;
use App\Models\SocialChannel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeePairSeeder extends Seeder
{
    public function run(): void
    {
        $org = '10000000-0000-4000-8000-000000000001';
        $sang = '01a1ec21-bf62-7081-8ff2-82d9c12de715';
        $phat = '30000000-0000-4000-8000-000000000005';
        $posts = [
            [1, $sang, 'tech-architecture', 'Bàn giao ca trực vào sáng thứ Hai', 'Mình đã gom đường dẫn dashboard và lịch sao lưu vào cùng một trang. Người nhận ca có thể xem lần sao lưu gần nhất ngay ở đầu trang.', 'Mình vừa nhận ca thử, tìm được cả lịch sử khôi phục rồi.'],
            [2, $phat, 'tech-architecture', 'Đọc bảng tin bằng bàn phím', 'Mình vừa thử đi từ ô tìm kiếm tới phần bình luận chỉ bằng phím Tab. Có hai chỗ mất dấu focus khi đóng hộp thoại, mình đã ghi lại để sửa.', 'Sang sẽ thử lại luồng đó trên Safari nhé.'],
            [3, $sang, 'ai-knowledge', 'Từ khóa nào đồng nghiệp thường tìm?', 'Trong buổi hướng dẫn sáng nay, ba bạn mới đều gõ “xin nghỉ” thay vì “nghỉ phép”. Mình đề xuất thêm cặp từ đồng nghĩa này vào bộ câu hỏi thử.', 'Phát bổ sung thêm trường hợp “đổi ca” và “đổi lịch trực” nhé.'],
            [4, $phat, 'life-sharing', 'Đổi sách giờ nghỉ trưa', 'Trưa thứ Sáu mình mang cuốn Nhập môn thiết kế tới bàn gần cửa sổ. Ai có sách nhiếp ảnh muốn đổi đọc một tuần thì nhắn mình nhé.', 'Mình mang cuốn về bố cục ảnh, hẹn Phát ở căng tin!'],
            [5, $sang, 'life-sharing', 'Chạy một vòng công viên sau giờ làm', 'Chiều thứ Năm mình chạy nhẹ khoảng ba cây số. Nhóm gặp ở cổng chính lúc 17h45, mọi người có thể đi bộ đoạn cuối cùng nhau.', 'Cho Phát tham gia với, mình sẽ mang nước.'],
            [6, $phat, 'ai-knowledge', 'Thử tìm tài liệu bằng tên viết tắt', 'Khi nhập tên dự án viết tắt, mình tìm được biên bản họp nhưng chưa thấy hướng dẫn bàn giao. Mình vừa thêm tên đầy đủ vào phần mô tả tài liệu.', 'Mình tìm lại bằng cả hai cách, giờ đều thấy hướng dẫn rồi.'],
        ];

        $photos = [
            1 => '1558494949-ef010cbdcc31',
            2 => '1587829741301-dc798b83add3',
            4 => '1495446815901-a7297e633e8d',
            5 => '1472396961693-142e6e269027',
        ];

        DB::transaction(function () use ($org, $sang, $phat, $posts, $photos) {
            $channels = SocialChannel::where('organization_id', $org)
                ->whereIn('code', ['general', 'tech-architecture', 'ai-knowledge', 'life-sharing', 'hr-culture', 'business-sales'])
                ->pluck('id', 'code');
            foreach ($channels as $channel) {
                foreach ([$sang, $phat] as $user) {
                    ChannelMember::firstOrCreate(['channel_id' => $channel, 'user_id' => $user],
                        ['member_role' => 'member', 'membership_status' => 'active', 'joined_at' => now()->subWeek()]);
                }
            }
            foreach ($posts as [$number, $author, $channel, $title, $body, $reply]) {
                $date = now()->subHours($number);
                $peer = $author === $sang ? $phat : $sang;
                $post = Post::firstOrCreate(['id' => sprintf('60100000-0000-4000-8000-%012d', $number)], [
                    'organization_id' => $org, 'channel_id' => $channels[$channel], 'author_user_id' => $author,
                    'post_type' => 'update', 'title' => $title, 'body_markdown' => $body, 'body_plaintext' => $body,
                    'visibility' => 'organization', 'publication_status' => 'published', 'moderation_status' => 'clear',
                    'published_at' => $date, 'created_by_user_id' => $author, 'created_at' => $date,
                ]);
                if (isset($photos[$number])) {
                    PostAttachment::firstOrCreate(
                        ['id' => sprintf('60200000-0000-4000-8000-%012d', $number)],
                        [
                            'post_id' => $post->id,
                            'attachment_type' => 'image',
                            'object_key' => 'https://images.unsplash.com/photo-'.$photos[$number].'?w=960&fit=crop&q=78',
                            'mime_type' => 'image/jpeg',
                            'scan_status' => 'clean',
                        ]
                    );
                }
                if (!$post->wasRecentlyCreated) {
                    continue;
                }
                PostComment::create(['organization_id' => $org, 'post_id' => $post->id, 'author_user_id' => $peer,
                    'body_markdown' => $reply, 'body_plaintext' => $reply, 'status' => 'published', 'moderation_status' => 'clear']);
                PostReaction::create(['post_id' => $post->id, 'user_id' => $peer, 'reaction_type' => 'like']);
                PostStat::create(['post_id' => $post->id, 'comment_count' => 1, 'reaction_count' => 1, 'last_interaction_at' => now()]);
            }
        });
    }
}
