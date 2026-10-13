// Dữ liệu mock thuần JavaScript (JS) cho toàn bộ hệ thống OpenHello / Base.vn
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
    content: 'Chào mừng các bạn tân binh gia nhập đại gia đình OpenHello trong tuần này! Chúc mọi người có những trải nghiệm làm việc năng động, chia sẻ kiến thức và cùng nhau chinh phục các mục tiêu mới.',
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
    user: {
      name: 'Võ Thành Luân',
      role: 'AI Specialist',
      avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80',
      online: true
    },
    lastMessage: 'Đồ thị tri thức đã đồng bộ thêm 12 nodes chuyên môn mới rồi nhé!',
    lastTime: '10:42',
    unread: 1,
    messages: [
      { id: 'm1', fromMe: false, text: 'Chào Sang! Bạn có tài liệu mô tả kiến trúc Knowledge Graph mới cập nhật không?', time: '10:35' },
      { id: 'm2', fromMe: true, text: 'Chào Luân, tài liệu đã được lưu trong Base Library mục Backend/AI nhé.', time: '10:38' },
      { id: 'm3', fromMe: false, text: 'Đồ thị tri thức đã đồng bộ thêm 12 nodes chuyên môn mới rồi nhé!', time: '10:42' }
    ]
  },
  {
    id: 'conv-2',
    user: {
      name: 'Phạm Phát',
      role: 'Solutions Architect',
      avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
      online: true
    },
    lastMessage: 'Đã hoàn tất cấu hình gateway proxy port 8000.',
    lastTime: '09:15',
    unread: 0,
    messages: [
      { id: 'm4', fromMe: false, text: 'Đã hoàn tất cấu hình gateway proxy port 8000.', time: '09:15' }
    ]
  },
  {
    id: 'conv-3',
    user: {
      name: 'Lê Thị Mai Phương',
      role: 'HR & Culture',
      avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
      online: false
    },
    lastMessage: 'Nhớ gửi danh sách nhân sự tham gia hội thảo tuần này nhé.',
    lastTime: 'Hôm qua',
    unread: 0,
    messages: [
      { id: 'm5', fromMe: false, text: 'Nhớ gửi danh sách nhân sự tham gia hội thảo tuần này nhé.', time: 'Hôm qua' }
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
