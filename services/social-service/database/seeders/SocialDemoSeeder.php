<?php

namespace Database\Seeders;

use App\Models\ChannelMember;
use App\Models\Post;
use App\Models\PostAttachment;
use App\Models\PostComment;
use App\Models\PostEvent;
use App\Models\PostReaction;
use App\Models\PostStat;
use App\Models\SocialCategory;
use App\Models\SocialChannel;
use App\Models\SocialTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SocialDemoSeeder extends Seeder
{
    public function run(): void
    {
        $organizationId = '10000000-0000-4000-8000-000000000001';

        // 19 User IDs corresponding to identity-service
        $users = [
            'admin'           => '30000000-0000-4000-8000-000000000001', // Nguyễn Quản Trị
            'hr_manager'      => '30000000-0000-4000-8000-000000000002', // Lê Thị Mai Phương (HR Manager)
            'team_leader'     => '30000000-0000-4000-8000-000000000003', // Trần Đức Thắng (Trưởng nhóm sản phẩm)
            'qa_expert'       => '30000000-0000-4000-8000-000000000004', // Nguyễn Minh Anh (QA Expert)
            'software_eng'    => '30000000-0000-4000-8000-000000000005', // Phạm Phát (Kỹ sư phần mềm)
            'ceo'             => '30000000-0000-4000-8000-000000000006', // Trần Minh Quân (CEO)
            'coo'             => '30000000-0000-4000-8000-000000000007', // Lê Thu Hà (COO)
            'recruiter'       => '30000000-0000-4000-8000-000000000008', // Phạm Ngọc Ánh (Tuyển dụng)
            'cnb'             => '30000000-0000-4000-8000-000000000009', // Đỗ Hoàng Yến (C&B)
            'chief_account'   => '30000000-0000-4000-8000-000000000010', // Vũ Thị Mai Hương (Kế toán trưởng)
            'accountant'      => '30000000-0000-4000-8000-000000000011', // Ngô Thanh Tùng (Kế toán)
            'sales_manager'   => '30000000-0000-4000-8000-000000000012', // Hoàng Gia Bảo (Head of Sales)
            'sales_b2b'       => '30000000-0000-4000-8000-000000000013', // Đặng Quốc Khánh (Sales B2B)
            'cskh_lead'       => '30000000-0000-4000-8000-000000000014', // Bùi Phương Thảo (CSKH Lead)
            'mkt_manager'     => '30000000-0000-4000-8000-000000000015', // Trịnh Khánh Linh (Head of Marketing)
            'content_mkt'     => '30000000-0000-4000-8000-000000000016', // Lý Minh Khôi (Content MKT)
            'product_manager' => '30000000-0000-4000-8000-000000000017', // Phan Tuấn Kiệt (Product Manager)
            'tech_lead'       => '30000000-0000-4000-8000-000000000018', // Võ Thành Luân (Head of Engineering)
            'devops'          => '30000000-0000-4000-8000-000000000019', // Hồ Trọng Nghĩa (DevOps)
        ];

        $allUserIds = array_values($users);

        // 1. Tạo các Channels tiêu biểu
        $channelsData = [
            [
                'id' => '60000000-0000-4000-8000-000000000001',
                'code' => 'general',
                'name' => 'Thông báo chung',
                'description' => 'Kênh thông tin và truyền thông chính thức của Ban Giám đốc và toàn công ty.',
                'owner' => $users['ceo'],
                'policy' => 'managed',
            ],
            [
                'id' => '60000000-0000-4000-8000-000000000010',
                'code' => 'tech-architecture',
                'name' => 'Tech & Architecture',
                'description' => 'Nơi trao đổi về kiến trúc hệ thống, phát triển sản phẩm và kinh nghiệm kỹ thuật.',
                'owner' => $users['tech_lead'],
                'policy' => 'open',
            ],
            [
                'id' => '60000000-0000-4000-8000-000000000011',
                'code' => 'ai-knowledge',
                'name' => 'AI & Knowledge Graph',
                'description' => 'Cùng chia sẻ thử nghiệm AI, dữ liệu đồ thị và ứng dụng tri thức trong công việc.',
                'owner' => $users['tech_lead'],
                'policy' => 'open',
            ],
            [
                'id' => '60000000-0000-4000-8000-000000000012',
                'code' => 'hr-culture',
                'name' => 'HR & Văn hóa Doanh nghiệp',
                'description' => 'Kết nối thành viên, cập nhật hoạt động nhân sự và lan tỏa văn hóa công ty.',
                'owner' => $users['hr_manager'],
                'policy' => 'open',
            ],
            [
                'id' => '60000000-0000-4000-8000-000000000013',
                'code' => 'business-sales',
                'name' => 'Kinh doanh & Khách hàng',
                'description' => 'Cập nhật tin tức thị trường, chiến thắng hợp đồng mới và phản hồi khách hàng.',
                'owner' => $users['sales_manager'],
                'policy' => 'open',
            ],
            [
                'id' => '60000000-0000-4000-8000-000000000014',
                'code' => 'life-sharing',
                'name' => 'Góc đời sống & Chia sẻ',
                'description' => 'Không gian thư giãn, chia sẻ sách hay, khoảnh khắc văn phòng và hoạt động ngoại khóa.',
                'owner' => $users['coo'],
                'policy' => 'open',
            ],
        ];

        $channels = [];
        foreach ($channelsData as $c) {
            $channels[$c['code']] = SocialChannel::query()->updateOrCreate(
                ['id' => $c['id']],
                [
                    'organization_id' => $organizationId,
                    'code' => $c['code'],
                    'name' => $c['name'],
                    'description' => $c['description'],
                    'channel_type' => 'organization',
                    'visibility' => 'organization',
                    'membership_policy' => $c['policy'],
                    'owner_user_id' => $c['owner'],
                    'status' => 'active',
                ]
            );

            // Gán tất cả thành viên vào channel
            foreach ($allUserIds as $uId) {
                ChannelMember::query()->updateOrCreate(
                    ['channel_id' => $c['id'], 'user_id' => $uId],
                    [
                        'member_role' => $uId === $c['owner'] ? 'owner' : 'member',
                        'membership_status' => 'active',
                        'joined_at' => now()->subDays(30),
                    ]
                );
            }
        }

        // 2. Categories
        SocialCategory::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'code' => 'news'],
            ['id' => '60000000-0000-4000-8000-000000000002', 'name' => 'Tin nội bộ', 'category_type' => 'news', 'status' => 'active']
        );
        SocialCategory::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'code' => 'culture'],
            ['id' => '60000000-0000-4000-8000-000000000028', 'name' => 'Văn hóa & Đời sống', 'category_type' => 'culture', 'status' => 'active']
        );

        // 3. Danh sách các bài viết phong phú, có hình ảnh và tương tác
        $postsData = [
            // Post 1: CEO Thông điệp chiến lược (Announcement - Pinned)
            [
                'id' => '60000000-0000-4000-8000-000000000101',
                'channel_id' => $channels['general']->id,
                'author_id' => $users['ceo'],
                'post_type' => 'announcement',
                'title' => 'Thư gửi toàn thể đội ngũ: Định hướng phát triển Quý 4/2026 & Cột mốc 100,000 người dùng',
                'body' => "Chào toàn thể anh chị em trong đại gia đình công ty!\n\nNhìn lại chặng đường 9 tháng vừa qua, tôi vô cùng tự hào về những nỗ lực phi thường của từng khối phòng ban. Từ những ngày đầu xây dựng nền tảng cho đến hôm nay, hệ thống của chúng ta đã chính thức cán mốc 100,000 active users.\n\nTrong Quý 4 sắp tới, mục tiêu chiến lược của chúng ta là tập trung tối ưu hóa trải nghiệm sản phẩm, mở rộng phân khúc khách hàng doanh nghiệp (Enterprise) và tích hợp sâu AI Knowledge Graph vào vận hành hàng ngày. Thành công này thuộc về sự tận tâm của từng cá nhân trong tập thể.\n\nCảm ơn mọi người và chúc chúng ta một quý mới bùng nổ hơn nữa!",
                'image' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'town_hall_meeting.jpg',
                'published_at' => now()->subHours(2),
                'pinned' => true,
                'comments' => [
                    ['user' => $users['coo'], 'content' => 'Một cột mốc rất ý nghĩa! Toàn khối Vận hành đã sẵn sàng cho các kế hoạch mở rộng trong Quý 4.'],
                    ['user' => $users['tech_lead'], 'content' => 'Khối Kỹ thuật cam kết giữ vững hạ tầng ổn định, tốc độ và bảo mật cao nhất để phục vụ mục tiêu 100k+ người dùng.'],
                    ['user' => $users['sales_manager'], 'content' => 'Chúc mừng công ty! Khối Kinh doanh đang tăng tốc chốt thêm các hợp đồng Enterprise lớn trong tháng này.'],
                ],
                'reactions' => [$users['coo'], $users['tech_lead'], $users['sales_manager'], $users['hr_manager'], $users['product_manager'], $users['software_eng'], $users['devops'], $users['accountant'], $users['recruiter']],
            ],

            // Post 2: Lịch trình công ty (Event - Pinned)
            [
                'id' => '60000000-0000-4000-8000-000000000102',
                'channel_id' => $channels['general']->id,
                'author_id' => $users['ceo'],
                'post_type' => 'event',
                'title' => 'Lịch trình sự kiện & All-Hands Meeting tháng 11/2026',
                'body' => "📅 Thứ Hai: Họp cập nhật tiến độ liên phòng ban (All-Hands Sync)\n💡 Thứ Tư: Tech Talk chuyên đề AI & Knowledge Graph tại phòng Hội thảo tầng 6\n🤝 Thứ Sáu: Happy Hour & Chào đón thành viên mới gia nhập công ty",
                'image' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'company_schedule.jpg',
                'published_at' => now()->subHours(8),
                'pinned' => true,
                'event' => [
                    'starts_at' => now()->next('Monday')->setTime(9, 0),
                    'ends_at' => now()->next('Monday')->setTime(10, 30),
                    'location' => 'Hội trường lớn Tầng 6 & Trực tuyến qua Base Meeting',
                ],
                'comments' => [
                    ['user' => $users['recruiter'], 'content' => 'Đã chuẩn bị quà welcome và teabreak cho các bạn tân binh vào thứ Sáu này rồi ạ!'],
                ],
                'reactions' => [$users['coo'], $users['hr_manager'], $users['recruiter'], $users['cnb'], $users['chief_account']],
            ],

            // Post 3: Tech Lead Võ Thành Luân - Event-Driven Architecture
            [
                'id' => '60000000-0000-4000-8000-000000000103',
                'channel_id' => $channels['tech-architecture']->id,
                'author_id' => $users['tech_lead'],
                'post_type' => 'update',
                'title' => 'Kiến trúc Event-Driven Microservices với Transactional Outbox Pattern đã hoàn thiện',
                'body' => "Chia sẻ cùng anh em khối Kỹ thuật: Nhóm Backend vừa hoàn tất việc tích hợp Transactional Outbox Pattern kết hợp RabbitMQ và Redis Streams trên toàn bộ các services (Identity, Social, Chat, Notification, Learning).\n\nLợi ích đạt được:\n1. Giải quyết triệt để bài toán Dual-Write và bảo toàn tính toàn vẹn dữ liệu giữa MySQL và Message Broker\n2. Độ trễ lan truyền sự kiện (End-to-End latency) giảm xuống dưới 45ms\n3. Hỗ trợ Idempotency key bảo vệ hệ thống trước tình huống mạng chập chờn hoặc retry requests.\n\nAnh em có thể tham khảo RFC chi tiết trong kho tài liệu kỹ thuật nhé!",
                'image' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'microservices_architecture.jpg',
                'published_at' => now()->subHours(14),
                'comments' => [
                    ['user' => $users['software_eng'], 'content' => 'Kiến trúc chạy rất mượt anh ạ. Đã test với kịch bản 1,000 messages/giây mà CPU MySQL chỉ ở mức 15%.'],
                    ['user' => $users['devops'], 'content' => 'Dashboard Grafana theo dõi Outbox worker đã được cấu hình alert qua Slack rồi nhé anh em.'],
                    ['user' => $users['qa_expert'], 'content' => 'Test suite idempotency check pass 100%. Rất yên tâm cho đợt release lớn sắp tới!'],
                ],
                'reactions' => [$users['software_eng'], $users['devops'], $users['qa_expert'], $users['product_manager'], $users['admin']],
            ],

            // Post 4: Tech Lead Võ Thành Luân - Graph RAG Neo4j
            [
                'id' => '60000000-0000-4000-8000-000000000104',
                'channel_id' => $channels['ai-knowledge']->id,
                'author_id' => $users['tech_lead'],
                'post_type' => 'update',
                'title' => 'Thực nghiệm Graph RAG với Neo4j: Cải thiện 38% độ chính xác cho câu hỏi nghiệp vụ đa bước',
                'body' => "So sánh thực tế giữa Vector Search truyền thống (Naive RAG) và Graph RAG trên bộ 1,200 câu hỏi quy chế nội bộ:\n\n- Khi câu hỏi yêu cầu liên kết nhiều thực thể (ví dụ: 'Quy trình nghỉ phép của nhân sự thử việc tại chi nhánh miền Nam do ai duyệt?'), Graph RAG giúp mô hình LLM lần theo đồ thị quan hệ chính xác 98.4%, trong khi Vector Search thông thường chỉ đạt 62% do mất ngữ cảnh phân cấp.\n\nHôm nay chúng tôi đã deploy bản beta tại Square AI. Mời mọi người vào test và cho feedback nhé!",
                'image' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'graph_rag_neo4j.jpg',
                'published_at' => now()->subDay(),
                'comments' => [
                    ['user' => $users['team_leader'], 'content' => 'Đã thử đặt các câu hỏi về phân quyền scoped RBAC, bot trả lời rất rõ ràng và trích dẫn chuẩn node điều lệ.'],
                    ['user' => $users['hr_manager'], 'content' => 'Tính năng này sẽ giúp HR giảm tải rất nhiều câu hỏi lặp đi lặp lại từ nhân sự mới!'],
                ],
                'reactions' => [$users['team_leader'], $users['hr_manager'], $users['software_eng'], $users['qa_expert'], $users['ceo']],
            ],

            // Post 5: HR Manager Lê Thị Mai Phương - Chào đón tân binh
            [
                'id' => '60000000-0000-4000-8000-000000000105',
                'channel_id' => $channels['hr-culture']->id,
                'author_id' => $users['hr_manager'],
                'post_type' => 'news',
                'title' => 'Chào đón 4 thành viên mới gia nhập đại gia đình Openhello trong tuần này!',
                'body' => "HR xin gửi lời chào nồng nhiệt nhất tới 4 tân binh gia nhập tuần này:\n1. Nguyễn Hoàng Long - Frontend Engineer (ENG-FE)\n2. Trần Bảo Trâm - UI/UX Designer (ENG-PROD)\n3. Đỗ Quốc Tuấn - B2B Sales Executive (SALES-B2B)\n4. Hoàng Bích Ngọc - Chuyên viên Truyền thông Nội bộ (MKT-CONTENT)\n\nChúc các bạn có một hành trình thật nhiều niềm vui, trải nghiệm thú vị và bứt phá sự nghiệp tại công ty. Anh em các team nhớ dắt tân binh đi làm quen và ăn trưa nhé!",
                'image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'new_members_welcome.jpg',
                'published_at' => now()->subDays(2),
                'comments' => [
                    ['user' => $users['recruiter'], 'content' => 'Chào mừng các bạn! Chúc mọi người onboarding thật suôn sẻ.'],
                    ['user' => $users['product_manager'], 'content' => 'Chào bạn Trâm gia nhập team Design nhé, nhiều wireframe đang chờ bạn cùng sáng tạo! 🎉'],
                    ['user' => $users['sales_manager'], 'content' => 'Chào đón Tuấn về đội Kinh doanh, cùng nhau chinh phục mục tiêu tháng 11 nào!'],
                ],
                'reactions' => [$users['recruiter'], $users['product_manager'], $users['sales_manager'], $users['content_mkt'], $users['cnb'], $users['ceo']],
            ],

            // Post 6: Head of Sales Hoàng Gia Bảo - Hợp đồng Enterprise lớn
            [
                'id' => '60000000-0000-4000-8000-000000000106',
                'channel_id' => $channels['business-sales']->id,
                'author_id' => $users['sales_manager'],
                'post_type' => 'update',
                'title' => 'Chính thức ký kết hợp đồng Enterprise 2.5 tỷ VNĐ với Tập đoàn Vạn An Phát!',
                'body' => "Tin vui đầu tuần cho toàn công ty!\n\nSau 3 tháng thương thảo, pitching giải pháp và demo tích hợp thực tế, đội ngũ B2B Sales đã chính thức đặt bút ký kết hợp đồng cung cấp nền tảng quản trị công việc toàn diện cho Tập đoàn Vạn An Phát với quy mô hơn 1,500 nhân sự.\n\nCảm ơn đội ngũ Kỹ thuật đã hỗ trợ thức đêm làm PoC giải pháp, cảm ơn CSKH và Pháp chế đã đồng hành xuyên suốt. Chiến thắng này là minh chứng rõ nét cho chất lượng sản phẩm của chúng ta!",
                'image' => 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'enterprise_contract_signing.jpg',
                'published_at' => now()->subDays(3),
                'comments' => [
                    ['user' => $users['ceo'], 'content' => 'Xuất sắc lắm team Sales! Một thương vụ khẳng định vị thế thương hiệu của chúng ta trên thị trường.'],
                    ['user' => $users['sales_b2b'], 'content' => 'Cảm ơn anh Bảo đã dẫn dắt deal này. Team B2B đang tiếp tục chăm sóc 3 deal lớn nữa cho tháng tới.'],
                    ['user' => $users['cskh_lead'], 'content' => 'Team CSKH đã chuẩn bị sẵn tài liệu đào tạo và kickoff triển khai với khách hàng vào thứ Hai tuần tới!'],
                ],
                'reactions' => [$users['ceo'], $users['sales_b2b'], $users['cskh_lead'], $users['coo'], $users['mkt_manager'], $users['chief_account']],
            ],

            // Post 7: Product Manager Phan Tuấn Kiệt - Release v2.4
            [
                'id' => '60000000-0000-4000-8000-000000000107',
                'channel_id' => $channels['general']->id,
                'author_id' => $users['product_manager'],
                'post_type' => 'news',
                'title' => 'Base Inside v2.4 chính thức ra mắt: Trải nghiệm bảng tin mượt mà và trực quan hơn',
                'body' => "Xin chào cả nhà! Đội ngũ Sản phẩm rất vui mừng thông báo phiên bản Base Inside v2.4 đã sẵn sàng phục vụ mọi người với những nâng cấp đáng giá:\n\n✨ Giao diện Feed mới tải nhanh gấp 3 lần nhờ Virtual Scrolling\n📸 Hỗ trợ hiển thị ảnh độ phân giải cao và tab Media riêng cho từng kênh\n📌 Ghim thông báo quan trọng và sự kiện nổi bật ngay đầu trang\n💬 Luồng thảo luận đa cấp giúp trao đổi công việc mạch lạc hơn\n\nHãy trải nghiệm ngay và để lại ý kiến đóng góp cho team Product nhé!",
                'image' => 'https://images.unsplash.com/photo-1531403009284-440f080d1e12?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'product_release_v24.jpg',
                'published_at' => now()->subDays(4),
                'comments' => [
                    ['user' => $users['tech_lead'], 'content' => 'Giao diện mượt mà và load ảnh cực nhanh. Chúc mừng team Product & Frontend!'],
                    ['user' => $users['content_mkt'], 'content' => 'Bảng tin mới nhìn chuyên nghiệp và hiện đại hơn hẳn. Thích nhất tính năng xem ảnh full screen.'],
                ],
                'reactions' => [$users['tech_lead'], $users['content_mkt'], $users['software_eng'], $users['qa_expert'], $users['coo']],
            ],

            // Post 8: Recruiter Phạm Ngọc Ánh - Giải thể thao Open Cup
            [
                'id' => '60000000-0000-4000-8000-000000000108',
                'channel_id' => $channels['hr-culture']->id,
                'author_id' => $users['recruiter'],
                'post_type' => 'announcement',
                'title' => 'Khởi động giải Pickleball & Chạy bộ nội bộ "Open Cup 2026" - Đăng ký ngay hôm nay!',
                'body' => "Đã đến lúc tạm gác lại bàn phím và cùng xỏ giày ra sân thôi anh chị em ơi!\n\n🏆 Giải đấu thể thao nội bộ thường niên 'Open Cup 2026' chính thức mở cổng đăng ký từ hôm nay với 2 bộ môn:\n1. Pickleball đôi nam - nữ phối hợp (Sân D-Sports, thứ Bảy hàng tuần)\n2. Chạy bộ cự ly 5km - 10km (Tích lũy qua Strava)\n\nTổng giá trị giải thưởng lên đến 20,000,000 VNĐ kèm cúp vô địch danh giá! Ban Giám đốc cũng sẽ trực tiếp tham gia tranh tài. Hạn chót đăng ký: 23:59 ngày thứ Sáu tuần này.",
                'image' => 'https://images.unsplash.com/photo-1526676037777-05a232554f77?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'sports_cup_2026.jpg',
                'published_at' => now()->subDays(5),
                'comments' => [
                    ['user' => $users['ceo'], 'content' => 'Tôi đã đăng ký một slot đánh đôi Pickleball rồi nhé. Thách đấu anh em team Kỹ thuật! 😎'],
                    ['user' => $users['devops'], 'content' => 'Team DevOps nhận kèo anh Quân ơi, thứ Bảy ra sân phân định thắng thua ạ! 🔥'],
                    ['user' => $users['accountant'], 'content' => 'Em đăng ký giải chạy 10km, quyết tâm giật giải nhất mang tiền thưởng về khao cả phòng!'],
                ],
                'reactions' => [$users['ceo'], $users['devops'], $users['accountant'], $users['sales_b2b'], $users['cskh_lead'], $users['cnb']],
            ],

            // Post 9: Marketing Manager Trịnh Khánh Linh - Triển lãm Tech Summit
            [
                'id' => '60000000-0000-4000-8000-000000000109',
                'channel_id' => $channels['general']->id,
                'author_id' => $users['mkt_manager'],
                'post_type' => 'news',
                'title' => 'Recap triển lãm Vietnam Tech Summit 2026: Gian hàng công ty thu hút hơn 3,000 lượt khách tham quan',
                'body' => "Cuối tuần qua, gian hàng công ty tại triển lãm Vietnam Tech Summit 2026 đã diễn ra thành công rực rỡ ngoài mong đợi!\n\n- Thu thập hơn 450 qualified leads doanh nghiệp tiềm năng\n- 8 cơ quan báo chí và truyền hình đưa tin về giải pháp AI Knowledge Graph\n- Hơn 1,000 phần quà lưu niệm đã được gửi tới khách tham quan\n\nCảm ơn tất cả các tình nguyện viên và nhân sự hỗ trợ đã cháy hết mình trong suốt 3 ngày sự kiện!",
                'image' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'tech_summit_recap.jpg',
                'published_at' => now()->subDays(6),
                'comments' => [
                    ['user' => $users['content_mkt'], 'content' => 'Video recap và phỏng vấn khách hàng đã render xong, chuẩn bị lên sóng fanpage ạ!'],
                    ['user' => $users['sales_manager'], 'content' => 'Đã phân bổ 450 leads cho anh em Sales liên hệ tư vấn trong tuần này rồi.'],
                ],
                'reactions' => [$users['content_mkt'], $users['sales_manager'], $users['ceo'], $users['coo'], $users['product_manager']],
            ],

            // Post 10: DevOps Hồ Trọng Nghĩa - 180 ngày Uptime 99.99%
            [
                'id' => '60000000-0000-4000-8000-000000000110',
                'channel_id' => $channels['tech-architecture']->id,
                'author_id' => $users['devops'],
                'post_type' => 'update',
                'title' => 'Cột mốc 180 ngày Uptime 99.99% - Khi các cụm Kubernetes ngủ ngon giấc!',
                'body' => "Sau đợt chuyển đổi hạ tầng sang cụm Kubernetes Multi-AZ với tính năng Auto-healing và cân bằng tải thông minh, dashboard Grafana sáng nay báo tin vui: Hệ thống của chúng ta đã duy trì chuỗi 180 ngày liên tiếp không có bất kỳ sự cố P0/P1 nào!\n\nBí quyết là gì? Không bao giờ deploy lên production vào chiều thứ Sáu và luôn có sẵn trà sữa cho anh em on-call trực đêm 🧋😄\n\nCảm ơn anh em Dev đã tuân thủ nghiêm ngặt quy trình CI/CD và viết unit test đầy đủ!",
                'image' => 'https://images.unsplash.com/photo-1504639725590-34d0984388bd?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'devops_uptime_monitoring.jpg',
                'published_at' => now()->subDays(7),
                'comments' => [
                    ['user' => $users['tech_lead'], 'content' => 'Xứng đáng thưởng nóng cho anh em DevOps! Giữ vững phong độ nhé Nghĩa ơi.'],
                    ['user' => $users['software_eng'], 'content' => 'Tối nay trà sữa full topping khao anh em thôi anh Nghĩa ơi! 🥤'],
                ],
                'reactions' => [$users['tech_lead'], $users['software_eng'], $users['qa_expert'], $users['admin'], $users['coo']],
            ],

            // Post 11: Phạm Phát - Chia sẻ tối ưu Vue 3 & Vite
            [
                'id' => '60000000-0000-4000-8000-000000000111',
                'channel_id' => $channels['tech-architecture']->id,
                'author_id' => $users['software_eng'],
                'post_type' => 'update',
                'title' => '5 mẹo tối ưu hiệu năng Vue 3 & Vite giúp bundle size giảm 40%',
                'body' => "Trong quá trình tối ưu lại frontend Base Web, mình rút ra 5 kinh nghiệm thực tế rất đáng lưu ý:\n\n1. Async Component kết hợp `defineAsyncComponent` cho các modal và drawer nặng\n2. Tận dụng triệt để Tree-shaking từ thư viện Lucide & Lodash-es\n3. Sử dụng Virtualizer cho danh sách bài viết dài để DOM không bị phình to\n4. Nén ảnh sang định dạng WebP trước khi đưa vào bundle\n5. Tối ưu CSS qua Tailwind JIT compilation.\n\nKết quả thời gian First Contentful Paint (FCP) giảm từ 1.8s xuống chỉ còn 0.4s trên kết nối 4G!",
                'image' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'vue_vite_optimization.jpg',
                'published_at' => now()->subDays(8),
                'comments' => [
                    ['user' => $users['tech_lead'], 'content' => 'Bài viết rất chất lượng. Anh Phát document lại vào wiki kỹ thuật cho các bạn mới nhé.'],
                    ['user' => $users['product_manager'], 'content' => 'Người dùng phản hồi web load nhanh hơn hẳn, mượt như app native vậy.'],
                ],
                'reactions' => [$users['tech_lead'], $users['product_manager'], $users['devops'], $users['team_leader']],
            ],

            // Post 12: Đỗ Hoàng Yến - Khám sức khỏe định kỳ
            [
                'id' => '60000000-0000-4000-8000-000000000112',
                'channel_id' => $channels['hr-culture']->id,
                'author_id' => $users['cnb'],
                'post_type' => 'announcement',
                'title' => 'Thông báo lịch khám sức khỏe định kỳ năm 2026 tại Bệnh viện Đa khoa Quốc tế',
                'body' => "Phòng Nhân sự & C&B xin thông báo tới toàn thể cán bộ nhân viên về chương trình Khám sức khỏe định kỳ hàng năm:\n\n- Thời gian: Ngày 25/11 và 26/11/2026 (Thứ Tư & Thứ Năm)\n- Địa điểm: Bệnh viện Đa khoa Quốc tế Vinmec Central Park\n- Gói khám bao gồm: Khám tổng quát, xét nghiệm máu chuyên sâu, chụp X-quang, tầm soát sức khỏe mắt và cơ xương khớp nghề nghiệp văn phòng.\n\nMọi người vui lòng chọn khung giờ khám phù hợp trên hệ thống trước 17h00 ngày 20/11 để HR sắp xếp xe đưa đón chu đáo nhé!",
                'image' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'annual_health_checkup.jpg',
                'published_at' => now()->subDays(9),
                'comments' => [
                    ['user' => $users['chief_account'], 'content' => 'Đã duyệt gói ngân sách khám sức khỏe nâng cao cho toàn bộ CBNV. Mọi người chú ý đi khám đầy đủ nhé.'],
                ],
                'reactions' => [$users['chief_account'], $users['accountant'], $users['hr_manager'], $users['recruiter'], $users['cskh_lead']],
            ],

            // Post 13: Ngô Thanh Tùng - Tinh tinh ngày lương
            [
                'id' => '60000000-0000-4000-8000-000000000113',
                'channel_id' => $channels['life-sharing']->id,
                'author_id' => $users['accountant'],
                'post_type' => 'update',
                'title' => 'Tinh tinh! Lương và thưởng dự án tháng này đã về tài khoản rồi nhé cả nhà!',
                'body' => "Thông báo ngọt ngào nhất ngày 15 hàng tháng đã được Phòng Kế toán gửi đi thành công tới 100% tài khoản ngân hàng của anh chị em rồi nhé! 💸🎉\n\nTháng này có thêm khoản thưởng nóng cho chiến dịch cán mốc 100k users. Mọi người kiểm tra email nhận phiếu lương (payslip) chi tiết. Chúc anh chị em cuối tuần chi tiêu hợp lý và nhớ để dành quỹ rủ đồng đội đi ăn lẩu nhé!",
                'image' => 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'payday_celebration.jpg',
                'published_at' => now()->subDays(10),
                'comments' => [
                    ['user' => $users['software_eng'], 'content' => 'Cảm ơn anh Tùng và phòng Kế toán! Chiều nay kèo lẩu cá hồi thẳng tiến thôi anh em ơi 🍲'],
                    ['user' => $users['sales_b2b'], 'content' => 'Lương về đúng ngày thứ Sáu, đẹp như một giấc mơ! Cảm ơn công ty nhiều ạ.'],
                ],
                'reactions' => [$users['software_eng'], $users['sales_b2b'], $users['cskh_lead'], $users['content_mkt'], $users['devops'], $users['recruiter'], $users['qa_expert']],
            ],

            // Post 14: Bùi Phương Thảo - CSKH cảm xúc
            [
                'id' => '60000000-0000-4000-8000-000000000114',
                'channel_id' => $channels['business-sales']->id,
                'author_id' => $users['cskh_lead'],
                'post_type' => 'update',
                'title' => 'Lời cảm ơn xúc động từ đối tác bệnh viện sau khi số hóa thành công luồng tiếp nhận bệnh nhân',
                'body' => "Sáng nay, hòm thư CSKH nhận được một bức thư cảm ơn rất dài từ bác sĩ giám đốc Bệnh viện Đa khoa tỉnh:\n\n'Nhờ có giải pháp điều phối ca trực và thông báo nội bộ của các bạn, thời gian chờ đợi khám bệnh của bà con đã giảm từ 45 phút xuống còn 15 phút. Cảm ơn đội ngũ hỗ trợ kỹ thuật đã nhiệt tình hướng dẫn các y bác sĩ ngay cả vào dịp cuối tuần...'\n\nĐọc những dòng này, bao nhiêu áp lực trực tổng đài dường như tan biến hết. Tự hào về sản phẩm và giá trị thiết thực mà chúng ta đang mang lại cho xã hội!",
                'image' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'customer_gratitude_letter.jpg',
                'published_at' => now()->subDays(11),
                'comments' => [
                    ['user' => $users['ceo'], 'content' => 'Đây chính là lý do chúng ta xây dựng Openhello mỗi ngày. Cảm ơn Thảo và đội ngũ CSKH rất nhiều!'],
                    ['user' => $users['coo'], 'content' => 'Một câu chuyện truyền cảm hứng tuyệt vời. Hãy giữ vững tinh thần tận tâm này nhé.'],
                ],
                'reactions' => [$users['ceo'], $users['coo'], $users['sales_manager'], $users['hr_manager'], $users['product_manager']],
            ],

            // Post 15: Lý Minh Khôi - Check-in ban công tầng 8
            [
                'id' => '60000000-0000-4000-8000-000000000115',
                'channel_id' => $channels['life-sharing']->id,
                'author_id' => $users['content_mkt'],
                'post_type' => 'update',
                'title' => 'Góc check-in mới: Vườn xương rồng và ban công ngắm hoàng hôn tại tầng 8',
                'body' => "Anh em ngồi code hoặc làm báo cáo căng thẳng quá thì nhớ bấm thang máy lên tầng 8 nhé! Đội Hành chính vừa decor lại góc ban công với dàn cây xanh mát mắt và mấy bộ bàn ghế gỗ cực chill.\n\nKhung giờ vàng từ 17h15 đến 17h45 đón hoàng hôn bao đẹp, chụp hình sống ảo góc nào cũng ra ảnh xịn sò. Anh em có ảnh check-in đẹp thả vào phần bình luận bên dưới nhé!",
                'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'office_rooftop_chill.jpg',
                'published_at' => now()->subDays(12),
                'comments' => [
                    ['user' => $users['mkt_manager'], 'content' => 'Chiều nay team MKT vừa lên đây brainstorm chiến dịch Tết, view hoàng hôn đỉnh thật sự.'],
                    ['user' => $users['recruiter'], 'content' => 'Ứng viên đến phỏng vấn khen văn phòng mình có view đẹp nhất khu công nghệ luôn đó Khôi ơi!'],
                ],
                'reactions' => [$users['mkt_manager'], $users['recruiter'], $users['cnb'], $users['cskh_lead'], $users['accountant']],
            ],

            // Post 16: Nguyễn Minh Anh - Tự động hóa QA
            [
                'id' => '60000000-0000-4000-8000-000000000116',
                'channel_id' => $channels['tech-architecture']->id,
                'author_id' => $users['qa_expert'],
                'post_type' => 'update',
                'title' => 'Tự động hóa End-to-End Testing: Giảm 85% thời gian regression test trước mỗi đợt Release',
                'body' => "Đội ngũ QA vừa hoàn thành việc tích hợp bộ kiểm thử tự động Playwright & Cypress vào luồng GitHub Actions CI:\n\n- Tự động chạy song song 32 luồng cho 600 kịch bản người dùng quan trọng\n- Thời gian chạy toàn bộ test suite rút ngắn từ 4 giờ làm thủ công xuống còn đúng 8 phút\n- Tự động chụp ảnh màn hình và quay video khi phát hiện regression bug\n\nChất lượng sản phẩm là trách nhiệm chung của toàn đội ngũ, nhưng có công cụ tự động hóa chuẩn chỉ thì anh em Dev sẽ yên tâm ngủ ngon hơn rất nhiều!",
                'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'qa_automation_pipeline.jpg',
                'published_at' => now()->subDays(13),
                'comments' => [
                    ['user' => $users['tech_lead'], 'content' => 'Tốc độ CI nhanh hơn hẳn, dev push code lên 10 phút sau là có kết quả test ngay. Quá đỉnh!'],
                    ['user' => $users['software_eng'], 'content' => 'Cảm ơn chị Minh Anh và team QA đã build bộ automation test quá xịn sò.'],
                ],
                'reactions' => [$users['tech_lead'], $users['software_eng'], $users['devops'], $users['product_manager']],
            ],

            // Post 17: Trần Minh Quân - Chia sẻ sách cuối tuần
            [
                'id' => '60000000-0000-4000-8000-000000000117',
                'channel_id' => $channels['life-sharing']->id,
                'author_id' => $users['ceo'],
                'post_type' => 'update',
                'title' => 'Cuốn sách cuối tuần: "Sáng tạo là cuộc chơi tập thể" - Ed Catmull (Nhà sáng lập Pixar)',
                'body' => "Một trong những cuốn sách tôi tâm đắc nhất về văn hóa sáng tạo và quản trị con người. Ed Catmull chia sẻ: 'Nếu bạn đưa một ý tưởng hay cho một nhóm tầm thường, họ sẽ phá hỏng nó. Nhưng nếu bạn đưa một ý tưởng bình thường cho một đội ngũ xuất sắc, họ sẽ sửa chữa nó hoặc nghĩ ra điều gì đó tốt hơn nhiều.'\n\nNền tảng của một tổ chức vững mạnh không nằm ở việc người lãnh đạo có câu trả lời cho mọi thứ, mà là tạo ra môi trường an toàn để mọi người sẵn sàng nói ra sự thật và cùng nhau thử nghiệm những cách làm mới.\n\nCuốn sách hiện có tại Tủ sách mở tầng 5, mời anh em cùng đọc và suy ngẫm!",
                'image' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'creativity_inc_book.jpg',
                'published_at' => now()->subDays(14),
                'comments' => [
                    ['user' => $users['coo'], 'content' => 'Cuốn sách rất hay về việc trao quyền và bảo vệ ý tưởng mới khi chúng còn non nớt.'],
                    ['user' => $users['mkt_manager'], 'content' => 'Em vừa mượn cuốn này đọc cuối tuần trước, chương về "Braintrust" của Pixar áp dụng vào họp brainstorming cực kỳ hiệu quả ạ.'],
                ],
                'reactions' => [$users['coo'], $users['mkt_manager'], $users['tech_lead'], $users['hr_manager'], $users['product_manager']],
            ],

            // Post 18: Kèo lẩu thứ Sáu (Phạm Phát)
            [
                'id' => '60000000-0000-4000-8000-000000000118',
                'channel_id' => $channels['life-sharing']->id,
                'author_id' => $users['software_eng'],
                'post_type' => 'update',
                'title' => 'Kèo lẩu cá hồi chiều thứ Sáu của anh em dev sau một tuần chạy sprint toát mồ hôi!',
                'body' => "Fix xong con bug production cuối cùng lúc 17h30, cả team quyết định kéo nhau ra quán lẩu quen thuộc ngay gần công ty. Vừa ăn vừa bàn luận từ chuyện kiến trúc vi dịch vụ đến chuyện đá bóng cuối tuần.\n\nĂn no nê lấy lại 200% năng lượng để sang tuần sau chiến đấu tiếp! Anh em team khác có muốn giao lưu lẩu nướng thì nhắn team Kỹ thuật xếp kèo nhé!",
                'image' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'hotpot_friday_dinner.jpg',
                'published_at' => now()->subDays(15),
                'comments' => [
                    ['user' => $users['devops'], 'content' => 'Đĩa thịt bò của anh em nhìn uy tín quá, tuần sau cho em tham gia với nhé! 🥩'],
                    ['user' => $users['qa_expert'], 'content' => 'Ăn xong nhớ về ngủ sớm, thứ Hai tuần sau bắt đầu regression test bản release mới đó nha.'],
                ],
                'reactions' => [$users['devops'], $users['qa_expert'], $users['tech_lead'], $users['accountant'], $users['sales_b2b']],
            ],

            // Post 19: Tuyển thủ cầu lông (Hồ Trọng Nghĩa)
            [
                'id' => '60000000-0000-4000-8000-000000000119',
                'channel_id' => $channels['life-sharing']->id,
                'author_id' => $users['devops'],
                'post_type' => 'update',
                'title' => 'CLB Cầu lông thứ Ba & thứ Năm: Tuyển thêm các tay vợt phong trào!',
                'body' => "CLB Cầu lông công ty sinh hoạt đều đặn 18h00 - 20h00 các buổi tối thứ Ba và thứ Năm hàng tuần tại sân Viettel (cách văn phòng 500m).\n\nTrình độ từ cầm vợt lần đầu đến 'chém cầu' cháy sân đều được hoan nghênh. Đã có sẵn cầu thi đấu và nước uống, anh chị em chỉ cần mang giày thể thao và tinh thần xả stress sau giờ làm. Ai tham gia thả like hoặc comment bên dưới để mình add vào nhóm Zalo nhé!",
                'image' => 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'badminton_club_sessions.jpg',
                'published_at' => now()->subDays(16),
                'comments' => [
                    ['user' => $users['accountant'], 'content' => 'Cho em xin một chân đánh đơn thứ Ba tuần tới anh Nghĩa ơi 🏸'],
                    ['user' => $users['sales_b2b'], 'content' => 'Mình cũng tham gia! Hôm nào giao lưu đôi nam nhé.'],
                ],
                'reactions' => [$users['accountant'], $users['sales_b2b'], $users['cskh_lead'], $users['recruiter']],
            ],

            // Post 20: Tiệc Tất Niên Year-End Party (Trần Minh Quân)
            [
                'id' => '60000000-0000-4000-8000-000000000120',
                'channel_id' => $channels['general']->id,
                'author_id' => $users['ceo'],
                'post_type' => 'event',
                'title' => 'Lịch trình All-Hands Meeting & Tiệc Tất niên Year-End Party 2026',
                'body' => "Thông báo lịch trình sự kiện lớn nhất trong năm của toàn thể công ty:\n\n📅 Ngày tổ chức: 15/01/2027\n📍 Địa điểm: Trung tâm Hội nghị Quốc tế White Palace\n\nNội dung chính:\n- 14h00 - 17h00: Tổng kết hoạt động năm 2026 & Công bố định hướng chiến lược 2027\n- 18h00 - 21h30: Dạ tiệc Gala Dinner, Vinh danh các tập thể xuất sắc và chương trình Lucky Draw với giải đặc biệt là xe máy điện VinFast!\n\nDresscode: Sang trọng / Tone màu Xanh dương & Trắng bạc. Chi tiết đăng ký vé xe và tiết mục văn nghệ sẽ được gửi trong tuần tới.",
                'image' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=1200&auto=format&fit=crop&q=80',
                'filename' => 'year_end_party_gala.jpg',
                'published_at' => now()->subDays(17),
                'event' => [
                    'starts_at' => now()->addDays(45)->setTime(14, 0),
                    'ends_at' => now()->addDays(45)->setTime(21, 30),
                    'location' => 'Trung tâm Hội nghị Quốc tế White Palace, TP.HCM',
                ],
                'comments' => [
                    ['user' => $users['coo'], 'content' => 'Toàn bộ công tác hậu cần và kịch bản chương trình đang được hoàn tất. Hứa hẹn một đêm Gala đáng nhớ!'],
                    ['user' => $users['hr_manager'], 'content' => 'Các team bắt đầu tập dượt văn nghệ từ tuần này đi nha, giải Tiết mục Xuất sắc nhất trị giá 10 triệu đồng đó! 🎤💃'],
                ],
                'reactions' => [$users['coo'], $users['hr_manager'], $users['sales_manager'], $users['tech_lead'], $users['mkt_manager'], $users['chief_account'], $users['software_eng']],
            ],
        ];

        // 4. Lưu dữ liệu Post, PostStat, PostAttachment, PostComment, PostReaction, PostPin
        foreach ($postsData as $pData) {
            $post = Post::query()->updateOrCreate(
                ['id' => $pData['id']],
                [
                    'organization_id' => $organizationId,
                    'channel_id' => $pData['channel_id'],
                    'author_user_id' => $pData['author_id'],
                    'post_type' => $pData['post_type'],
                    'title' => $pData['title'],
                    'body_markdown' => $pData['body'],
                    'body_plaintext' => $pData['body'],
                    'visibility' => 'organization',
                    'publication_status' => 'published',
                    'moderation_status' => 'clear',
                    'published_at' => $pData['published_at'],
                    'created_by_user_id' => $pData['author_id'],
                    'created_at' => $pData['published_at'],
                    'updated_at' => $pData['published_at'],
                ]
            );

            // Stats
            PostStat::query()->updateOrCreate(
                ['post_id' => $post->id],
                [
                    'view_count' => rand(150, 680),
                    'reaction_count' => count($pData['reactions'] ?? []),
                    'comment_count' => count($pData['comments'] ?? []),
                    'share_count' => rand(2, 25),
                    'save_count' => rand(1, 10),
                    'last_interaction_at' => $pData['published_at'],
                    'updated_at' => now(),
                ]
            );

            // Image Attachment
            if (!empty($pData['image'])) {
                $attachId = '7000' . substr($post->id, 4);
                DB::table('post_attachments')->updateOrInsert(
                    ['id' => $attachId],
                    [
                        'post_id' => $post->id,
                        'attachment_type' => 'image',
                        'object_key' => $pData['image'],
                        'original_filename' => $pData['filename'] ?? 'image.jpg',
                        'mime_type' => 'image/jpeg',
                        'size_bytes' => 1024 * rand(400, 1500),
                        'sha256' => hash('sha256', $pData['image']),
                        'scan_status' => 'clean',
                        'sort_order' => 0,
                        'created_at' => $pData['published_at'],
                        'updated_at' => $pData['published_at'],
                    ]
                );
            }

            // Event if present
            if (!empty($pData['event'])) {
                PostEvent::query()->updateOrCreate(
                    ['post_id' => $post->id],
                    [
                        'starts_at' => $pData['event']['starts_at'],
                        'ends_at' => $pData['event']['ends_at'],
                        'location_text' => $pData['event']['location'],
                    ]
                );
            }

            // Pin if requested
            if (!empty($pData['pinned'])) {
                $pinId = '7100' . substr($post->id, 4);
                DB::table('post_pins')->updateOrInsert(
                    ['post_id' => $post->id],
                    [
                        'id' => $pinId,
                        'organization_id' => $organizationId,
                        'channel_id' => $pData['channel_id'],
                        'pinned_by_user_id' => $pData['author_id'],
                        'pinned_at' => $pData['published_at'],
                        'expires_at' => null,
                    ]
                );
            }

            // Reactions
            if (!empty($pData['reactions'])) {
                foreach ($pData['reactions'] as $reactUserId) {
                    $reactId = (string) Str::uuid();
                    DB::table('post_reactions')->updateOrInsert(
                        ['post_id' => $post->id, 'user_id' => $reactUserId],
                        [
                            'id' => $reactId,
                            'reaction_type' => 'like',
                            'created_at' => $pData['published_at'],
                            'updated_at' => $pData['published_at'],
                        ]
                    );
                }
            }

            // Comments
            if (!empty($pData['comments'])) {
                foreach ($pData['comments'] as $cIdx => $c) {
                    $existing = DB::table('post_comments')
                        ->where('post_id', $post->id)
                        ->where('author_user_id', $c['user'])
                        ->where('body_plaintext', $c['content'])
                        ->first();
                    $commentId = $existing ? $existing->id : (string) Str::uuid();
                    DB::table('post_comments')->updateOrInsert(
                        ['id' => $commentId],
                        [
                            'organization_id' => $organizationId,
                            'post_id' => $post->id,
                            'parent_comment_id' => null,
                            'author_user_id' => $c['user'],
                            'body_markdown' => $c['content'],
                            'body_plaintext' => $c['content'],
                            'status' => 'published',
                            'moderation_status' => 'clear',
                            'created_at' => $pData['published_at']->copy()->addMinutes(($cIdx + 1) * 15),
                            'updated_at' => $pData['published_at']->copy()->addMinutes(($cIdx + 1) * 15),
                        ]
                    );
                }
            }
        }

        // Tags
        SocialTag::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'normalized_name' => 'welcome'],
            ['display_name' => 'Welcome', 'status' => 'active', 'created_by_user_id' => $users['admin']]
        );
        SocialTag::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'normalized_name' => 'engineering'],
            ['display_name' => 'Engineering', 'status' => 'active', 'created_by_user_id' => $users['tech_lead']]
        );
        SocialTag::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'normalized_name' => 'culture'],
            ['display_name' => 'Culture', 'status' => 'active', 'created_by_user_id' => $users['hr_manager']]
        );
    }
}
