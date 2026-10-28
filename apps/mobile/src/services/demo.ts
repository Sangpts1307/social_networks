import type { Post, User, KnowledgeItem } from "../types";
export const demoUser: User = {
  id: "demo-user",
  organization_id: "demo",
  email: "demo@openhello.local",
  display_name: "Sang Phạm",
  must_change_password: false,
};
export const demoPosts: Post[] = [
  {
    id: "demo-1",
    channel_id: "demo",
    author_user_id: "Đội ngũ OpenHello",
    title: "Một nơi để kết nối. Cùng nhau tiến xa.",
    body_plaintext:
      "Chào mừng bạn đến với không gian làm việc chung. Chia sẻ điều bạn đang làm, đặt câu hỏi và tìm những ý tưởng mới từ đồng nghiệp. ✨",
    published_at: "2026-10-19T02:00:00Z",
    reaction_count: 24,
    comment_count: 0,
    my_reaction: null,
    is_saved: false,
  },
  {
    id: "demo-2",
    channel_id: "demo",
    author_user_id: "Cộng đồng học tập",
    title: "Hôm nay bạn học được điều gì mới?",
    body_plaintext:
      "Một bài viết hay, một cách giải quyết vấn đề, hay một mẹo nhỏ cũng có thể giúp đồng đội. Hãy chia sẻ câu chuyện của bạn!",
    published_at: "2026-10-19T01:00:00Z",
    reaction_count: 12,
    comment_count: 0,
    my_reaction: null,
    is_saved: false,
  },
];
export const demoLibrary: KnowledgeItem[] = [
  {
    id: "guide",
    title: "Bắt đầu hành trình tại OpenHello",
    summary: "Tìm hiểu văn hóa, công cụ và cách kết nối với đội ngũ.",
    topic_name: "Onboarding",
    category_name: "Cẩm nang",
  },
  {
    id: "sharing",
    title: "Chia sẻ tri thức, nhân đôi giá trị",
    summary: "Những thói quen nhỏ giúp cả đội cùng phát triển.",
    topic_name: "Phát triển bản thân",
    category_name: "Kỹ năng",
  },
];
