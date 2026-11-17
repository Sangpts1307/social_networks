// Dữ liệu mock thuần JavaScript (JS) cho toàn bộ hệ thống Openhello.vn
// Đồng bộ 100% với các bản thiết kế trong design/exports/html-tailwind

export const MOCK_USERS = [
  {
    id: 'user-1',
    name: 'Sang Phạm',
    role: 'Lead Developer',
    email: 'sang.pham@openhello.vn',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
    department: 'Ban Công nghệ (Tech Team)'
  },
  {
    id: 'user-2',
    name: 'Võ Thành Luân',
    role: 'Senior Backend & AI Specialist',
    email: 'luan.vo@openhello.vn',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80',
    department: 'Phòng Nghiên cứu AI'
  },
  {
    id: 'user-3',
    name: 'Phạm Phát',
    role: 'Solutions Architect',
    email: 'phat.pham@openhello.vn',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
    department: 'Ban Kiến trúc Hệ thống'
  },
  {
    id: 'user-4',
    name: 'Lê Thị Mai Phương',
    role: 'HR & Văn hóa Doanh nghiệp',
    email: 'phuong.le@openhello.vn',
    avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
    department: 'Khối Nhân sự'
  },
  {
    id: 'user-5',
    name: 'Nguyễn Lê Thanh Trường',
    role: 'Operations & QA Lead',
    email: 'truong.nguyen@openhello.vn',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&auto=format&fit=crop&q=80',
    department: 'Ban Vận hành'
  }
]

// Ứng dụng chính trên Portal (Đã tinh gọn, gộp tri thức/thư viện/search/vinh danh vào Base Square)
export const MOCK_APPS = [
  {
    id: 'inside',
    name: 'Base Inside',
    desc: 'Mạng xã hội Doanh nghiệp',
    icon: 'hub',
    color: 'bg-purple-500',
    category: 'INFO+'
  },
  {
    id: 'message',
    name: 'Base Message',
    desc: 'Chat nhóm & Trao đổi trực tiếp',
    icon: 'chat',
    color: 'bg-sky-500',
    category: 'WORK+'
  },
  {
    id: 'square',
    name: 'Base Square',
    desc: 'Hỏi đáp & Quản trị Tri thức',
    icon: 'school',
    color: 'bg-emerald-500',
    category: 'INFO+'
  },
  {
    id: 'admin',
    name: 'Phân hệ Quản trị',
    desc: 'Quản trị & Kiểm duyệt nội dung',
    icon: 'admin_panel_settings',
    color: 'bg-slate-700',
    category: 'ADMIN'
  }
]

// Thông báo toàn công ty (01-welcome.html)
export const MOCK_ANNOUNCEMENTS = [
  {
    id: 'ann-1',
    title: 'Thông báo nghỉ lễ ngày Quốc Khánh 02/09',
    author: 'Nguyễn Lê Thanh Trường',
    date: '16/08',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&auto=format&fit=crop&q=80'
  },
  {
    id: 'ann-2',
    title: 'Công văn báo cáo Kiểm toán nội bộ Q3/2026',
    author: 'Phạm Phát',
    date: '25/04',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80'
  }
]

// Bảng tin mạng xã hội Base Inside (03-social-feed.html)
export const MOCK_SOCIAL_POSTS = [
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
    likes: 0,
    commentsCount: 0,
    isLiked: false,
    image: '',
    comments: []
  },
  {
    id: 'post-1',
    author: 'Võ Thành Luân',
    role: 'Senior Backend & AI Specialist',
    channel: 'Tech & Architecture',
    time: '2 giờ trước',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80',
    content: 'Hôm nay nhóm AI Knowledge Graph đã kết nối thành công pipeline trích xuất tri thức tự động từ tài liệu PDF vào cơ sở dữ liệu đồ thị Neo4j. Các thành viên có thể đặt câu hỏi tại Base Square để AI tự động đối chiếu ngữ nghĩa!',
    likes: 24,
    commentsCount: 6,
    isLiked: false,
    image: 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&auto=format&fit=crop&q=80',
    comments: [
      {
        id: 'c-1',
        author: 'Phạm Phát',
        avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&auto=format&fit=crop&q=80',
        content: 'Quá tuyệt vời! Đã test thử với bộ câu hỏi kiến trúc microservice, vector search phản hồi dưới 120ms.',
        time: '1 giờ trước'
      }
    ]
  },
  {
    id: 'post-2',
    author: 'Lê Thị Mai Phương',
    role: 'HR & Văn hóa Doanh nghiệp',
    channel: 'Toàn công ty',
    time: '5 giờ trước',
    avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
    content: 'Chào mừng các bạn tân binh gia nhập đại gia đình Openhello.vn trong tuần này! Chúc mọi người có những trải nghiệm làm việc năng động, chia sẻ kiến thức và cùng nhau chinh phục các mục tiêu mới.',
    likes: 42,
    commentsCount: 8,
    isLiked: true,
    image: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&auto=format&fit=crop&q=80',
    comments: []
  }
]

// Tin tức nội bộ (03b-social-news.html)
export const MOCK_NEWS = [
  {
    id: 'news-1',
    title: 'Hội thảo Công nghệ 2026: Ứng dụng Graph RAG trong Quản trị Tri thức Nội bộ',
    summary: 'Tổng kết các mô hình triển khai kiến trúc Graph Neural Network và Hybrid Search hỗ trợ tìm kiếm tài liệu chính xác.',
    author: 'Võ Thành Luân',
    date: 'Hôm nay',
    reads: 320,
    cover: 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=600&auto=format&fit=crop&q=80'
  },
  {
    id: 'news-2',
    title: 'Phát động phong trào Đóng góp Tri thức & Tích lũy Base Reward Quý 3',
    summary: 'Nhận điểm thưởng và vinh danh trực tiếp trên bảng xếp hạng Leaderboard khi đóng góp câu trả lời xuất sắc.',
    author: 'Lê Thị Mai Phương',
    date: 'Hôm qua',
    reads: 512,
    cover: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600&auto=format&fit=crop&q=80'
  }
]

// Hội thoại Base Message (05-messenger.html)
export const MOCK_CONVERSATIONS = [
  {
    id: 'conv-1',
    type: 'direct',
    user: {
      name: 'Võ Thành Luân',
      role: 'Senior Backend & AI Specialist',
      avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80',
      online: true
    },
    title: 'Võ Thành Luân',
    subtitle: 'Senior Backend & AI Specialist',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80',
    online: true,
    lastMessage: 'Đã gửi bản sơ đồ ontology và kiến trúc Knowledge Graph mới cập nhật.',
    lastTime: '10:45',
    unread: 1,
    memberCount: 2,
    messages: [
      { id: 'm1', fromMe: false, senderName: 'Võ Thành Luân', text: 'Chào bạn! Bạn có tài liệu mô tả kiến trúc Knowledge Graph mới cập nhật không?', time: '10:35' },
      { id: 'm2', fromMe: true, text: 'Chào Luân, tài liệu đã được lưu trong Base Library mục Backend/AI nhé.', time: '10:38' },
      { id: 'm3', fromMe: false, senderName: 'Võ Thành Luân', text: 'Đồ thị tri thức đã đồng bộ thêm 12 nodes chuyên môn mới rồi nhé!', time: '10:42' },
      {
        id: 'm4',
        fromMe: false,
        senderName: 'Võ Thành Luân',
        text: 'Mình gửi bạn bản sơ đồ ontology phân tầng tri thức và tài liệu kiến trúc cập nhật hôm nay:',
        time: '10:44',
        attachments: [
          {
            id: 'att-1',
            name: 'Kien_truc_Knowledge_Graph_v2.pdf',
            size: '3.4 MB',
            type: 'pdf',
            mimeType: 'application/pdf'
          },
          {
            id: 'att-2',
            name: 'knowledge_graph_schema_preview.png',
            size: '1.8 MB',
            type: 'image',
            url: 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=900&auto=format&fit=crop&q=80',
            mimeType: 'image/png'
          }
        ]
      },
      { id: 'm5', fromMe: true, text: 'Cảm ơn Luân! Mình đã tải tài liệu về kiểm tra và tích hợp vào search engine ngay.', time: '10:45' }
    ]
  },
  {
    id: 'conv-2',
    type: 'direct',
    user: {
      name: 'Phạm Phát',
      role: 'Solutions Architect',
      avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
      online: true
    },
    title: 'Phạm Phát',
    subtitle: 'Solutions Architect',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
    online: true,
    lastMessage: 'Đã hoàn tất cấu hình gateway proxy port 8000 và test tải.',
    lastTime: '09:25',
    unread: 0,
    memberCount: 2,
    messages: [
      { id: 'm201', fromMe: false, senderName: 'Phạm Phát', text: 'Đã hoàn tất cấu hình gateway proxy port 8000 và chạy thử nghiệm chịu tải.', time: '09:15' },
      { id: 'm202', fromMe: true, text: 'Hiệu năng thế nào anh? Có bị nghẽn ở middleware kiểm tra quyền RBAC không anh?', time: '09:18' },
      {
        id: 'm203',
        fromMe: false,
        senderName: 'Phạm Phát',
        text: 'Latency p95 chỉ khoảng 18ms, throughput đạt hơn 3,500 req/s. Mình gửi bảng tổng hợp benchmark chi tiết:',
        time: '09:22',
        attachments: [
          {
            id: 'att-201',
            name: 'Gateway_Performance_Benchmark.xlsx',
            size: '1.2 MB',
            type: 'sheet',
            mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
          }
        ]
      },
      { id: 'm204', fromMe: true, text: 'Kết quả benchmark ấn tượng quá anh! Vậy chiều nay mình tiến hành deploy lên staging luôn.', time: '09:25' }
    ]
  },
  {
    id: 'conv-3',
    type: 'direct',
    user: {
      name: 'Lê Thị Mai Phương',
      role: 'HR & Văn hóa Doanh nghiệp',
      avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
      online: false
    },
    title: 'Lê Thị Mai Phương',
    subtitle: 'HR & Văn hóa Doanh nghiệp',
    avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
    online: false,
    lastMessage: 'Nhớ gửi danh sách nhân sự tham gia hội thảo tuần này nhé.',
    lastTime: 'Hôm qua',
    unread: 0,
    memberCount: 2,
    messages: [
      { id: 'm301', fromMe: false, senderName: 'Lê Thị Mai Phương', text: 'Chào bạn, nhớ gửi danh sách nhân sự tham gia hội thảo AI Enterprise tuần này nhé.', time: '14:20' },
      { id: 'm302', fromMe: true, text: 'Chào chị Phương, team Kỹ thuật đã chốt danh sách 6 bạn tham gia rồi ạ.', time: '14:35' },
      {
        id: 'm303',
        fromMe: false,
        senderName: 'Lê Thị Mai Phương',
        text: 'Chị gửi bạn lịch trình chi tiết và sơ đồ chỗ ngồi của ban tổ chức nhé:',
        time: '14:40',
        attachments: [
          {
            id: 'att-301',
            name: 'Ke_hoach_Hoi_thao_AI_Enterprise_2026.pdf',
            size: '2.1 MB',
            type: 'pdf',
            mimeType: 'application/pdf'
          }
        ]
      },
      { id: 'm304', fromMe: true, text: 'Dạ em nhận được rồi ạ, em thông báo đến các bạn ngay chiều nay.', time: '14:45' }
    ]
  },
  {
    id: 'conv-4',
    type: 'direct',
    user: {
      name: 'Nguyễn Lê Thanh Trường',
      role: 'Operations & QA Lead',
      avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
      online: true
    },
    title: 'Nguyễn Lê Thanh Trường',
    subtitle: 'Operations & QA Lead',
    avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
    online: true,
    lastMessage: 'Tất cả test cases cốt lõi đều pass xanh, sẵn sàng release chiều nay!',
    lastTime: '08:40',
    unread: 1,
    memberCount: 2,
    messages: [
      { id: 'm401', fromMe: false, senderName: 'Nguyễn Lê Thanh Trường', text: 'Báo cáo kiểm thử tự động Sprint 42 đã hoàn thành 100% test suites.', time: '08:30' },
      {
        id: 'm402',
        fromMe: false,
        senderName: 'Nguyễn Lê Thanh Trường',
        text: 'Gửi bạn ảnh chụp màn hình kết quả chạy Cypress và Postman runner mới nhất:',
        time: '08:32',
        attachments: [
          {
            id: 'att-401',
            name: 'qa_automated_test_report_sprint42.png',
            size: '950 KB',
            type: 'image',
            url: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=900&auto=format&fit=crop&q=80',
            mimeType: 'image/png'
          }
        ]
      },
      { id: 'm403', fromMe: true, text: 'Tuyệt quá anh Trường ơi, không có critical bug nào trên luồng phân quyền đúng không anh?', time: '08:35' },
      { id: 'm404', fromMe: false, senderName: 'Nguyễn Lê Thanh Trường', text: 'Tất cả test cases cốt lõi đều pass xanh, sẵn sàng release chiều nay!', time: '08:40' }
    ]
  },
  {
    id: 'conv-5',
    type: 'direct',
    user: {
      name: 'Nguyễn Minh Trang',
      role: 'UI/UX Product Designer',
      avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80',
      online: true
    },
    title: 'Nguyễn Minh Trang',
    subtitle: 'UI/UX Product Designer',
    avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80',
    online: true,
    lastMessage: 'Đẹp lắm Trang, các icon và khoảng cách padding rất đều mắt!',
    lastTime: '11:15',
    unread: 0,
    memberCount: 2,
    messages: [
      { id: 'm501', fromMe: false, senderName: 'Nguyễn Minh Trang', text: 'Sang ơi, mình đã hoàn thành bản thiết kế cập nhật cho module Base Message trên Figma!', time: '11:10' },
      {
        id: 'm502',
        fromMe: false,
        senderName: 'Nguyễn Minh Trang',
        text: 'Bao gồm bộ token màu chuẩn và tài liệu đặc tả components chat chia sẻ tệp:',
        time: '11:12',
        attachments: [
          {
            id: 'att-501',
            name: 'Design_System_Tokens_v3.pdf',
            size: '4.8 MB',
            type: 'pdf',
            mimeType: 'application/pdf'
          },
          {
            id: 'att-502',
            name: 'Messenger_UI_Components_Spec.png',
            size: '1.4 MB',
            type: 'image',
            url: 'https://images.unsplash.com/photo-1581291518655-9523c932deb4?w=900&auto=format&fit=crop&q=80',
            mimeType: 'image/png'
          }
        ]
      },
      { id: 'm503', fromMe: true, text: 'Đẹp lắm Trang, các icon và khoảng cách padding rất đều mắt!', time: '11:15' }
    ]
  },
  {
    id: 'conv-6',
    type: 'direct',
    user: {
      name: 'Lê Phương Thảo',
      role: 'Quản lý Nhân sự & Tuyển dụng',
      avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80',
      online: false
    },
    title: 'Lê Phương Thảo',
    subtitle: 'Quản lý Nhân sự & Tuyển dụng',
    avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80',
    online: false,
    lastMessage: 'Cảm ơn chị Thảo, em đã tải về xem qua quy định mới.',
    lastTime: 'Thứ 6',
    unread: 0,
    memberCount: 2,
    messages: [
      {
        id: 'm601',
        fromMe: false,
        senderName: 'Lê Phương Thảo',
        text: 'Gửi bạn Sang bản Sổ tay Văn hóa Doanh nghiệp và chính sách phúc lợi năm 2026 nhé.',
        time: '15:10',
        attachments: [
          {
            id: 'att-601',
            name: 'So_tay_Van_hoa_Doanh_nghiep_2026.pdf',
            size: '5.2 MB',
            type: 'pdf',
            mimeType: 'application/pdf'
          }
        ]
      },
      { id: 'm602', fromMe: true, text: 'Cảm ơn chị Thảo, em đã tải về xem qua quy định mới.', time: '15:20' }
    ]
  },
  {
    id: 'conv-7',
    type: 'direct',
    user: {
      name: 'Phạm Quốc Bảo',
      role: 'DevOps & SRE Engineer',
      avatar: 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
      online: true
    },
    title: 'Phạm Quốc Bảo',
    subtitle: 'DevOps & SRE Engineer',
    avatar: 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
    online: true,
    lastMessage: 'Đã kiểm tra, cụm MinIO S3 và các pod hoạt động rất ổn định.',
    lastTime: '07:55',
    unread: 0,
    memberCount: 2,
    messages: [
      { id: 'm701', fromMe: false, senderName: 'Phạm Quốc Bảo', text: 'Cụm MinIO S3 lưu trữ tệp đính kèm và MongoDB cluster đã cấu hình tự động mở rộng xong.', time: '07:45' },
      {
        id: 'm702',
        fromMe: false,
        senderName: 'Phạm Quốc Bảo',
        text: 'Gửi bạn file cấu hình biến môi trường và log test kết nối giữa các node:',
        time: '07:48',
        attachments: [
          {
            id: 'att-701',
            name: 'infrastructure_storage_config.yaml',
            size: '48 KB',
            type: 'doc',
            mimeType: 'text/yaml'
          }
        ]
      },
      { id: 'm703', fromMe: true, text: 'Đã kiểm tra, cụm MinIO S3 và các pod hoạt động rất ổn định.', time: '07:55' }
    ]
  },
  {
    id: 'conv-8',
    type: 'direct',
    user: {
      name: 'Đỗ Hoàng Long',
      role: 'Product Owner',
      avatar: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&auto=format&fit=crop&q=80',
      online: true
    },
    title: 'Đỗ Hoàng Long',
    subtitle: 'Product Owner',
    avatar: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&auto=format&fit=crop&q=80',
    online: true,
    lastMessage: 'Rất tốt, khách hàng nội bộ đang rất mong đợi tính năng gửi tệp này.',
    lastTime: '08:10',
    unread: 0,
    memberCount: 2,
    messages: [
      { id: 'm801', fromMe: false, senderName: 'Đỗ Hoàng Long', text: 'Tuần này team mình ưu tiên hoàn thiện đồng bộ giao diện chat và khả năng gửi nhận tệp tài liệu nhé.', time: '08:00' },
      { id: 'm802', fromMe: true, text: 'Dạ vâng anh Long, phần đính kèm file ảnh, file PDF và tính năng tải trực tiếp về máy đang được hoàn thiện tỉ mỉ.', time: '08:05' },
      { id: 'm803', fromMe: false, senderName: 'Đỗ Hoàng Long', text: 'Rất tốt, khách hàng nội bộ đang rất mong đợi tính năng gửi tệp này.', time: '08:10' }
    ]
  },
  {
    id: 'conv-group-1',
    type: 'group',
    title: 'Ban Kỹ thuật & Kiến trúc Core',
    subtitle: '5 thành viên',
    avatar: '',
    online: false,
    lastMessage: 'Phạm Phát: Mình vừa gửi bản kiến trúc hệ thống mới nhất, cả team xem qua nhé!',
    lastTime: '11:30',
    unread: 1,
    memberCount: 5,
    user: {
      name: 'Ban Kỹ thuật & Kiến trúc Core',
      role: 'Nhóm Kỹ thuật (5 thành viên)',
      avatar: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=150&auto=format&fit=crop&q=80',
      online: true
    },
    members: [
      { userId: 'u-phat', name: 'Phạm Phát', role: 'Trưởng nhóm kiến trúc', memberRole: 'owner' },
      { userId: 'u-luan', name: 'Võ Thành Luân', role: 'Senior Backend & AI', memberRole: 'member' },
      { userId: 'u-bao', name: 'Phạm Quốc Bảo', role: 'DevOps Lead', memberRole: 'member' },
      { userId: 'u-sang', name: 'Sang Phạm', role: 'Lead Developer', memberRole: 'member' },
      { userId: 'u-member', name: 'Thành viên', role: 'Kỹ sư phần mềm', memberRole: 'member' }
    ],
    messages: [
      { id: 'mg101', fromMe: false, senderName: 'Phạm Phát', text: 'Chào cả nhóm, chúng ta họp nhanh 15 phút về kiến trúc microservices và phân tán dữ liệu nhé.', time: '11:20' },
      { id: 'mg102', fromMe: false, senderName: 'Võ Thành Luân', text: 'Module AI Knowledge đã kết nối mượt mà qua gRPC, độ trễ vector search chỉ 25ms.', time: '11:22' },
      { id: 'mg103', fromMe: false, senderName: 'Phạm Quốc Bảo', text: 'Hạ tầng K8s và Docker containers đều đạt ngưỡng 99.9% uptime, sẵn sàng chịu tải cao.', time: '11:25' },
      {
        id: 'mg104',
        fromMe: false,
        senderName: 'Phạm Phát',
        text: 'Mình vừa gửi bản kiến trúc hệ thống mới nhất, cả team xem qua nhé!',
        time: '11:30',
        attachments: [
          {
            id: 'att-g101',
            name: 'Kien_truc_Microservices_Core_System_2026.pdf',
            size: '4.6 MB',
            type: 'pdf',
            mimeType: 'application/pdf'
          }
        ]
      },
      { id: 'mg105', fromMe: true, text: 'Dạ em đã tải bản PDF về nghiên cứu rồi ạ, sơ đồ kết nối API Gateway rất tường minh.', time: '11:32' }
    ]
  },
  {
    id: 'conv-group-2',
    type: 'group',
    title: 'Dự án AI & Knowledge Graph',
    subtitle: '5 thành viên',
    avatar: '',
    online: false,
    lastMessage: 'Võ Thành Luân: Đã import thêm 500 bài viết tri thức vào Neo4j và vector database.',
    lastTime: '10:15',
    unread: 0,
    memberCount: 5,
    user: {
      name: 'Dự án AI & Knowledge Graph',
      role: 'Nhóm AI & Tri thức (5 thành viên)',
      avatar: 'https://images.unsplash.com/photo-1509228468518-180dd4864904?w=150&auto=format&fit=crop&q=80',
      online: true
    },
    members: [
      { userId: 'u-luan', name: 'Võ Thành Luân', role: 'Chuyên gia AI', memberRole: 'owner' },
      { userId: 'u-trang', name: 'Nguyễn Minh Trang', role: 'UI/UX Designer', memberRole: 'member' },
      { userId: 'u-phat', name: 'Phạm Phát', role: 'Solutions Architect', memberRole: 'member' },
      { userId: 'u-sang', name: 'Sang Phạm', role: 'Lead Developer', memberRole: 'member' },
      { userId: 'u-member', name: 'Thành viên', role: 'Kỹ sư phần mềm', memberRole: 'member' }
    ],
    messages: [
      {
        id: 'mg201',
        fromMe: false,
        senderName: 'Võ Thành Luân',
        text: 'Đã import thêm 500 bài viết tri thức vào Neo4j và vector database.',
        time: '10:15',
        attachments: [
          {
            id: 'att-g201',
            name: 'Knowledge_Graph_Preview_Diagram.png',
            size: '1.2 MB',
            type: 'image',
            url: 'https://images.unsplash.com/photo-1509228468518-180dd4864904?w=900&auto=format&fit=crop&q=80',
            mimeType: 'image/png'
          }
        ]
      },
      { id: 'mg202', fromMe: false, senderName: 'Nguyễn Minh Trang', text: 'Giao diện đồ thị tri thức trên web hiển thị các node và mối liên kết rất trực quan!', time: '10:18' },
      { id: 'mg203', fromMe: true, text: 'Thuật toán tìm kiếm ngữ nghĩa semantic search phản hồi dưới 50ms, độ chính xác cao.', time: '10:20' }
    ]
  },
  {
    id: 'conv-group-3',
    type: 'group',
    title: 'Ban Nhân sự & Văn hóa Openhello',
    subtitle: '5 thành viên',
    avatar: '',
    online: false,
    lastMessage: 'Lê Phương Thảo: Chúc mừng sinh nhật các thành viên có ngày sinh trong tháng này!',
    lastTime: '09:00',
    unread: 0,
    memberCount: 5,
    user: {
      name: 'Ban Nhân sự & Văn hóa Openhello',
      role: 'Khối Nhân sự (5 thành viên)',
      avatar: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=150&auto=format&fit=crop&q=80',
      online: true
    },
    members: [
      { userId: 'u-thao', name: 'Lê Phương Thảo', role: 'Quản lý Nhân sự', memberRole: 'owner' },
      { userId: 'u-phuong', name: 'Lê Thị Mai Phương', role: 'HR & Culture', memberRole: 'member' },
      { userId: 'u-truong', name: 'Nguyễn Lê Thanh Trường', role: 'Operations Lead', memberRole: 'member' },
      { userId: 'u-sang', name: 'Sang Phạm', role: 'Lead Developer', memberRole: 'member' },
      { userId: 'u-member', name: 'Thành viên', role: 'Kỹ sư phần mềm', memberRole: 'member' }
    ],
    messages: [
      {
        id: 'mg301',
        fromMe: false,
        senderName: 'Lê Phương Thảo',
        text: 'Chúc mừng sinh nhật các thành viên có ngày sinh trong tháng này! Chúc mọi người luôn dồi dào sức khỏe và đam mê.',
        time: '09:00',
        attachments: [
          {
            id: 'att-g301',
            name: 'Lich_Sinh_Nhat_Cong_Ty_Thang_11.pdf',
            size: '1.5 MB',
            type: 'pdf',
            mimeType: 'application/pdf'
          }
        ]
      },
      { id: 'mg302', fromMe: false, senderName: 'Lê Thị Mai Phương', text: 'Chiều nay 16h có tiệc trà nhẹ tại sảnh tầng 3 nhé cả nhà ơi 🎉 Mời mọi người tham gia đông đủ nhé!', time: '09:05' },
      { id: 'mg303', fromMe: true, text: 'Cảm ơn Ban Nhân sự nhiều ạ! Team Kỹ thuật sẽ có mặt đúng giờ.', time: '09:10' }
    ]
  },
  {
    id: 'conv-group-4',
    type: 'group',
    title: 'Release & Triển khai Production',
    subtitle: '5 thành viên',
    avatar: '',
    online: false,
    lastMessage: 'Nguyễn Lê Thanh Trường: Đã hoàn tất danh sách kiểm tra trước giờ Go-Live.',
    lastTime: 'Hôm qua',
    unread: 0,
    memberCount: 5,
    user: {
      name: 'Release & Triển khai Production',
      role: 'Đội Vận hành Go-Live (5 thành viên)',
      avatar: 'https://images.unsplash.com/photo-1531403009284-440f080d1e12?w=150&auto=format&fit=crop&q=80',
      online: true
    },
    members: [
      { userId: 'u-truong', name: 'Nguyễn Lê Thanh Trường', role: 'Operations & QA Lead', memberRole: 'owner' },
      { userId: 'u-long', name: 'Đỗ Hoàng Long', role: 'Product Owner', memberRole: 'member' },
      { userId: 'u-bao', name: 'Phạm Quốc Bảo', role: 'DevOps Lead', memberRole: 'member' },
      { userId: 'u-sang', name: 'Sang Phạm', role: 'Lead Developer', memberRole: 'member' },
      { userId: 'u-member', name: 'Thành viên', role: 'Kỹ sư phần mềm', memberRole: 'member' }
    ],
    messages: [
      { id: 'mg401', fromMe: false, senderName: 'Đỗ Hoàng Long', text: 'Kiểm tra kỹ các luồng xác thực, phân quyền RBAC và khả năng chịu tải trên server trước khi release.', time: '16:00' },
      {
        id: 'mg402',
        fromMe: false,
        senderName: 'Nguyễn Lê Thanh Trường',
        text: 'Đã hoàn tất danh sách kiểm tra trước giờ Go-Live, gửi cả nhóm file checklist:',
        time: '16:05',
        attachments: [
          {
            id: 'att-g401',
            name: 'Release_Checklist_Production_v1.0.xlsx',
            size: '820 KB',
            type: 'sheet',
            mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
          }
        ]
      },
      { id: 'mg403', fromMe: false, senderName: 'Phạm Quốc Bảo', text: 'Đã tạo snapshot backup database và thiết lập quy trình rollback tự động an toàn.', time: '16:10' },
      { id: 'mg404', fromMe: true, text: 'Team lập trình đã rà soát xong toàn bộ mã nguồn, sẵn sàng triển khai đúng giờ.', time: '16:15' }
    ]
  }
]

// Thư viện tài liệu học tập Base Library (08-learning-home.html)
export const MOCK_LIBRARY_DOCUMENTS = [
  {
    id: 'doc-1',
    title: 'Khởi đầu với Knowledge Graph & Kiến trúc Tri thức Doanh nghiệp',
    category: 'Backend / AI',
    type: 'PDF',
    size: '3.4 MB',
    author: 'Võ Thành Luân',
    date: '14/09/2026',
    downloads: 142,
    views: 388,
    isIndexed: true
  },
  {
    id: 'doc-2',
    title: 'Hướng dẫn Thiết kế Microservices và Đồng bộ Dữ liệu Event-Driven',
    category: 'Kỹ thuật Phần mềm',
    type: 'DOCX',
    size: '1.8 MB',
    author: 'Phạm Phát',
    date: '12/09/2026',
    downloads: 98,
    views: 245,
    isIndexed: true
  },
  {
    id: 'doc-3',
    title: 'Sổ tay Văn hóa Doanh nghiệp & Quy chế Bảo mật Thông tin',
    category: 'HR & Văn hóa',
    type: 'PDF',
    size: '4.2 MB',
    author: 'Lê Thị Mai Phương',
    date: '01/09/2026',
    downloads: 215,
    views: 680,
    isIndexed: false
  },
  {
    id: 'doc-4',
    title: 'Kế hoạch Phân bổ Tài nguyên Điện toán Đám mây & Database Neo4j',
    category: 'DevOps & Hạ tầng',
    type: 'XLSX',
    size: '890 KB',
    author: 'Sang Phạm',
    date: '28/08/2026',
    downloads: 76,
    views: 180,
    isIndexed: true
  }
]

// Danh sách câu hỏi hỏi đáp Base Square (09-qa-list.html)
export const MOCK_QA_QUESTIONS = [
  {
    id: 'q-1',
    title: 'Knowledge Graph dùng để làm gì trong quản trị tri thức doanh nghiệp?',
    author: 'Sang Phạm',
    authorAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
    authorRole: 'Lead Developer',
    topic: 'Kỹ thuật',
    category: 'Backend / AI',
    time: '3 giờ trước',
    votes: 18,
    answersCount: 2,
    views: 165,
    isAnswered: true,
    tags: ['Knowledge Graph', 'AI', 'Neo4j', 'Tri thức']
  },
  {
    id: 'q-2',
    title: 'Làm thế nào để tối ưu truy vấn Eloquent Laravel kết hợp Index Database?',
    author: 'Võ Thành Luân',
    authorAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80',
    authorRole: 'Senior Backend',
    topic: 'Kỹ thuật',
    category: 'Backend',
    time: 'Hôm qua',
    votes: 12,
    answersCount: 4,
    views: 290,
    isAnswered: true,
    tags: ['Laravel', 'Database', 'Performance']
  },
  {
    id: 'q-3',
    title: 'Quy trình xét thưởng và đổi điểm Base Reward sang voucher nội bộ như thế nào?',
    author: 'Nguyễn Lê Thanh Trường',
    authorAvatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&auto=format&fit=crop&q=80',
    authorRole: 'Operations',
    topic: 'Chính sách',
    category: 'HR & Phúc lợi',
    time: '2 ngày trước',
    votes: 9,
    answersCount: 1,
    views: 124,
    isAnswered: false,
    tags: ['Reward', 'HR', 'Chính sách']
  }
]

// Bảng xếp hạng vinh danh Base Reward Leaderboard (12-leaderboard.html)
export const MOCK_LEADERBOARD = [
  {
    rank: 1,
    name: 'Võ Thành Luân',
    role: 'Senior Backend & AI Specialist',
    points: '1,450 Điểm',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
    badge: '🏆 Quán quân Tri thức'
  },
  {
    rank: 2,
    name: 'Phạm Phát',
    role: 'Solutions Architect',
    points: '1,120 Điểm',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
    badge: '🥈 Chuyên gia Đóng góp'
  },
  {
    rank: 3,
    name: 'Nguyễn Lê Thanh Trường',
    role: 'Operations & QA Lead',
    points: '890 Điểm',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
    badge: '🥉 Thành viên Tích cực'
  },
  {
    rank: 4,
    name: 'Lê Thị Mai Phương',
    role: 'HR & Văn hóa',
    points: '640 Điểm',
    avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
    badge: 'Người truyền cảm hứng'
  },
  {
    rank: 5,
    name: 'Sang Phạm',
    role: 'Lead Developer',
    points: '520 Điểm',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
    badge: 'Cộng tác viên nổi bật'
  }
]

// Các node trong Cây tri thức Neo4j (14-knowledge-graph-explorer.html)
export const MOCK_GRAPH_DATA = {
  nodes: [
    { id: 'n1', label: 'Võ Thành Luân', type: 'expert', role: 'Chuyên gia AI/KG', x: 260, y: 180, r: 28, color: '#2563eb' },
    { id: 'n2', label: 'Chủ đề: Kỹ thuật', type: 'topic', role: 'Chủ đề cấp cao', x: 440, y: 120, r: 24, color: '#6366f1' },
    { id: 'n3', label: 'Backend Architecture', type: 'category', role: 'Danh mục', x: 480, y: 270, r: 22, color: '#8b5cf6' },
    { id: 'n4', label: 'Khởi đầu với KG', type: 'document', role: 'Tài liệu v1', x: 670, y: 190, r: 24, color: '#10b981' },
    { id: 'n5', label: 'Knowledge Graph dùng làm gì?', type: 'question', role: 'Hỏi đáp Q&A', x: 380, y: 360, r: 22, color: '#f59e0b' }
  ],
  links: [
    { source: 'n1', target: 'n2', label: 'MANAGES' },
    { source: 'n2', target: 'n3', label: 'CONTAINS' },
    { source: 'n3', target: 'n4', label: 'HAS_DOCUMENT' },
    { source: 'n1', target: 'n4', label: 'AUTHORED' },
    { source: 'n3', target: 'n5', label: 'RELATED_TO' },
    { source: 'n1', target: 'n5', label: 'ANSWERED' }
  ]
}

// Kết quả tìm kiếm AI Hybrid Search (15-hybrid-search.html)
export const MOCK_SEARCH_RESULTS = [
  {
    id: 's-1',
    type: 'Tài liệu PDF',
    typeColor: 'bg-rose-100 text-rose-700',
    title: 'Khởi đầu với Knowledge Graph & Kiến trúc Tri thức Doanh nghiệp',
    snippet: '...Mô hình Đồ thị Tri thức (Knowledge Graph) ánh xạ thực thể nhân sự, kỹ năng, tài liệu kỹ thuật thành một mạng lưới liên kết có ngữ nghĩa...',
    author: 'Võ Thành Luân',
    matchScore: '98% Match (Vector + Graph)',
    relation: 'Liên kết tới: Chủ đề Kỹ thuật > Backend'
  },
  {
    id: 's-2',
    type: 'Hỏi đáp Q&A',
    typeColor: 'bg-emerald-100 text-emerald-700',
    title: 'Knowledge Graph dùng để làm gì trong quản trị tri thức doanh nghiệp?',
    snippet: '...Trả lời bởi Võ Thành Luân: KG giúp giải quyết triệt để tình trạng phân mảnh thông tin, hỗ trợ tìm kiếm chuyên gia theo kỹ năng thực tế...',
    author: 'Sang Phạm',
    matchScore: '94% Match',
    relation: '2 Câu trả lời được kiểm duyệt'
  },
  {
    id: 's-3',
    type: 'Chuyên gia',
    typeColor: 'bg-blue-100 text-blue-700',
    title: 'Võ Thành Luân — Chuyên gia Backend & Graph RAG',
    snippet: 'Phụ trách nghiên cứu mô hình AI Knowledge Graph, tối ưu hóa cơ sở dữ liệu đồ thị Neo4j và kiến trúc Microservices.',
    author: 'Phòng Nghiên cứu AI',
    matchScore: '91% Match (Expert Finder)',
    relation: '14 Tài liệu xuất bản, 48 câu trả lời'
  }
]
