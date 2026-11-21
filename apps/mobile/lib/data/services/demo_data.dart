import '../models/user_model.dart';
import '../models/post_model.dart';
import '../models/channel_model.dart';
import '../models/comment_model.dart';
import '../models/story_model.dart';
import '../models/chat_model.dart';
import '../models/knowledge_model.dart';
import '../models/media_attachment_model.dart';

class DemoData {
  static const UserModel demoUser = UserModel(
    id: 'demo-user',
    organizationId: 'demo',
    email: 'demo@openhello.vn',
    displayName: 'Sang Phạm',
    mustChangePassword: false,
    avatarUrl: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
    role: 'Lead Developer · Ban Công nghệ',
  );

  static final Map<String, UserModel> demoMembers = {
    'demo-user': demoUser,
    'demo-linh': const UserModel(
      id: 'demo-linh',
      email: 'linh@openhello.vn',
      displayName: 'Lê Thị Mai Phương',
      avatarUrl: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80',
      role: 'HR & Văn hóa Doanh nghiệp',
    ),
    'demo-duc': const UserModel(
      id: 'demo-duc',
      email: 'luan@openhello.vn',
      displayName: 'Võ Thành Luân',
      avatarUrl: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
      role: 'Senior Backend & AI Specialist',
    ),
    'demo-ha': const UserModel(
      id: 'demo-ha',
      email: 'phat@openhello.vn',
      displayName: 'Phạm Phát',
      avatarUrl: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&auto=format&fit=crop&q=80',
      role: 'Solutions Architect',
    ),
  };

  static List<ChannelModel> get channels => [
    const ChannelModel(
      id: 'general',
      name: 'Toàn công ty',
      description: 'Thông tin chung, câu chuyện đội ngũ và những điều đáng chia sẻ.',
    ),
    const ChannelModel(
      id: 'tech',
      name: 'Tech & Architecture',
      description: 'Cùng trao đổi về công nghệ, thiết kế và những sản phẩm tốt hơn.',
    ),
    const ChannelModel(
      id: 'life',
      name: 'Đời sống văn phòng',
      description: 'Một tách cà phê, một lời cảm ơn, một kết nối mới.',
    ),
    const ChannelModel(
      id: 'design',
      name: 'Thiết kế & Trải nghiệm',
      description: 'Chia sẻ ý tưởng, góp ý giao diện và cùng tạo nên sản phẩm tốt hơn.',
    ),
    const ChannelModel(
      id: 'culture',
      name: 'Văn hóa & Kết nối',
      description: 'Câu chuyện đồng nghiệp, hoạt động đội ngũ và những lời cảm ơn.',
    ),
  ];

  static List<PostModel> get initialPosts {
    final now = DateTime.now();
    return [
      PostModel(
        id: 'social-welcome',
        channelId: 'general',
        authorUserId: 'demo-linh',
        authorName: 'Lê Thị Mai Phương',
        authorAvatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80',
        authorRole: 'HR & Văn hóa Doanh nghiệp',
        title: 'Chào mừng thành viên mới! 👋',
        bodyPlaintext: 'Chào mừng các bạn tân binh gia nhập đại gia đình Openhello trong tuần này! Chúc mọi người có những trải nghiệm làm việc năng động, chia sẻ kiến thức và cùng nhau chinh phục các mục tiêu mới.',
        publishedAt: now.subtract(const Duration(minutes: 45)).toIso8601String(),
        reactionCount: 28,
        commentCount: 3,
        postType: 'announcement',
        imageUrl: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&auto=format&fit=crop&q=80',
      ),
      PostModel(
        id: 'social-tech',
        channelId: 'tech',
        authorUserId: 'demo-duc',
        authorName: 'Võ Thành Luân',
        authorAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
        authorRole: 'Senior Backend & AI Specialist',
        bodyPlaintext: 'Hôm nay nhóm AI Knowledge Graph đã kết nối thành công pipeline trích xuất tri thức tự động từ tài liệu PDF vào cơ sở dữ liệu đồ thị Neo4j. Các thành viên có thể đặt câu hỏi tại Base Square để AI tự động đối chiếu ngữ nghĩa!',
        publishedAt: now.subtract(const Duration(hours: 2)).toIso8601String(),
        reactionCount: 15,
        commentCount: 2,
        postType: 'update',
        imageUrl: 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&auto=format&fit=crop&q=80',
      ),
      PostModel(
        id: 'social-arch',
        channelId: 'tech',
        authorUserId: 'demo-ha',
        authorName: 'Phạm Phát',
        authorAvatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&auto=format&fit=crop&q=80',
        authorRole: 'Solutions Architect',
        bodyPlaintext: 'Chuẩn bị triển khai bản cập nhật Gateway API với tính năng rate-limiting và auto token rotation. Các service backend sẽ giảm thiểu tối đa tình trạng gián đoạn!',
        publishedAt: now.subtract(const Duration(hours: 4)).toIso8601String(),
        reactionCount: 9,
        commentCount: 1,
        postType: 'news',
      ),
    ];
  }

  static Map<String, List<CommentModel>> get initialComments {
    final now = DateTime.now();
    return {
      'social-welcome': [
        CommentModel(
          id: 'c1',
          postId: 'social-welcome',
          authorUserId: 'demo-user',
          authorName: 'Sang Phạm',
          authorAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
          bodyPlaintext: 'Chào mừng các bạn mới đến với team công nghệ!',
          createdAt: now.subtract(const Duration(minutes: 30)).toIso8601String(),
        ),
        CommentModel(
          id: 'c2',
          postId: 'social-welcome',
          authorUserId: 'demo-duc',
          authorName: 'Võ Thành Luân',
          authorAvatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80',
          bodyPlaintext: 'Rất vui được đồng hành cùng mọi người.',
          createdAt: now.subtract(const Duration(minutes: 20)).toIso8601String(),
        ),
      ],
      'social-tech': [
        CommentModel(
          id: 'c3',
          postId: 'social-tech',
          authorUserId: 'demo-ha',
          authorName: 'Phạm Phát',
          authorAvatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&auto=format&fit=crop&q=80',
          bodyPlaintext: 'Tốc độ query qua Neo4j phản hồi cực nhanh!',
          createdAt: now.subtract(const Duration(hours: 1)).toIso8601String(),
        ),
      ],
    };
  }

  static List<StoryModel> get stories {
    final now = DateTime.now();
    return [
      StoryModel(
        id: 'sample-story',
        authorUserId: 'demo-linh',
        authorName: 'Lê Thị Mai Phương',
        authorAvatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80',
        body: 'Chào mừng thành viên mới gia nhập đại gia đình Openhello! 🎉',
        background: '#1877F2',
        media: const [
          MediaAttachment(
            id: 'sample-story-photo',
            uri: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&auto=format&fit=crop&q=80',
            type: 'image',
          ),
        ],
        createdAt: now.toIso8601String(),
        expiresAt: now.add(const Duration(hours: 24)).toIso8601String(),
      ),
    ];
  }

  static List<KnowledgeItem> get libraryItems => const [
    KnowledgeItem(
      id: 'guide',
      title: 'Bắt đầu hành trình tại Openhello',
      summary: 'Tìm hiểu văn hóa, công cụ và cách kết nối với đội ngũ đồng nghiệp.',
      topicName: 'Onboarding',
      categoryName: 'Cẩm nang',
    ),
    KnowledgeItem(
      id: 'sharing',
      title: 'Chia sẻ tri thức, nhân đôi giá trị',
      summary: 'Những thói quen nhỏ giúp cả đội cùng học hỏi và phát triển bền vững.',
      topicName: 'Phát triển bản thân',
      categoryName: 'Kỹ năng',
    ),
    KnowledgeItem(
      id: 'arch-doc',
      title: 'Kiến trúc Microservices & AI Knowledge Graph',
      summary: 'Tài liệu hướng dẫn kết nối Gateway, Neo4j và mô hình AI LLM embedding.',
      topicName: 'Backend & AI',
      categoryName: 'Kỹ thuật',
    ),
  ];

  static List<ConversationDto> get conversations => [
    ConversationDto(
      id: 'luan',
      type: 'direct',
      title: 'Võ Thành Luân',
      directKey: 'demo-user:demo-duc',
      createdByUserId: 'demo-duc',
      lastMessage: ChatMessageDto(
        id: 'm3',
        conversationId: 'luan',
        senderUserId: 'demo-duc',
        bodyPlaintext: 'Đồ thị tri thức đã đồng bộ thêm 12 nodes chuyên môn mới rồi nhé!',
        sentAt: DateTime.now().subtract(const Duration(minutes: 5)).toIso8601String(),
      ),
      unreadCount: 1,
    ),
    ConversationDto(
      id: 'phat',
      type: 'direct',
      title: 'Phạm Phát',
      directKey: 'demo-user:demo-ha',
      createdByUserId: 'demo-ha',
      lastMessage: ChatMessageDto(
        id: 'm1',
        conversationId: 'phat',
        senderUserId: 'demo-ha',
        bodyPlaintext: 'Đã hoàn tất cấu hình Gateway proxy.',
        sentAt: DateTime.now().subtract(const Duration(hours: 2)).toIso8601String(),
      ),
    ),
    ConversationDto(
      id: 'phuong',
      type: 'direct',
      title: 'Lê Thị Mai Phương',
      directKey: 'demo-user:demo-linh',
      createdByUserId: 'demo-linh',
      lastMessage: ChatMessageDto(
        id: 'm2',
        conversationId: 'phuong',
        senderUserId: 'demo-linh',
        bodyPlaintext: 'Nhớ gửi danh sách nhân sự tham gia hội thảo tuần này nhé.',
        sentAt: DateTime.now().subtract(const Duration(days: 1)).toIso8601String(),
      ),
    ),
  ];

  static Map<String, List<ChatMessageDto>> get conversationMessages => {
    'luan': [
      ChatMessageDto(
        id: 'm1',
        conversationId: 'luan',
        senderUserId: 'demo-duc',
        bodyPlaintext: 'Chào Sang! Bạn có tài liệu mô tả kiến trúc Knowledge Graph mới cập nhật không?',
        sentAt: DateTime.now().subtract(const Duration(minutes: 20)).toIso8601String(),
      ),
      ChatMessageDto(
        id: 'm2',
        conversationId: 'luan',
        senderUserId: 'demo-user',
        bodyPlaintext: 'Tài liệu đã được lưu trong Base Library mục Backend/AI nhé.',
        sentAt: DateTime.now().subtract(const Duration(minutes: 10)).toIso8601String(),
      ),
      ChatMessageDto(
        id: 'm3',
        conversationId: 'luan',
        senderUserId: 'demo-duc',
        bodyPlaintext: 'Đồ thị tri thức đã đồng bộ thêm 12 nodes chuyên môn mới rồi nhé!',
        sentAt: DateTime.now().subtract(const Duration(minutes: 5)).toIso8601String(),
      ),
    ],
    'phat': [
      ChatMessageDto(
        id: 'm1',
        conversationId: 'phat',
        senderUserId: 'demo-ha',
        bodyPlaintext: 'Đã hoàn tất cấu hình Gateway proxy.',
        sentAt: DateTime.now().subtract(const Duration(hours: 2)).toIso8601String(),
      ),
    ],
    'phuong': [
      ChatMessageDto(
        id: 'm1',
        conversationId: 'phuong',
        senderUserId: 'demo-linh',
        bodyPlaintext: 'Nhớ gửi danh sách nhân sự tham gia hội thảo tuần này nhé.',
        sentAt: DateTime.now().subtract(const Duration(days: 1)).toIso8601String(),
      ),
    ],
  };
}
