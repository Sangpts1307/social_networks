export interface UserProfile {
  id: string
  name: string
  email: string
  avatar: string
  role: 'admin' | 'employee'
  roleTitle: string
  department: string
  coins: number
  points: number
  answersCount: number
  postsCount: number
  skills: string[]
}

export interface PostComment {
  id: string
  author: string
  avatar: string
  content: string
  createdAt: string
}

export interface SocialPost {
  id: string
  author: string
  authorRole: string
  department: string
  avatar: string
  content: string
  imageUrl?: string
  channel: string
  createdAt: string
  likes: number
  isLiked: boolean
  isSaved: boolean
  comments: PostComment[]
}

export interface ChatMessage {
  id: string
  senderId: string
  senderName: string
  text: string
  time: string
  isMe: boolean
}

export interface ChatConversation {
  id: string
  user: {
    id: string
    name: string
    avatar: string
    role: string
    department: string
    online: boolean
  }
  lastMessage: string
  lastTime: string
  unreadCount: number
  messages: ChatMessage[]
}

export interface QaAnswer {
  id: string
  author: string
  authorRole: string
  avatar: string
  content: string
  createdAt: string
  votes: number
  isAccepted: boolean
  isVoted?: boolean
}

export interface QaQuestion {
  id: string
  title: string
  content: string
  topic: string
  category: string
  author: string
  authorRole: string
  avatar: string
  createdAt: string
  votes: number
  answersCount: number
  views: number
  isSaved?: boolean
  answers: QaAnswer[]
}

export interface LibraryDocument {
  id: string
  title: string
  category: string
  department: string
  fileType: 'pdf' | 'docx'
  fileSize: string
  author: string
  createdAt: string
  downloads: number
  isAiIndexed: boolean
  summary: string
}

export interface GraphNode {
  id: string
  label: string
  type: 'concept' | 'service' | 'person' | 'document'
  group: string
  x: number
  y: number
  details: string
}

export interface GraphEdge {
  source: string
  target: string
  relation: string
}

export interface LeaderboardMember {
  rank: number
  name: string
  role: string
  department: string
  avatar: string
  points: number
  coins: number
  acceptedAnswers: number
  badge: string
}

export interface ModerationItem {
  id: string
  type: 'post' | 'comment'
  author: string
  authorDept: string
  content: string
  reason: string
  flaggedBy: 'AI Scanner' | 'Người dùng báo cáo'
  flaggedAt: string
  status: 'pending' | 'approved' | 'hidden' | 'deleted'
}

export const INITIAL_CURRENT_USER: UserProfile = {
  id: 'u-luan',
  name: 'Võ Thành Luân',
  email: 'luan.vt@openhello.vn',
  avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
  role: 'admin',
  roleTitle: 'Kỹ sư Hệ thống & AI',
  department: 'Khối Kỹ thuật & R&D',
  coins: 1250,
  points: 3420,
  answersCount: 18,
  postsCount: 12,
  skills: ['Vue 3', 'TypeScript', 'Knowledge Graph', 'API Gateway', 'Microservices', 'Docker']
}

export const INITIAL_POSTS: SocialPost[] = [
  {
    id: 'p-1',
    author: 'Trần Hoàng Nam',
    authorRole: 'Tech Lead / Kiến trúc sư',
    department: 'Khối Kỹ thuật',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80',
    channel: 'Kỹ thuật',
    content: 'Đã hoàn thành tích hợp Neo4j với dịch vụ AI Knowledge Graph v2.1. Tốc độ truy vấn ngữ nghĩa và tìm kiếm đa phương thức cải thiện hơn 40%. Anh em các team có thể kiểm tra trực tiếp qua thanh tìm kiếm AI Hybrid Search.',
    imageUrl: 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&auto=format&fit=crop&q=80',
    createdAt: '15 phút trước',
    likes: 24,
    isLiked: false,
    isSaved: true,
    comments: [
      {
        id: 'c-1',
        author: 'Phạm Quốc Bảo',
        avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=120&auto=format&fit=crop&q=80',
        content: 'Benchmark tải thực tế trên k8s cụm staging rất ổn định nhé anh Nam!',
        createdAt: '10 phút trước'
      },
      {
        id: 'c-2',
        author: 'Nguyễn Minh Trang',
        avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=120&auto=format&fit=crop&q=80',
        content: 'UI hiển thị kết quả tìm kiếm kết hợp rất trực quan, team Product đánh giá cao.',
        createdAt: '5 phút trước'
      }
    ]
  },
  {
    id: 'p-2',
    author: 'Lê Phương Thảo',
    authorRole: 'Trưởng ban Nhân sự',
    department: 'Nhân sự & Văn hóa',
    avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120&auto=format&fit=crop&q=80',
    channel: 'Toàn công ty',
    content: 'Thông báo lịch làm việc kết hợp linh hoạt (Hybrid Working) tháng tới và buổi Tech Sharing "Ứng dụng AI trong tự động hóa quy trình nghiệp vụ" vào chiều thứ Sáu lúc 15:30 tại Hội trường tầng 3.',
    createdAt: '2 giờ trước',
    likes: 46,
    isLiked: true,
    isSaved: false,
    comments: [
      {
        id: 'c-3',
        author: 'Võ Thành Luân',
        avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
        content: 'Team R&D sẽ chuẩn bị demo trực tiếp trên hệ thống nội bộ.',
        createdAt: '1 giờ trước'
      }
    ]
  },
  {
    id: 'p-3',
    author: 'Nguyễn Minh Trang',
    authorRole: 'Trưởng nhóm Sản phẩm',
    department: 'Ban Sản phẩm',
    avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=120&auto=format&fit=crop&q=80',
    channel: 'Sản phẩm',
    content: 'Khởi động chương trình Đổi xu thưởng (Base Reward) lấy các khóa đào tạo nâng cao kỹ năng và voucher sách chuyên môn. Các bạn đóng góp câu trả lời được duyệt tại Base Square sẽ được nhân đôi điểm thưởng tuần này!',
    createdAt: 'Hôm qua',
    likes: 38,
    isLiked: false,
    isSaved: false,
    comments: []
  }
]

export const INITIAL_CONVERSATIONS: ChatConversation[] = [
  {
    id: 'conv-1',
    user: {
      id: 'u-nam',
      name: 'Trần Hoàng Nam',
      avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80',
      role: 'Tech Lead',
      department: 'Khối Kỹ thuật',
      online: true
    },
    lastMessage: 'Em kiểm tra lại latency bên Gateway khi gọi Graph nhé.',
    lastTime: '10:42',
    unreadCount: 1,
    messages: [
      { id: 'm-1', senderId: 'u-nam', senderName: 'Trần Hoàng Nam', text: 'Chào Luân, tiến độ kết nối giao diện với Gateway thế nào rồi?', time: '10:35', isMe: false },
      { id: 'm-2', senderId: 'u-luan', senderName: 'Võ Thành Luân', text: 'Em đã chuẩn bị xong toàn bộ màn hình và mock data đồng bộ rồi anh ạ.', time: '10:38', isMe: true },
      { id: 'm-3', senderId: 'u-nam', senderName: 'Trần Hoàng Nam', text: 'Tốt lắm. Em kiểm tra lại latency bên Gateway khi gọi Graph nhé.', time: '10:42', isMe: false }
    ]
  },
  {
    id: 'conv-2',
    user: {
      id: 'u-trang',
      name: 'Nguyễn Minh Trang',
      avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=120&auto=format&fit=crop&q=80',
      role: 'Product Lead',
      department: 'Ban Sản phẩm',
      online: true
    },
    lastMessage: 'Giao diện đồng bộ tối giản nhìn rất thoáng và rõ ràng!',
    lastTime: '09:15',
    unreadCount: 0,
    messages: [
      { id: 'm-4', senderId: 'u-trang', senderName: 'Nguyễn Minh Trang', text: 'Luân ơi bản thiết kế mới không dùng shadow đậm đúng tinh thần tối giản rồi đó.', time: '09:10', isMe: false },
      { id: 'm-5', senderId: 'u-luan', senderName: 'Võ Thành Luân', text: 'Vâng chị, em bỏ bớt các border-radius quá to và tinh gọn lại toàn bộ các nút bấm.', time: '09:12', isMe: true },
      { id: 'm-6', senderId: 'u-trang', senderName: 'Nguyễn Minh Trang', text: 'Giao diện đồng bộ tối giản nhìn rất thoáng và rõ ràng!', time: '09:15', isMe: false }
    ]
  },
  {
    id: 'conv-3',
    user: {
      id: 'u-thao',
      name: 'Lê Phương Thảo',
      avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120&auto=format&fit=crop&q=80',
      role: 'HR Lead',
      department: 'Nhân sự & Văn hóa',
      online: false
    },
    lastMessage: 'Đã cập nhật danh sách nhân sự mới vào thư mục Onboarding.',
    lastTime: 'Hôm qua',
    unreadCount: 0,
    messages: [
      { id: 'm-7', senderId: 'u-thao', senderName: 'Lê Phương Thảo', text: 'Đã cập nhật danh sách nhân sự mới vào thư mục Onboarding.', time: '16:20', isMe: false }
    ]
  }
]

export const INITIAL_QUESTIONS: QaQuestion[] = [
  {
    id: 'q-1',
    title: 'Làm sao cấu hình xác thực JWT liên dịch vụ qua API Gateway đảm bảo phân quyền RBAC?',
    topic: 'Kỹ thuật',
    category: 'Backend',
    author: 'Phạm Quốc Bảo',
    authorRole: 'DevOps Specialist',
    avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=120&auto=format&fit=crop&q=80',
    createdAt: 'Hôm qua',
    votes: 32,
    answersCount: 2,
    views: 142,
    content: 'Khi request đi qua API Gateway, chúng ta nên giải mã JWT và forward User-Id kèm Role vào header nội bộ (X-User-Id, X-User-Role) cho từng microservice hay để các service tự giải mã public key?',
    answers: [
      {
        id: 'ans-1',
        author: 'Trần Hoàng Nam',
        authorRole: 'Tech Lead',
        avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80',
        content: 'Theo mô hình Zero-Trust nhẹ cho nội bộ, API Gateway thực hiện xác thực Token ban đầu, sau đó forward danh tính qua header được ký nội bộ (Internal HMAC) hoặc mTLS. Các service hạ tầng chỉ cần kiểm tra header này mà không cần truy vấn lại Identity DB.',
        createdAt: 'Hôm qua',
        votes: 18,
        isAccepted: true
      },
      {
        id: 'ans-2',
        author: 'Võ Thành Luân',
        authorRole: 'Kỹ sư Hệ thống',
        avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
        content: 'Bổ sung thêm: có thể áp dụng Redis Cache tại Gateway để kiểm tra blacklist token khi người dùng đăng xuất ngay lập tức.',
        createdAt: '5 giờ trước',
        votes: 7,
        isAccepted: false
      }
    ]
  },
  {
    id: 'q-2',
    title: 'Chiến lược chunking văn bản tiếng Việt khi nạp tài liệu vào Knowledge Graph AI?',
    topic: 'Kỹ thuật',
    category: 'AI & Data',
    author: 'Nguyễn Văn Minh',
    authorRole: 'AI Research Engineer',
    avatar: 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=120&auto=format&fit=crop&q=80',
    createdAt: '2 ngày trước',
    votes: 21,
    answersCount: 1,
    views: 95,
    content: 'Khi xử lý các văn bản quy trình và tài liệu kỹ thuật tiếng Việt, việc ngắt câu theo dấu chấm thông thường dễ làm mất ngữ cảnh của các điều khoản số học hoặc danh mục mục lục.',
    answers: [
      {
        id: 'ans-3',
        author: 'Trần Hoàng Nam',
        authorRole: 'Tech Lead',
        avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80',
        content: 'Nên dùng parser phân tích cú pháp theo Markdown Heading / Paragraph chunking kết hợp sliding window overlap 15-20% để giữ nguyên vẹn liên kết thực thể.',
        createdAt: '1 ngày trước',
        votes: 12,
        isAccepted: true
      }
    ]
  },
  {
    id: 'q-3',
    title: 'Mẫu đề xuất cấp phát ngân sách đào tạo nội bộ quý 4 theo biểu mẫu mới?',
    topic: 'Nhân sự',
    category: 'Quy trình & Đào tạo',
    author: 'Đặng Mai Lan',
    authorRole: 'Chuyên viên Marketing',
    avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=120&auto=format&fit=crop&q=80',
    createdAt: '3 ngày trước',
    votes: 14,
    answersCount: 1,
    views: 68,
    content: 'Mình cần xin biểu mẫu đề xuất kinh phí cử nhân sự tham gia hội thảo công nghệ quý 4.',
    answers: [
      {
        id: 'ans-4',
        author: 'Lê Phương Thảo',
        authorRole: 'HR Lead',
        avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120&auto=format&fit=crop&q=80',
        content: 'Bạn vào mục "Base Library" -> chọn phòng ban "Nhân sự & Văn hóa", tải file "Quy-trinh-dao-tao-va-ngan-sach-2026.docx" có sẵn form phụ lục nhé.',
        createdAt: '3 ngày trước',
        votes: 9,
        isAccepted: true
      }
    ]
  }
]

export const INITIAL_DOCUMENTS: LibraryDocument[] = [
  {
    id: 'doc-1',
    title: 'Sơ đồ kiến trúc vi dịch vụ và đặc tả API Gateway 2026',
    category: 'Kiến trúc hệ thống',
    department: 'Khối Kỹ thuật',
    fileType: 'pdf',
    fileSize: '4.8 MB',
    author: 'Trần Hoàng Nam',
    createdAt: '24/08/2026',
    downloads: 184,
    isAiIndexed: true,
    summary: 'Đặc tả chi tiết ranh giới các dịch vụ Identity, Social, Learning, Chat, Notification và AI Knowledge.'
  },
  {
    id: 'doc-2',
    title: 'Quy chế bảo mật thông tin và phân quyền dữ liệu doanh nghiệp',
    category: 'Chính sách & Quy định',
    department: 'Pháp chế & An ninh mạng',
    fileType: 'pdf',
    fileSize: '2.1 MB',
    author: 'Lê Phương Thảo',
    createdAt: '15/08/2026',
    downloads: 320,
    isAiIndexed: true,
    summary: 'Quy định quản lý tài khoản, mã hóa dữ liệu lưu trữ và chuẩn bảo mật khi giao tiếp bên ngoài.'
  },
  {
    id: 'doc-3',
    title: 'Hướng dẫn xây dựng Knowledge Graph và trích xuất thực thể AI',
    category: 'R&D AI & Dữ liệu',
    department: 'Khối Kỹ thuật',
    fileType: 'pdf',
    fileSize: '6.4 MB',
    author: 'Võ Thành Luân',
    createdAt: '02/09/2026',
    downloads: 95,
    isAiIndexed: true,
    summary: 'Tài liệu hướng dẫn cấu hình Neo4j, trích xuất thực thể từ tài liệu nội bộ và thuật toán tìm kiếm kết hợp.'
  },
  {
    id: 'doc-4',
    title: 'Sổ tay văn hóa doanh nghiệp và quy trình Onboarding nhân sự',
    category: 'Nhân sự',
    department: 'Nhân sự & Văn hóa',
    fileType: 'docx',
    fileSize: '3.5 MB',
    author: 'Lê Phương Thảo',
    createdAt: '10/08/2026',
    downloads: 412,
    isAiIndexed: true,
    summary: 'Tài liệu chỉ dẫn dành cho nhân sự mới, giới thiệu hệ sinh thái các ứng dụng nội bộ.'
  }
]

export const INITIAL_GRAPH_NODES: GraphNode[] = [
  { id: '1', label: 'Hệ thống Microservices', type: 'concept', group: 'Hạ tầng', x: 260, y: 150, details: 'Kiến trúc nền tảng 6 dịch vụ độc lập kết nối qua Gateway.' },
  { id: '2', label: 'API Gateway NestJS', type: 'service', group: 'Hạ tầng', x: 420, y: 120, details: 'Điểm tiếp nhận tập trung, phân luồng JWT và rate limit.' },
  { id: '3', label: 'Identity Service', type: 'service', group: 'Dịch vụ', x: 580, y: 100, details: 'Xác thực tài khoản, phiên làm việc và RBAC.' },
  { id: '4', label: 'AI Knowledge Graph', type: 'service', group: 'AI', x: 380, y: 260, details: 'Dịch vụ phân tích tri thức và trích xuất liên kết thực thể.' },
  { id: '5', label: 'Neo4j Database', type: 'service', group: 'Dữ liệu', x: 550, y: 280, details: 'Cơ sở dữ liệu đồ thị lưu trữ thực thể và quan hệ nghiệp vụ.' },
  { id: '6', label: 'Trần Hoàng Nam', type: 'person', group: 'Chuyên gia', x: 180, y: 280, details: 'Tech Lead phụ trách kiến trúc lõi và cơ sở dữ liệu đồ thị.' },
  { id: '7', label: 'Võ Thành Luân', type: 'person', group: 'Chuyên gia', x: 460, y: 410, details: 'Kỹ sư hệ thống chịu trách nhiệm tích hợp Frontend và AI Gateway.' },
  { id: '8', label: 'Sơ đồ Kiến trúc v2', type: 'document', group: 'Tài liệu', x: 140, y: 130, details: 'Tài liệu chuẩn hóa ranh giới dịch vụ được phê duyệt.' }
]

export const INITIAL_GRAPH_EDGES: GraphEdge[] = [
  { source: '1', target: '2', relation: 'ĐIỀU_HƯỚNG_BỞI' },
  { source: '2', target: '3', relation: 'XÁC_THỰC_QUA' },
  { source: '2', target: '4', relation: 'TRUY_VẤN_AI' },
  { source: '4', target: '5', relation: 'LƯU_TRỮ_ĐỒ_THỊ' },
  { source: '6', target: '1', relation: 'CHỦ_TRÌ_THIẾT_KẾ' },
  { source: '7', target: '2', relation: 'PHÁT_TRIỂN_CHÍNH' },
  { source: '7', target: '4', relation: 'TÍCH_HỢP' },
  { source: '8', target: '1', relation: 'MÔ_TẢ_HỆ_THỐNG' }
]

export const INITIAL_LEADERBOARD: LeaderboardMember[] = [
  {
    rank: 1,
    name: 'Trần Hoàng Nam',
    role: 'Tech Lead',
    department: 'Khối Kỹ thuật',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80',
    points: 4890,
    coins: 2450,
    acceptedAnswers: 32,
    badge: 'Chuyên gia Bạch Kim'
  },
  {
    rank: 2,
    name: 'Võ Thành Luân',
    role: 'Kỹ sư Hệ thống',
    department: 'Khối Kỹ thuật',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
    points: 3420,
    coins: 1250,
    acceptedAnswers: 18,
    badge: 'Chuyên gia Vàng'
  },
  {
    rank: 3,
    name: 'Lê Phương Thảo',
    role: 'Trưởng ban Nhân sự',
    department: 'Nhân sự & Văn hóa',
    avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120&auto=format&fit=crop&q=80',
    points: 2980,
    coins: 1100,
    acceptedAnswers: 15,
    badge: 'Đại sứ Tri thức'
  },
  {
    rank: 4,
    name: 'Phạm Quốc Bảo',
    role: 'DevOps Specialist',
    department: 'Khối Kỹ thuật',
    avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=120&auto=format&fit=crop&q=80',
    points: 2150,
    coins: 820,
    acceptedAnswers: 11,
    badge: 'Thành viên Tích cực'
  },
  {
    rank: 5,
    name: 'Nguyễn Minh Trang',
    role: 'Product Lead',
    department: 'Ban Sản phẩm',
    avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=120&auto=format&fit=crop&q=80',
    points: 1890,
    coins: 750,
    acceptedAnswers: 9,
    badge: 'Người chia sẻ'
  }
]

export const INITIAL_MODERATION_ITEMS: ModerationItem[] = [
  {
    id: 'mod-1',
    type: 'post',
    author: 'Nguyễn Văn Hùng',
    authorDept: 'Kinh doanh',
    content: 'Cần nhượng lại voucher giảm giá nghỉ dưỡng 5 sao cá nhân giá rẻ, liên hệ zalo 0987xxx...',
    reason: 'Đăng tin rao vặt cá nhân sai kênh quy định nội bộ',
    flaggedBy: 'AI Scanner',
    flaggedAt: '35 phút trước',
    status: 'pending'
  },
  {
    id: 'mod-2',
    type: 'comment',
    author: 'Đỗ Tuấn Kiệt',
    authorDept: 'Chăm sóc khách hàng',
    content: 'Tại sao việc bàn giao ca trực lại chậm trễ vô lý như vậy, quản lý làm việc quá tắc trách!',
    reason: 'Ngôn từ gay gắt vi phạm tiêu chuẩn giao tiếp văn minh',
    flaggedBy: 'Người dùng báo cáo',
    flaggedAt: '2 giờ trước',
    status: 'pending'
  }
]
