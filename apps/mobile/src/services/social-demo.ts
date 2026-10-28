import type { Post, Channel, Comment, Member } from "../types";
export const members: Record<string, Member> = {
  "demo-user": {
    name: "Sang Phạm",
    avatar:
      "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80",
    role: "Lead Developer · Ban Công nghệ",
    initials: "SP",
    color: "#1877f2",
  },
  "demo-linh": {
    name: "Lê Thị Mai Phương",
    avatar:
      "https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&auto=format&fit=crop&q=80",
    role: "HR & Văn hóa Doanh nghiệp",
    initials: "MP",
    color: "#0891b2",
  },
  "demo-duc": {
    name: "Võ Thành Luân",
    avatar:
      "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80",
    role: "Senior Backend & AI Specialist",
    initials: "TL",
    color: "#d97706",
  },
  "demo-ha": {
    name: "Phạm Phát",
    avatar:
      "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&auto=format&fit=crop&q=80",
    role: "Solutions Architect",
    initials: "PP",
    color: "#db2777",
  },
};
export const socialChannels: Channel[] = [
  {
    id: "general",
    name: "Toàn công ty",
    description:
      "Thông tin chung, câu chuyện đội ngũ và những điều đáng chia sẻ.",
    membership_policy: "open",
  },
  {
    id: "tech",
    name: "Tech & Architecture",
    description:
      "Cùng trao đổi về công nghệ, thiết kế và những sản phẩm tốt hơn.",
    membership_policy: "open",
  },
  {
    id: "life",
    name: "Đời sống văn phòng",
    description: "Một tách cà phê, một lời cảm ơn, một kết nối mới.",
    membership_policy: "open",
  },
];
socialChannels.push(
  {
    id: "design",
    name: "Thiết kế & Trải nghiệm",
    description:
      "Chia sẻ ý tưởng, góp ý giao diện và cùng tạo nên sản phẩm tốt hơn.",
    membership_policy: "open",
  },
  {
    id: "culture",
    name: "Văn hóa & Kết nối",
    description:
      "Câu chuyện đồng nghiệp, hoạt động đội ngũ và những lời cảm ơn.",
    membership_policy: "open",
  },
);
export function initialPosts(): Post[] {
  const now = Date.now();
  return [
    {
      id: "social-welcome",
      demo_image_url:
        "https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&auto=format&fit=crop&q=80",
      channel_id: "general",
      author_user_id: "demo-linh",
      title: "Chào mừng thành viên mới! 👋",
      body_plaintext: `Chào mừng các bạn tân binh gia nhập đại gia đình OpenHello trong tuần này! Chúc mọi người có những trải nghiệm làm việc năng động, chia sẻ kiến thức và cùng nhau chinh phục các mục tiêu mới.`,
      published_at: new Date(now - 45 * 60000).toISOString(),
      reaction_count: 28,
      comment_count: 3,
      my_reaction: null,
      is_saved: false,
      post_type: "announcement",
    },
    {
      id: "social-tech",
      demo_image_url:
        "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&auto=format&fit=crop&q=80",
      channel_id: "tech",
      author_user_id: "demo-duc",
      title: null,
      body_plaintext: `Hôm nay nhóm AI Knowledge Graph đã kết nối thành công pipeline trích xuất tri thức tự động từ tài liệu PDF vào cơ sở dữ liệu đồ thị Neo4j. Các thành viên có thể đặt câu hỏi tại Base Square để AI tự động đối chiếu ngữ nghĩa!`,
      published_at: new Date(now - 2 * 3600000).toISOString(),
      reaction_count: 16,
      comment_count: 0,
      my_reaction: "like",
      is_saved: false,
      post_type: "update",
    },
    {
      id: "social-mine",
      channel_id: "life",
      author_user_id: "demo-user",
      title: null,
      body_plaintext: `Một lời cảm ơn tới team đã góp ý cho bản thiết kế tuần này 💜

Những góc nhìn khác nhau giúp mình nhìn vấn đề rõ hơn. Rất vui vì được làm việc cùng mọi người!`,
      published_at: new Date(now - 4 * 3600000).toISOString(),
      reaction_count: 9,
      comment_count: 0,
      my_reaction: null,
      is_saved: true,
      post_type: "update",
    },
    {
      id: "social-learning",
      channel_id: "tech",
      author_user_id: "demo-ha",
      title: "Chia sẻ nhỏ: phản hồi sao cho hữu ích?",
      body_plaintext: `Thay vì chỉ nói “chưa ổn”, hãy thử chia sẻ điều bạn quan sát được, tác động của nó và một gợi ý cụ thể.

Mọi người có mẹo nào để buổi review hiệu quả hơn không?`,
      published_at: new Date(now - 22 * 3600000).toISOString(),
      reaction_count: 21,
      comment_count: 0,
      my_reaction: null,
      is_saved: false,
      post_type: "news",
    },
  ];
}
export function initialComments(): Record<string, Comment[]> {
  const date = new Date().toISOString();
  return {
    "social-welcome": [
      {
        id: "comment-1",
        post_id: "social-welcome",
        parent_comment_id: null,
        author_user_id: "demo-duc",
        body_plaintext: "Chào mừng các bạn đến với team OpenHello! 👋",
        created_at: date,
      },
      {
        id: "comment-2",
        post_id: "social-welcome",
        parent_comment_id: "comment-1",
        author_user_id: "demo-linh",
        body_plaintext: "Cảm ơn team tech đã đồng hành cùng các bạn mới nhé!",
        created_at: date,
      },
      {
        id: "comment-3",
        post_id: "social-welcome",
        parent_comment_id: null,
        author_user_id: "demo-ha",
        body_plaintext:
          "Chúc các bạn có một hành trình thật nhiều trải nghiệm!",
        created_at: date,
      },
    ],
  };
}
