import { ref } from 'vue'
import { MOCK_SOCIAL_POSTS } from '../../mock/mockData.js'

// Keep locally composed posts visible when navigating between the feed and personal page.
export const insidePosts = ref<any[]>([
  ...MOCK_SOCIAL_POSTS,
  { id: 'channel-ai-1', author: 'Võ Thành Luân', role: 'AI Specialist', channel: 'AI & Knowledge Graph', time: 'Hôm nay', avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80', content: 'Nhóm đã cập nhật luồng trích xuất tri thức từ tài liệu. Mời mọi người thử đặt câu hỏi và góp ý về chất lượng câu trả lời.', image: 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800&auto=format&fit=crop&q=80', likes: 16, commentsCount: 2, isLiked: false, comments: [] },
  { id: 'channel-hr-1', author: 'Lê Thị Mai Phương', role: 'HR & Văn hóa Doanh nghiệp', channel: 'HR & Văn hóa Doanh nghiệp', time: 'Hôm qua', avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=80&auto=format&fit=crop&q=80', content: 'Một số khoảnh khắc từ buổi chia sẻ nội bộ tuần này. Cảm ơn mọi người đã tham gia, đặt câu hỏi và chia sẻ kinh nghiệm với các nhóm khác!', image: 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=800&auto=format&fit=crop&q=80', likes: 31, commentsCount: 4, isLiked: false, comments: [] }
])

export function seedPersonalPosts(author: string) {
  if (!author || insidePosts.value.some(post => post.id === `personal-seed-1-${author}`)) return
  const avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80'
  insidePosts.value.push(
    { id: `personal-seed-1-${author}`, author, role: 'Thành viên', channel: 'Tech & Architecture', time: 'Hôm qua', avatar, content: 'Vừa hoàn thành bản cập nhật cho không gian làm việc chung. Mình đã tổng hợp các ghi chú triển khai và những điểm cần lưu ý để mọi người tiện theo dõi và góp ý.', likes: 12, commentsCount: 2, isLiked: false, image: '', comments: [] },
    { id: `personal-seed-2-${author}`, author, role: 'Thành viên', channel: 'Chia sẻ tri thức', time: '3 ngày trước', avatar, content: 'Một vài điều rút ra sau buổi chia sẻ nội bộ: tài liệu rõ ràng giúp cả nhóm trao đổi nhanh hơn, và những câu hỏi nhỏ thường mở ra cách làm tốt hơn.', likes: 18, commentsCount: 4, isLiked: false, image: '', comments: [] },
    { id: `personal-seed-3-${author}`, author, role: 'Thành viên', channel: 'Toàn công ty', time: 'Tuần trước', avatar, content: 'Cảm ơn mọi người đã hỗ trợ trong tuần vừa qua. Rất vui khi thấy các nhóm cùng cập nhật tiến độ và chia sẻ kinh nghiệm trên Inside!', likes: 27, commentsCount: 3, isLiked: false, image: '', comments: [] }
  )
}
