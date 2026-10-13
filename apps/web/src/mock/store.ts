import { reactive } from 'vue'
import {
  INITIAL_CURRENT_USER,
  INITIAL_POSTS,
  INITIAL_CONVERSATIONS,
  INITIAL_QUESTIONS,
  INITIAL_DOCUMENTS,
  INITIAL_GRAPH_NODES,
  INITIAL_GRAPH_EDGES,
  INITIAL_LEADERBOARD,
  INITIAL_MODERATION_ITEMS,
  type UserProfile,
  type SocialPost,
  type ChatConversation,
  type QaQuestion,
  type LibraryDocument,
  type GraphNode,
  type GraphEdge,
  type LeaderboardMember,
  type ModerationItem
} from './data'

export type AppScreen =
  | 'portal'
  | 'social'
  | 'messenger'
  | 'qa'
  | 'library'
  | 'graph'
  | 'search'
  | 'leaderboard'
  | 'profile'
  | 'admin'

export interface AppNotification {
  id: string
  title: string
  time: string
  isRead: boolean
  screenTarget?: AppScreen
}

export const store = reactive({
  currentScreen: 'portal' as AppScreen,
  currentUser: { ...INITIAL_CURRENT_USER } as UserProfile,
  isLoggedIn: true,
  posts: [...INITIAL_POSTS] as SocialPost[],
  conversations: [...INITIAL_CONVERSATIONS] as ChatConversation[],
  activeConversationId: 'conv-1',
  questions: [...INITIAL_QUESTIONS] as QaQuestion[],
  selectedQuestionId: 'q-1',
  documents: [...INITIAL_DOCUMENTS] as LibraryDocument[],
  graphNodes: [...INITIAL_GRAPH_NODES] as GraphNode[],
  graphEdges: [...INITIAL_GRAPH_EDGES] as GraphEdge[],
  leaderboard: [...INITIAL_LEADERBOARD] as LeaderboardMember[],
  moderationItems: [...INITIAL_MODERATION_ITEMS] as ModerationItem[],
  notifications: [
    { id: 'notif-1', title: 'Hệ thống AI Knowledge Graph vừa được cập nhật bản 2.1', time: '10 phút trước', isRead: false, screenTarget: 'graph' },
    { id: 'notif-2', title: 'Trần Hoàng Nam vừa trả lời câu hỏi Q&A của bạn', time: '1 giờ trước', isRead: false, screenTarget: 'qa' },
    { id: 'notif-3', title: 'Bạn nhận được +50 xu thưởng nhờ câu trả lời hữu ích', time: 'Hôm qua', isRead: true, screenTarget: 'leaderboard' },
    { id: 'notif-4', title: 'Thông báo lịch làm việc kết hợp tháng mới', time: '2 ngày trước', isRead: true, screenTarget: 'social' }
  ] as AppNotification[],

  // Actions
  setScreen(screen: AppScreen) {
    this.currentScreen = screen
    window.scrollTo({ top: 0, behavior: 'smooth' })
  },

  toggleLikePost(postId: string) {
    const post = this.posts.find(p => p.id === postId)
    if (!post) return
    post.isLiked = !post.isLiked
    post.likes += post.isLiked ? 1 : -1
  },

  toggleSavePost(postId: string) {
    const post = this.posts.find(p => p.id === postId)
    if (!post) return
    post.isSaved = !post.isSaved
  },

  addComment(postId: string, text: string) {
    if (!text.trim()) return
    const post = this.posts.find(p => p.id === postId)
    if (!post) return
    post.comments.push({
      id: `c-${Date.now()}`,
      author: this.currentUser.name,
      avatar: this.currentUser.avatar,
      content: text.trim(),
      createdAt: 'Vừa xong'
    })
  },

  createPost(content: string, channel: string, imageUrl?: string) {
    if (!content.trim()) return
    this.posts.unshift({
      id: `p-${Date.now()}`,
      author: this.currentUser.name,
      authorRole: this.currentUser.roleTitle,
      department: this.currentUser.department,
      avatar: this.currentUser.avatar,
      content: content.trim(),
      imageUrl: imageUrl?.trim() || undefined,
      channel,
      createdAt: 'Vừa xong',
      likes: 0,
      isLiked: false,
      isSaved: false,
      comments: []
    })
    this.currentUser.postsCount++
  },

  sendMessage(conversationId: string, text: string) {
    if (!text.trim()) return
    const conv = this.conversations.find(c => c.id === conversationId)
    if (!conv) return
    const msg = {
      id: `m-${Date.now()}`,
      senderId: this.currentUser.id,
      senderName: this.currentUser.name,
      text: text.trim(),
      time: 'Vừa xong',
      isMe: true
    }
    conv.messages.push(msg)
    conv.lastMessage = text.trim()
    conv.lastTime = 'Vừa xong'
  },

  voteQuestion(questionId: string, delta: number) {
    const q = this.questions.find(item => item.id === questionId)
    if (!q) return
    q.votes += delta
  },

  addQuestion(title: string, content: string, topic: string, category: string) {
    if (!title.trim() || !content.trim()) return
    const newQ: QaQuestion = {
      id: `q-${Date.now()}`,
      title: title.trim(),
      content: content.trim(),
      topic,
      category,
      author: this.currentUser.name,
      authorRole: this.currentUser.roleTitle,
      avatar: this.currentUser.avatar,
      createdAt: 'Vừa xong',
      votes: 0,
      answersCount: 0,
      views: 1,
      answers: []
    }
    this.questions.unshift(newQ)
    this.selectedQuestionId = newQ.id
  },

  addAnswer(questionId: string, content: string) {
    if (!content.trim()) return
    const q = this.questions.find(item => item.id === questionId)
    if (!q) return
    q.answers.push({
      id: `ans-${Date.now()}`,
      author: this.currentUser.name,
      authorRole: this.currentUser.roleTitle,
      avatar: this.currentUser.avatar,
      content: content.trim(),
      createdAt: 'Vừa xong',
      votes: 0,
      isAccepted: false
    })
    q.answersCount = q.answers.length
    this.currentUser.answersCount++
    this.currentUser.points += 20
    this.currentUser.coins += 10
  },

  addDocument(title: string, category: string, department: string, fileType: 'pdf' | 'docx') {
    if (!title.trim()) return
    this.documents.unshift({
      id: `doc-${Date.now()}`,
      title: title.trim(),
      category,
      department,
      fileType,
      fileSize: '1.5 MB',
      author: this.currentUser.name,
      createdAt: 'Hôm nay',
      downloads: 0,
      isAiIndexed: true,
      summary: 'Tài liệu vừa được tải lên và lập chỉ mục vào đồ thị tri thức AI.'
    })
  },

  moderateItem(id: string, action: 'approved' | 'hidden' | 'deleted') {
    const item = this.moderationItems.find(m => m.id === id)
    if (!item) return
    item.status = action
  },

  markNotificationsRead() {
    this.notifications.forEach(n => { n.isRead = true })
  },

  toggleRole() {
    this.currentUser.role = this.currentUser.role === 'admin' ? 'employee' : 'admin'
    this.currentUser.roleTitle = this.currentUser.role === 'admin' ? 'Kỹ sư Hệ thống & AI (Admin)' : 'Nhân viên Kỹ thuật'
  }
})
