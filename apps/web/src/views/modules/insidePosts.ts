import { ref } from 'vue'

export const insidePosts = ref<any[]>([
  {
    id: 'company-schedule',
    author: 'Ban Điều hành',
    role: 'Thông báo công ty',
    channel: 'Toàn công ty',
    time: 'Lịch trình sắp tới',
    avatar: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=80&auto=format&fit=crop&q=80',
    title: 'Lịch trình sắp tới của công ty',
    content: '📅 Thứ Hai: Họp cập nhật tiến độ các nhóm.\n💡 Thứ Tư: Chia sẻ kiến thức nội bộ.\n🤝 Thứ Sáu: Tổng kết tuần và chuẩn bị kế hoạch tiếp theo.',
    pinned: true,
    likes: 24,
    commentsCount: 1,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-sched-1',
        author: 'Lê Thị Mai Phương',
        role: 'Quản lý nhân sự & Văn hóa',
        avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
        content: 'Đã chuẩn bị quà welcome và teabreak cho các bạn tân binh vào thứ Sáu này rồi ạ!',
        time: 'Hôm nay'
      }
    ]
  },
  {
    id: 'post-seed-101',
    author: 'Trần Minh Quân',
    role: 'Giám đốc Điều hành (CEO)',
    channel: 'Thông báo chung',
    time: '2 giờ trước',
    avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
    title: 'Thư gửi toàn thể đội ngũ: Định hướng phát triển Quý 4/2026 & Cột mốc 100,000 người dùng',
    content: 'Chào toàn thể anh chị em trong đại gia đình công ty!\n\nNhìn lại chặng đường 9 tháng vừa qua, tôi vô cùng tự hào về những nỗ lực phi thường của từng khối phòng ban. Từ những ngày đầu xây dựng nền tảng cho đến hôm nay, hệ thống của chúng ta đã chính thức cán mốc 100,000 active users.\n\nTrong Quý 4 sắp tới, mục tiêu chiến lược của chúng ta là tập trung tối ưu hóa trải nghiệm sản phẩm, mở rộng phân khúc khách hàng doanh nghiệp (Enterprise) và tích hợp sâu AI Knowledge Graph vào vận hành hàng ngày. Thành công này thuộc về sự tận tâm của từng cá nhân trong tập thể.\n\nCảm ơn mọi người và chúc chúng ta một quý mới bùng nổ hơn nữa!',
    postType: 'announcement',
    likes: 86,
    commentsCount: 3,
    isLiked: false,
    pinned: true,
    image: 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-101-1',
        author: 'Lê Thu Hà',
        role: 'Giám đốc Vận hành (COO)',
        avatar: 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80',
        content: 'Một cột mốc rất ý nghĩa! Toàn khối Vận hành đã sẵn sàng cho các kế hoạch mở rộng trong Quý 4.',
        time: '1 giờ trước'
      },
      {
        id: 'c-101-2',
        author: 'Võ Thành Luân',
        role: 'Trưởng phòng Kỹ thuật (Head of Engineering)',
        avatar: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
        content: 'Khối Kỹ thuật cam kết giữ vững hạ tầng ổn định, tốc độ và bảo mật cao nhất để phục vụ mục tiêu 100k+ người dùng.',
        time: '45 phút trước'
      },
      {
        id: 'c-101-3',
        author: 'Hoàng Gia Bảo',
        role: 'Trưởng phòng Kinh doanh (Head of Sales)',
        avatar: 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150&auto=format&fit=crop&q=80',
        content: 'Chúc mừng công ty! Khối Kinh doanh đang tăng tốc chốt thêm các hợp đồng Enterprise lớn trong tháng này.',
        time: '30 phút trước'
      }
    ]
  },
  {
    id: 'post-seed-102',
    author: 'Trần Minh Quân',
    role: 'Giám đốc Điều hành (CEO)',
    channel: 'Thông báo chung',
    time: '8 giờ trước',
    avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
    title: 'Lịch trình sự kiện & All-Hands Meeting tháng 11/2026',
    content: '📅 Thứ Hai: Họp cập nhật tiến độ liên phòng ban (All-Hands Sync)\n💡 Thứ Tư: Tech Talk chuyên đề AI & Knowledge Graph tại phòng Hội thảo tầng 6\n🤝 Thứ Sáu: Happy Hour & Chào đón thành viên mới gia nhập công ty',
    postType: 'event',
    likes: 42,
    commentsCount: 1,
    isLiked: true,
    pinned: true,
    image: 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-102-1',
        author: 'Phạm Ngọc Ánh',
        role: 'Chuyên viên Tuyển dụng & Đào tạo',
        avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
        content: 'Đã chuẩn bị quà welcome và teabreak cho các bạn tân binh vào thứ Sáu này rồi ạ!',
        time: '6 giờ trước'
      }
    ]
  },
  {
    id: 'post-seed-103',
    author: 'Võ Thành Luân',
    role: 'Trưởng phòng Kỹ thuật (Head of Engineering)',
    channel: 'Tech & Architecture',
    time: '14 giờ trước',
    avatar: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
    title: 'Kiến trúc Event-Driven Microservices với Transactional Outbox Pattern đã hoàn thiện',
    content: 'Chia sẻ cùng anh em khối Kỹ thuật: Nhóm Backend vừa hoàn tất việc tích hợp Transactional Outbox Pattern kết hợp RabbitMQ và Redis Streams trên toàn bộ các services (Identity, Social, Chat, Notification, Learning).\n\nLợi ích đạt được:\n1. Giải quyết triệt để bài toán Dual-Write và bảo toàn tính toàn vẹn dữ liệu giữa MySQL và Message Broker\n2. Độ trễ lan truyền sự kiện (End-to-End latency) giảm xuống dưới 45ms\n3. Hỗ trợ Idempotency key bảo vệ hệ thống trước tình huống mạng chập chờn hoặc retry requests.\n\nAnh em có thể tham khảo RFC chi tiết trong kho tài liệu kỹ thuật nhé!',
    postType: 'update',
    likes: 58,
    commentsCount: 3,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-103-1',
        author: 'Phạm Phát',
        role: 'Kỹ sư phần mềm cao cấp',
        avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
        content: 'Kiến trúc chạy rất mượt anh ạ. Đã test với kịch bản 1,000 messages/giây mà CPU MySQL chỉ ở mức 15%.',
        time: '12 giờ trước'
      },
      {
        id: 'c-103-2',
        author: 'Hồ Trọng Nghĩa',
        role: 'Chuyên viên DevOps & IT Helpdesk',
        avatar: 'https://images.unsplash.com/photo-1628157582853-a796fa650a6a?w=150&auto=format&fit=crop&q=80',
        content: 'Dashboard Grafana theo dõi Outbox worker đã được cấu hình alert qua Slack rồi nhé anh em.',
        time: '10 giờ trước'
      },
      {
        id: 'c-103-3',
        author: 'Nguyễn Minh Anh',
        role: 'Chuyên gia QA & Tri thức',
        avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80',
        content: 'Test suite idempotency check pass 100%. Rất yên tâm cho đợt release lớn sắp tới!',
        time: '8 giờ trước'
      }
    ]
  },
  {
    id: 'post-seed-104',
    author: 'Võ Thành Luân',
    role: 'Trưởng phòng Kỹ thuật (Head of Engineering)',
    channel: 'AI & Knowledge Graph',
    time: 'Hôm qua',
    avatar: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
    title: 'Thực nghiệm Graph RAG với Neo4j: Cải thiện 38% độ chính xác cho câu hỏi nghiệp vụ đa bước',
    content: 'So sánh thực tế giữa Vector Search truyền thống (Naive RAG) và Graph RAG trên bộ 1,200 câu hỏi quy chế nội bộ:\n\n- Khi câu hỏi yêu cầu liên kết nhiều thực thể (ví dụ: "Quy trình nghỉ phép của nhân sự thử việc tại chi nhánh miền Nam do ai duyệt?"), Graph RAG giúp mô hình LLM lần theo đồ thị quan hệ chính xác 98.4%, trong khi Vector Search thông thường chỉ đạt 62% do mất ngữ cảnh phân cấp.\n\nHôm nay chúng tôi đã deploy bản beta tại Square AI. Mời mọi người vào test và cho feedback nhé!',
    postType: 'update',
    likes: 64,
    commentsCount: 2,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-104-1',
        author: 'Trần Đức Thắng',
        role: 'Trưởng nhóm sản phẩm',
        avatar: 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
        content: 'Đã thử đặt các câu hỏi về phân quyền scoped RBAC, bot trả lời rất rõ ràng và trích dẫn chuẩn node điều lệ.',
        time: 'Hôm qua'
      },
      {
        id: 'c-104-2',
        author: 'Lê Thị Mai Phương',
        role: 'Quản lý nhân sự & Văn hóa',
        avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
        content: 'Tính năng này sẽ giúp HR giảm tải rất nhiều câu hỏi lặp đi lặp lại từ nhân sự mới!',
        time: 'Hôm qua'
      }
    ]
  },
  {
    id: 'post-seed-105',
    author: 'Lê Thị Mai Phương',
    role: 'Quản lý nhân sự & Văn hóa',
    channel: 'HR & Văn hóa Doanh nghiệp',
    time: '2 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
    title: 'Chào đón 4 thành viên mới gia nhập đại gia đình Openhello trong tuần này!',
    content: 'HR xin gửi lời chào nồng nhiệt nhất tới 4 tân binh gia nhập tuần này:\n1. Nguyễn Hoàng Long - Frontend Engineer (ENG-FE)\n2. Trần Bảo Trâm - UI/UX Designer (ENG-PROD)\n3. Đỗ Quốc Tuấn - B2B Sales Executive (SALES-B2B)\n4. Hoàng Bích Ngọc - Chuyên viên Truyền thông Nội bộ (MKT-CONTENT)\n\nChúc các bạn có một hành trình thật nhiều niềm vui, trải nghiệm thú vị và bứt phá sự nghiệp tại công ty. Anh em các team nhớ dắt tân binh đi làm quen và ăn trưa nhé!',
    postType: 'news',
    likes: 72,
    commentsCount: 3,
    isLiked: true,
    image: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-105-1',
        author: 'Phạm Ngọc Ánh',
        role: 'Chuyên viên Tuyển dụng & Đào tạo',
        avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
        content: 'Chào mừng các bạn! Chúc mọi người onboarding thật suôn sẻ.',
        time: '2 ngày trước'
      },
      {
        id: 'c-105-2',
        author: 'Phan Tuấn Kiệt',
        role: 'Quản lý Sản phẩm (Product Manager)',
        avatar: 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
        content: 'Chào bạn Trâm gia nhập team Design nhé, nhiều wireframe đang chờ bạn cùng sáng tạo! 🎉',
        time: '2 ngày trước'
      },
      {
        id: 'c-105-3',
        author: 'Hoàng Gia Bảo',
        role: 'Trưởng phòng Kinh doanh (Head of Sales)',
        avatar: 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150&auto=format&fit=crop&q=80',
        content: 'Chào đón Tuấn về đội Kinh doanh, cùng nhau chinh phục mục tiêu tháng 11 nào!',
        time: '2 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-106',
    author: 'Hoàng Gia Bảo',
    role: 'Trưởng phòng Kinh doanh (Head of Sales)',
    channel: 'Kinh doanh & Khách hàng',
    time: '3 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150&auto=format&fit=crop&q=80',
    title: 'Chính thức ký kết hợp đồng Enterprise 2.5 tỷ VNĐ với Tập đoàn Vạn An Phát!',
    content: 'Tin vui đầu tuần cho toàn công ty!\n\nSau 3 tháng thương thảo, pitching giải pháp và demo tích hợp thực tế, đội ngũ B2B Sales đã chính thức đặt bút ký kết hợp đồng cung cấp nền tảng quản trị công việc toàn diện cho Tập đoàn Vạn An Phát với quy mô hơn 1,500 nhân sự.\n\nCảm ơn đội ngũ Kỹ thuật đã hỗ trợ thức đêm làm PoC giải pháp, cảm ơn CSKH và Pháp chế đã đồng hành xuyên suốt. Chiến thắng này là minh chứng rõ nét cho chất lượng sản phẩm của chúng ta!',
    postType: 'update',
    likes: 89,
    commentsCount: 3,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-106-1',
        author: 'Trần Minh Quân',
        role: 'Giám đốc Điều hành (CEO)',
        avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
        content: 'Xuất sắc lắm team Sales! Một thương vụ khẳng định vị thế thương hiệu của chúng ta trên thị trường.',
        time: '3 ngày trước'
      },
      {
        id: 'c-106-2',
        author: 'Đặng Quốc Khánh',
        role: 'Chuyên viên Kinh doanh B2B (Account Executive)',
        avatar: 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?w=150&auto=format&fit=crop&q=80',
        content: 'Cảm ơn anh Bảo đã dẫn dắt deal này. Team B2B đang tiếp tục chăm sóc 3 deal lớn nữa cho tháng tới.',
        time: '3 ngày trước'
      },
      {
        id: 'c-106-3',
        author: 'Bùi Phương Thảo',
        role: 'Trưởng nhóm Chăm sóc Khách hàng (CSKH)',
        avatar: 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=150&auto=format&fit=crop&q=80',
        content: 'Team CSKH đã chuẩn bị sẵn tài liệu đào tạo và kickoff triển khai với khách hàng vào thứ Hai tuần tới!',
        time: '3 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-107',
    author: 'Phan Tuấn Kiệt',
    role: 'Quản lý Sản phẩm (Product Manager)',
    channel: 'Thông báo chung',
    time: '4 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
    title: 'Base Inside v2.4 chính thức ra mắt: Trải nghiệm bảng tin mượt mà và trực quan hơn',
    content: 'Xin chào cả nhà! Đội ngũ Sản phẩm rất vui mừng thông báo phiên bản Base Inside v2.4 đã sẵn sàng phục vụ mọi người với những nâng cấp đáng giá:\n\n✨ Giao diện Feed mới tải nhanh gấp 3 lần nhờ Virtual Scrolling\n📸 Hỗ trợ hiển thị ảnh độ phân giải cao và tab Media riêng cho từng kênh\n📌 Ghim thông báo quan trọng và sự kiện nổi bật ngay đầu trang\n💬 Luồng thảo luận đa cấp giúp trao đổi công việc mạch lạc hơn\n\nHãy trải nghiệm ngay và để lại ý kiến đóng góp cho team Product nhé!',
    postType: 'news',
    likes: 67,
    commentsCount: 2,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1531403009284-440f080d1e12?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-107-1',
        author: 'Võ Thành Luân',
        role: 'Trưởng phòng Kỹ thuật (Head of Engineering)',
        avatar: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
        content: 'Giao diện mượt mà và load ảnh cực nhanh. Chúc mừng team Product & Frontend!',
        time: '4 ngày trước'
      },
      {
        id: 'c-107-2',
        author: 'Lý Minh Khôi',
        role: 'Chuyên viên Content & Social Media',
        avatar: 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=150&auto=format&fit=crop&q=80',
        content: 'Bảng tin mới nhìn chuyên nghiệp và hiện đại hơn hẳn. Thích nhất tính năng xem ảnh full screen.',
        time: '4 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-108',
    author: 'Phạm Ngọc Ánh',
    role: 'Chuyên viên Tuyển dụng & Đào tạo',
    channel: 'HR & Văn hóa Doanh nghiệp',
    time: '5 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
    title: 'Khởi động giải Pickleball & Chạy bộ nội bộ "Open Cup 2026" - Đăng ký ngay hôm nay!',
    content: 'Đã đến lúc tạm gác lại bàn phím và cùng xỏ giày ra sân thôi anh chị em ơi!\n\n🏆 Giải đấu thể thao nội bộ thường niên "Open Cup 2026" chính thức mở cổng đăng ký từ hôm nay với 2 bộ môn:\n1. Pickleball đôi nam - nữ phối hợp (Sân D-Sports, thứ Bảy hàng tuần)\n2. Chạy bộ cự ly 5km - 10km (Tích lũy qua Strava)\n\nTổng giá trị giải thưởng lên đến 20,000,000 VNĐ kèm cúp vô địch danh giá! Ban Giám đốc cũng sẽ trực tiếp tham gia tranh tài. Hạn chót đăng ký: 23:59 ngày thứ Sáu tuần này.',
    postType: 'announcement',
    likes: 81,
    commentsCount: 3,
    isLiked: true,
    image: 'https://images.unsplash.com/photo-1526676037777-05a232554f77?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-108-1',
        author: 'Trần Minh Quân',
        role: 'Giám đốc Điều hành (CEO)',
        avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
        content: 'Tôi đã đăng ký một slot đánh đôi Pickleball rồi nhé. Thách đấu anh em team Kỹ thuật! 😎',
        time: '5 ngày trước'
      },
      {
        id: 'c-108-2',
        author: 'Hồ Trọng Nghĩa',
        role: 'Chuyên viên DevOps & IT Helpdesk',
        avatar: 'https://images.unsplash.com/photo-1628157582853-a796fa650a6a?w=150&auto=format&fit=crop&q=80',
        content: 'Team DevOps nhận kèo anh Quân ơi, thứ Bảy ra sân phân định thắng thua ạ! 🔥',
        time: '5 ngày trước'
      },
      {
        id: 'c-108-3',
        author: 'Ngô Thanh Tùng',
        role: 'Kế toán tổng hợp & Công nợ',
        avatar: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&auto=format&fit=crop&q=80',
        content: 'Em đăng ký giải chạy 10km, quyết tâm giật giải nhất mang tiền thưởng về khao cả phòng!',
        time: '5 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-109',
    author: 'Trịnh Khánh Linh',
    role: 'Trưởng phòng Marketing (Marketing Manager)',
    channel: 'Thông báo chung',
    time: '6 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1573496799652-408c2ac9fe98?w=150&auto=format&fit=crop&q=80',
    title: 'Recap triển lãm Vietnam Tech Summit 2026: Gian hàng công ty thu hút hơn 3,000 lượt khách tham quan',
    content: 'Cuối tuần qua, gian hàng công ty tại triển lãm Vietnam Tech Summit 2026 đã diễn ra thành công rực rỡ ngoài mong đợi!\n\n- Thu thập hơn 450 qualified leads doanh nghiệp tiềm năng\n- 8 cơ quan báo chí và truyền hình đưa tin về giải pháp AI Knowledge Graph\n- Hơn 1,000 phần quà lưu niệm đã được gửi tới khách tham quan\n\nCảm ơn tất cả các tình nguyện viên và nhân sự hỗ trợ đã cháy hết mình trong suốt 3 ngày sự kiện!',
    postType: 'news',
    likes: 62,
    commentsCount: 2,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-109-1',
        author: 'Lý Minh Khôi',
        role: 'Chuyên viên Content & Social Media',
        avatar: 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=150&auto=format&fit=crop&q=80',
        content: 'Video recap và phỏng vấn khách hàng đã render xong, chuẩn bị lên sóng fanpage ạ!',
        time: '6 ngày trước'
      },
      {
        id: 'c-109-2',
        author: 'Hoàng Gia Bảo',
        role: 'Trưởng phòng Kinh doanh (Head of Sales)',
        avatar: 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150&auto=format&fit=crop&q=80',
        content: 'Đã phân bổ 450 leads cho anh em Sales liên hệ tư vấn trong tuần này rồi.',
        time: '6 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-110',
    author: 'Hồ Trọng Nghĩa',
    role: 'Chuyên viên DevOps & IT Helpdesk',
    channel: 'Tech & Architecture',
    time: '7 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1628157582853-a796fa650a6a?w=150&auto=format&fit=crop&q=80',
    title: 'Cột mốc 180 ngày Uptime 99.99% - Khi các cụm Kubernetes ngủ ngon giấc!',
    content: 'Sau đợt chuyển đổi hạ tầng sang cụm Kubernetes Multi-AZ với tính năng Auto-healing và cân bằng tải thông minh, dashboard Grafana sáng nay báo tin vui: Hệ thống của chúng ta đã duy trì chuỗi 180 ngày liên tiếp không có bất kỳ sự cố P0/P1 nào!\n\nBí quyết là gì? Không bao giờ deploy lên production vào chiều thứ Sáu và luôn có sẵn trà sữa cho anh em on-call trực đêm 🧋😄\n\nCảm ơn anh em Dev đã tuân thủ nghiêm ngặt quy trình CI/CD và viết unit test đầy đủ!',
    postType: 'update',
    likes: 74,
    commentsCount: 2,
    isLiked: true,
    image: 'https://images.unsplash.com/photo-1504639725590-34d0984388bd?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-110-1',
        author: 'Võ Thành Luân',
        role: 'Trưởng phòng Kỹ thuật (Head of Engineering)',
        avatar: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
        content: 'Xứng đáng thưởng nóng cho anh em DevOps! Giữ vững phong độ nhé Nghĩa ơi.',
        time: '7 ngày trước'
      },
      {
        id: 'c-110-2',
        author: 'Phạm Phát',
        role: 'Kỹ sư phần mềm cao cấp',
        avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
        content: 'Tối nay trà sữa full topping khao anh em thôi anh Nghĩa ơi! 🥤',
        time: '7 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-111',
    author: 'Phạm Phát',
    role: 'Kỹ sư phần mềm cao cấp',
    channel: 'Tech & Architecture',
    time: '8 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
    title: '5 mẹo tối ưu hiệu năng Vue 3 & Vite giúp bundle size giảm 40%',
    content: 'Trong quá trình tối ưu lại frontend Base Web, mình rút ra 5 kinh nghiệm thực tế rất đáng lưu ý:\n\n1. Async Component kết hợp defineAsyncComponent cho các modal và drawer nặng\n2. Tận dụng triệt để Tree-shaking từ thư viện Lucide & Lodash-es\n3. Sử dụng Virtualizer cho danh sách bài viết dài để DOM không bị phình to\n4. Nén ảnh sang định dạng WebP trước khi đưa vào bundle\n5. Tối ưu CSS qua Tailwind JIT compilation.\n\nKết quả thời gian First Contentful Paint (FCP) giảm từ 1.8s xuống chỉ còn 0.4s trên kết nối 4G!',
    postType: 'update',
    likes: 53,
    commentsCount: 2,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-111-1',
        author: 'Võ Thành Luân',
        role: 'Trưởng phòng Kỹ thuật (Head of Engineering)',
        avatar: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
        content: 'Bài viết rất chất lượng. Anh Phát document lại vào wiki kỹ thuật cho các bạn mới nhé.',
        time: '8 ngày trước'
      },
      {
        id: 'c-111-2',
        author: 'Phan Tuấn Kiệt',
        role: 'Quản lý Sản phẩm (Product Manager)',
        avatar: 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
        content: 'Người dùng phản hồi web load nhanh hơn hẳn, mượt như app native vậy.',
        time: '8 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-112',
    author: 'Đỗ Hoàng Yến',
    role: 'Chuyên viên C&B và Hành chính',
    channel: 'HR & Văn hóa Doanh nghiệp',
    time: '9 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=150&auto=format&fit=crop&q=80',
    title: 'Thông báo lịch khám sức khỏe định kỳ năm 2026 tại Bệnh viện Đa khoa Quốc tế',
    content: 'Phòng Nhân sự & C&B xin thông báo tới toàn thể cán bộ nhân viên về chương trình Khám sức khỏe định kỳ hàng năm:\n\n- Thời gian: Ngày 25/11 và 26/11/2026 (Thứ Tư & Thứ Năm)\n- Địa điểm: Bệnh viện Đa khoa Quốc tế Vinmec Central Park\n- Gói khám bao gồm: Khám tổng quát, xét nghiệm máu chuyên sâu, chụp X-quang, tầm soát sức khỏe mắt và cơ xương khớp nghề nghiệp văn phòng.\n\nMọi người vui lòng chọn khung giờ khám phù hợp trên hệ thống trước 17h00 ngày 20/11 để HR sắp xếp xe đưa đón chu đáo nhé!',
    postType: 'announcement',
    likes: 49,
    commentsCount: 1,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-112-1',
        author: 'Vũ Thị Mai Hương',
        role: 'Kế toán trưởng',
        avatar: 'https://images.unsplash.com/photo-1567532939604-b6b5b0db2604?w=150&auto=format&fit=crop&q=80',
        content: 'Đã duyệt gói ngân sách khám sức khỏe nâng cao cho toàn bộ CBNV. Mọi người chú ý đi khám đầy đủ nhé.',
        time: '9 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-113',
    author: 'Ngô Thanh Tùng',
    role: 'Kế toán tổng hợp & Công nợ',
    channel: 'Góc đời sống & Chia sẻ',
    time: '10 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&auto=format&fit=crop&q=80',
    title: 'Tinh tinh! Lương và thưởng dự án tháng này đã về tài khoản rồi nhé cả nhà!',
    content: 'Thông báo ngọt ngào nhất ngày 15 hàng tháng đã được Phòng Kế toán gửi đi thành công tới 100% tài khoản ngân hàng của anh chị em rồi nhé! 💸🎉\n\nTháng này có thêm khoản thưởng nóng cho chiến dịch cán mốc 100k users. Mọi người kiểm tra email nhận phiếu lương (payslip) chi tiết. Chúc anh chị em cuối tuần chi tiêu hợp lý và nhớ để dành quỹ rủ đồng đội đi ăn lẩu nhé!',
    postType: 'update',
    likes: 95,
    commentsCount: 2,
    isLiked: true,
    image: 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-113-1',
        author: 'Phạm Phát',
        role: 'Kỹ sư phần mềm cao cấp',
        avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
        content: 'Cảm ơn anh Tùng và phòng Kế toán! Chiều nay kèo lẩu cá hồi thẳng tiến thôi anh em ơi 🍲',
        time: '10 ngày trước'
      },
      {
        id: 'c-113-2',
        author: 'Đặng Quốc Khánh',
        role: 'Chuyên viên Kinh doanh B2B (Account Executive)',
        avatar: 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?w=150&auto=format&fit=crop&q=80',
        content: 'Lương về đúng ngày thứ Sáu, đẹp như một giấc mơ! Cảm ơn công ty nhiều ạ.',
        time: '10 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-114',
    author: 'Bùi Phương Thảo',
    role: 'Trưởng nhóm Chăm sóc Khách hàng (CSKH)',
    channel: 'Kinh doanh & Khách hàng',
    time: '11 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=150&auto=format&fit=crop&q=80',
    title: 'Lời cảm ơn xúc động từ đối tác bệnh viện sau khi số hóa thành công luồng tiếp nhận bệnh nhân',
    content: 'Sáng nay, hòm thư CSKH nhận được một bức thư cảm ơn rất dài từ bác sĩ giám đốc Bệnh viện Đa khoa tỉnh:\n\n"Nhờ có giải pháp điều phối ca trực và thông báo nội bộ của các bạn, thời gian chờ đợi khám bệnh của bà con đã giảm từ 45 phút xuống còn 15 phút. Cảm ơn đội ngũ hỗ trợ kỹ thuật đã nhiệt tình hướng dẫn các y bác sĩ ngay cả vào dịp cuối tuần..."\n\nĐọc những dòng này, bao nhiêu áp lực trực tổng đài dường như tan biến hết. Tự hào về sản phẩm và giá trị thiết thực mà chúng ta đang mang lại cho xã hội!',
    postType: 'update',
    likes: 82,
    commentsCount: 2,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-114-1',
        author: 'Trần Minh Quân',
        role: 'Giám đốc Điều hành (CEO)',
        avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
        content: 'Đây chính là lý do chúng ta xây dựng Openhello mỗi ngày. Cảm ơn Thảo và đội ngũ CSKH rất nhiều!',
        time: '11 ngày trước'
      },
      {
        id: 'c-114-2',
        author: 'Lê Thu Hà',
        role: 'Giám đốc Vận hành (COO)',
        avatar: 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80',
        content: 'Một câu chuyện truyền cảm hứng tuyệt vời. Hãy giữ vững tinh thần tận tâm này nhé.',
        time: '11 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-115',
    author: 'Lý Minh Khôi',
    role: 'Chuyên viên Content & Social Media',
    channel: 'Góc đời sống & Chia sẻ',
    time: '12 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=150&auto=format&fit=crop&q=80',
    title: 'Góc check-in mới: Vườn xương rồng và ban công ngắm hoàng hôn tại tầng 8',
    content: 'Anh em ngồi code hoặc làm báo cáo căng thẳng quá thì nhớ bấm thang máy lên tầng 8 nhé! Đội Hành chính vừa decor lại góc ban công với dàn cây xanh mát mắt và mấy bộ bàn ghế gỗ cực chill.\n\nKhung giờ vàng từ 17h15 đến 17h45 đón hoàng hôn bao đẹp, chụp hình sống ảo góc nào cũng ra ảnh xịn sò. Anh em có ảnh check-in đẹp thả vào phần bình luận bên dưới nhé!',
    postType: 'update',
    likes: 68,
    commentsCount: 2,
    isLiked: true,
    image: 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-115-1',
        author: 'Trịnh Khánh Linh',
        role: 'Trưởng phòng Marketing (Marketing Manager)',
        avatar: 'https://images.unsplash.com/photo-1573496799652-408c2ac9fe98?w=150&auto=format&fit=crop&q=80',
        content: 'Chiều nay team MKT vừa lên đây brainstorm chiến dịch Tết, view hoàng hôn đỉnh thật sự.',
        time: '12 ngày trước'
      },
      {
        id: 'c-115-2',
        author: 'Phạm Ngọc Ánh',
        role: 'Chuyên viên Tuyển dụng & Đào tạo',
        avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
        content: 'Ứng viên đến phỏng vấn khen văn phòng mình có view đẹp nhất khu công nghệ luôn đó Khôi ơi!',
        time: '12 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-116',
    author: 'Nguyễn Minh Anh',
    role: 'Chuyên gia QA & Tri thức',
    channel: 'Tech & Architecture',
    time: '13 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80',
    title: 'Tự động hóa End-to-End Testing: Giảm 85% thời gian regression test trước mỗi đợt Release',
    content: 'Đội ngũ QA vừa hoàn thành việc tích hợp bộ kiểm thử tự động Playwright & Cypress vào luồng GitHub Actions CI:\n\n- Tự động chạy song song 32 luồng cho 600 kịch bản người dùng quan trọng\n- Thời gian chạy toàn bộ test suite rút ngắn từ 4 giờ làm thủ công xuống còn đúng 8 phút\n- Tự động chụp ảnh màn hình và quay video khi phát hiện regression bug\n\nChất lượng sản phẩm là trách nhiệm chung của toàn đội ngũ, nhưng có công cụ tự động hóa chuẩn chỉ thì anh em Dev sẽ yên tâm ngủ ngon hơn rất nhiều!',
    postType: 'update',
    likes: 47,
    commentsCount: 2,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-116-1',
        author: 'Võ Thành Luân',
        role: 'Trưởng phòng Kỹ thuật (Head of Engineering)',
        avatar: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
        content: 'Tốc độ CI nhanh hơn hẳn, dev push code lên 10 phút sau là có kết quả test ngay. Quá đỉnh!',
        time: '13 ngày trước'
      },
      {
        id: 'c-116-2',
        author: 'Phạm Phát',
        role: 'Kỹ sư phần mềm cao cấp',
        avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
        content: 'Cảm ơn chị Minh Anh và team QA đã build bộ automation test quá xịn sò.',
        time: '13 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-117',
    author: 'Trần Minh Quân',
    role: 'Giám đốc Điều hành (CEO)',
    channel: 'Góc đời sống & Chia sẻ',
    time: '14 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
    title: 'Cuốn sách cuối tuần: "Sáng tạo là cuộc chơi tập thể" - Ed Catmull (Nhà sáng lập Pixar)',
    content: 'Một trong những cuốn sách tôi tâm đắc nhất về văn hóa sáng tạo và quản trị con người. Ed Catmull chia sẻ: "Nếu bạn đưa một ý tưởng hay cho một nhóm tầm thường, họ sẽ phá hỏng nó. Nhưng nếu bạn đưa một ý tưởng bình thường cho một đội ngũ xuất sắc, họ sẽ sửa chữa nó hoặc nghĩ ra điều gì đó tốt hơn nhiều."\n\nNền tảng của một tổ chức vững mạnh không nằm ở việc người lãnh đạo có câu trả lời cho mọi thứ, mà là tạo ra môi trường an toàn để mọi người sẵn sàng nói ra sự thật và cùng nhau thử nghiệm những cách làm mới.\n\nCuốn sách hiện có tại Tủ sách mở tầng 5, mời anh em cùng đọc và suy ngẫm!',
    postType: 'update',
    likes: 76,
    commentsCount: 2,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-117-1',
        author: 'Lê Thu Hà',
        role: 'Giám đốc Vận hành (COO)',
        avatar: 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80',
        content: 'Cuốn sách rất hay về việc trao quyền và bảo vệ ý tưởng mới khi chúng còn non nớt.',
        time: '14 ngày trước'
      },
      {
        id: 'c-117-2',
        author: 'Trịnh Khánh Linh',
        role: 'Trưởng phòng Marketing (Marketing Manager)',
        avatar: 'https://images.unsplash.com/photo-1573496799652-408c2ac9fe98?w=150&auto=format&fit=crop&q=80',
        content: 'Em vừa mượn cuốn này đọc cuối tuần trước, chương về "Braintrust" của Pixar áp dụng vào brainstorming cực kỳ hiệu quả ạ.',
        time: '14 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-118',
    author: 'Phạm Phát',
    role: 'Kỹ sư phần mềm cao cấp',
    channel: 'Góc đời sống & Chia sẻ',
    time: '15 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
    title: 'Kèo lẩu cá hồi chiều thứ Sáu của anh em dev sau một tuần chạy sprint toát mồ hôi!',
    content: 'Fix xong con bug production cuối cùng lúc 17h30, cả team quyết định kéo nhau ra quán lẩu quen thuộc ngay gần công ty. Vừa ăn vừa bàn luận từ chuyện kiến trúc vi dịch vụ đến chuyện đá bóng cuối tuần.\n\nĂn no nê lấy lại 200% năng lượng để sang tuần sau chiến đấu tiếp! Anh em team khác có muốn giao lưu lẩu nướng thì nhắn team Kỹ thuật xếp kèo nhé!',
    postType: 'update',
    likes: 83,
    commentsCount: 2,
    isLiked: true,
    image: 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-118-1',
        author: 'Hồ Trọng Nghĩa',
        role: 'Chuyên viên DevOps & IT Helpdesk',
        avatar: 'https://images.unsplash.com/photo-1628157582853-a796fa650a6a?w=150&auto=format&fit=crop&q=80',
        content: 'Đĩa thịt bò của anh em nhìn uy tín quá, tuần sau cho em tham gia với nhé! 🥩',
        time: '15 ngày trước'
      },
      {
        id: 'c-118-2',
        author: 'Nguyễn Minh Anh',
        role: 'Chuyên gia QA & Tri thức',
        avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80',
        content: 'Ăn xong nhớ về ngủ sớm, thứ Hai tuần sau bắt đầu regression test bản release mới đó nha.',
        time: '15 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-119',
    author: 'Hồ Trọng Nghĩa',
    role: 'Chuyên viên DevOps & IT Helpdesk',
    channel: 'Góc đời sống & Chia sẻ',
    time: '16 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1628157582853-a796fa650a6a?w=150&auto=format&fit=crop&q=80',
    title: 'CLB Cầu lông thứ Ba & thứ Năm: Tuyển thêm các tay vợt phong trào!',
    content: 'CLB Cầu lông công ty sinh hoạt đều đặn 18h00 - 20h00 các buổi tối thứ Ba và thứ Năm hàng tuần tại sân Viettel (cách văn phòng 500m).\n\nTrình độ từ cầm vợt lần đầu đến "chém cầu" cháy sân đều được hoan nghênh. Đã có sẵn cầu thi đấu và nước uống, anh chị em chỉ cần mang giày thể thao và tinh thần xả stress sau giờ làm. Ai tham gia thả like hoặc comment bên dưới để mình add vào nhóm Zalo nhé!',
    postType: 'update',
    likes: 54,
    commentsCount: 2,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-119-1',
        author: 'Ngô Thanh Tùng',
        role: 'Kế toán tổng hợp & Công nợ',
        avatar: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&auto=format&fit=crop&q=80',
        content: 'Cho em xin một chân đánh đơn thứ Ba tuần tới anh Nghĩa ơi 🏸',
        time: '16 ngày trước'
      },
      {
        id: 'c-119-2',
        author: 'Đặng Quốc Khánh',
        role: 'Chuyên viên Kinh doanh B2B (Account Executive)',
        avatar: 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?w=150&auto=format&fit=crop&q=80',
        content: 'Mình cũng tham gia! Hôm nào giao lưu đôi nam nhé.',
        time: '16 ngày trước'
      }
    ]
  },
  {
    id: 'post-seed-120',
    author: 'Trần Minh Quân',
    role: 'Giám đốc Điều hành (CEO)',
    channel: 'Thông báo chung',
    time: '17 ngày trước',
    avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
    title: 'Lịch trình All-Hands Meeting & Tiệc Tất niên Year-End Party 2026',
    content: 'Thông báo lịch trình sự kiện lớn nhất trong năm của toàn thể công ty:\n\n📅 Ngày tổ chức: 15/01/2027\n📍 Địa điểm: Trung tâm Hội nghị Quốc tế White Palace\n\nNội dung chính:\n- 14h00 - 17h00: Tổng kết hoạt động năm 2026 & Công bố định hướng chiến lược 2027\n- 18h00 - 21h30: Dạ tiệc Gala Dinner, Vinh danh các tập thể xuất sắc và chương trình Lucky Draw với giải đặc biệt là xe máy điện VinFast!\n\nDresscode: Sang trọng / Tone màu Xanh dương & Trắng bạc. Chi tiết đăng ký vé xe và tiết mục văn nghệ sẽ được gửi trong tuần tới.',
    postType: 'event',
    likes: 92,
    commentsCount: 2,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=1200&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-120-1',
        author: 'Lê Thu Hà',
        role: 'Giám đốc Vận hành (COO)',
        avatar: 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80',
        content: 'Toàn bộ công tác hậu cần và kịch bản chương trình đang được hoàn tất. Hứa hẹn một đêm Gala đáng nhớ!',
        time: '17 ngày trước'
      },
      {
        id: 'c-120-2',
        author: 'Lê Thị Mai Phương',
        role: 'Quản lý nhân sự & Văn hóa',
        avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
        content: 'Các team bắt đầu tập dượt văn nghệ từ tuần này đi nha, giải Tiết mục Xuất sắc nhất trị giá 10 triệu đồng đó! 🎤💃',
        time: '17 ngày trước'
      }
    ]
  }
])

export function seedPersonalPosts(author: string) {
  if (!author || insidePosts.value.some(post => post.id === `personal-seed-1-${author}`)) return
  const avatar = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80'
  insidePosts.value.push(
    { id: `personal-seed-1-${author}`, author, role: 'Thành viên', channel: 'Tech & Architecture', time: 'Hôm qua', avatar, content: 'Vừa hoàn thành bản cập nhật cho không gian làm việc chung. Mình đã tổng hợp các ghi chú triển khai và những điểm cần lưu ý để mọi người tiện theo dõi và góp ý.', likes: 12, commentsCount: 2, isLiked: false, image: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1200&auto=format&fit=crop&q=80', comments: [] },
    { id: `personal-seed-2-${author}`, author, role: 'Thành viên', channel: 'Góc đời sống & Chia sẻ', time: '3 ngày trước', avatar, content: 'Một vài điều rút ra sau buổi chia sẻ nội bộ: tài liệu rõ ràng giúp cả nhóm trao đổi nhanh hơn, và những câu hỏi nhỏ thường mở ra cách làm tốt hơn.', likes: 18, commentsCount: 4, isLiked: false, image: 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1200&auto=format&fit=crop&q=80', comments: [] },
    { id: `personal-seed-3-${author}`, author, role: 'Thành viên', channel: 'Thông báo chung', time: 'Tuần trước', avatar, content: 'Cảm ơn mọi người đã hỗ trợ trong tuần vừa qua. Rất vui khi thấy các nhóm cùng cập nhật tiến độ và chia sẻ kinh nghiệm trên Inside!', likes: 27, commentsCount: 3, isLiked: false, image: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&auto=format&fit=crop&q=80', comments: [] }
  )
}
