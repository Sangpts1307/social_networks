<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import type { AuthUser } from '../../api/auth'
import { profileApi } from '../../api/profile'
import { refreshUnreadChatCount, unreadChatCount } from '../../api/chatInbox'
import {
  socialApi,
  type DirectoryUserDto,
  type SocialChannelDto,
  type SocialCommentDto,
  type SocialPostDto,
  type SocialAttachmentDto,
} from '../../api/social'
import { companyPages, insideChannels, type CompanySection } from './insideContent'
import { insidePosts, seedPersonalPosts } from './insidePosts'
import InsideProfileView from './InsideProfileView.vue'

interface ChannelItem {
  id: string
  code?: string
  name: string
  image: string
  cover: string
  description: string
  visibility?: 'organization' | 'members'
  membershipPolicy?: 'open' | 'managed'
  ownerUserId?: string
  isMember?: boolean
}

const DEFAULT_AVATAR = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80'
const DEFAULT_COVER = 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200&auto=format&fit=crop&q=85'

const props = defineProps<{
  user: AuthUser
  token: string
  page?: 'feed' | 'profile'
}>()

const emit = defineEmits<{
  (e: 'back'): void
  (e: 'select-app', appId: string): void
  (e: 'logout'): void
}>()

const apiPosts = ref<any[] | null>([])
const posts = computed(() => apiPosts.value ?? insidePosts.value)
const apiConnected = ref(false)
const socialBusy = ref(false)
const socialError = ref('')
const attachmentPreview = ref<{ name: string; url: string } | null>(null)

async function openAttachment(postId: string, attachment: SocialAttachmentDto) {
  try {
    const { data } = await socialApi.attachmentUrl(props.token, postId, attachment.id)
    if (attachment.mime_type === 'application/pdf') {
      attachmentPreview.value = { name: attachment.original_filename, url: data.url }
    } else {
      window.open(data.url, '_blank', 'noopener,noreferrer')
    }
  } catch (cause) {
    socialError.value = cause instanceof Error ? cause.message : 'Không thể mở tệp đính kèm.'
  }
}
const isComposerExpanded = ref(false)
const newTitle = ref('')
const newContent = ref('')
const newPostType = ref<'update' | 'announcement' | 'news' | 'event'>('update')
const pinNewPost = ref(false)
const eventLocation = ref('')
const showCreateChannelModal = ref(false)
const newChannelName = ref('')
const newChannelCode = ref('')
const newChannelDesc = ref('')
const channels = ref<ChannelItem[]>([])
const newChannel = ref<string>(channels.value[0]?.name || 'Tech & Architecture')
const jobTitle = ref('')
const followingCount = ref<number | null>(null)
const followersCount = ref<number | null>(null)
const activeFeed = ref<'home' | 'saved'>('home')
const selectedChannel = ref('')
const channelTab = ref<'posts' | 'members' | 'media'>('posts')
const currentChannel = computed(() =>
  channels.value.find(channel => channel.name === selectedChannel.value || channel.id === selectedChannel.value),
)
const channelMap = computed<Record<string, string>>(() =>
  Object.fromEntries(channels.value.map(c => [c.id, c.name])),
)
const directoryUsers = ref<Record<string, DirectoryUserDto>>({})
const apiChannelMembers = ref<Array<{ id: string; name: string; role: string; avatar: string }> | null>(null)
const channelMembers = computed(() => apiChannelMembers.value ?? [])
const companySection = ref<CompanySection | ''>(readCompanySection())
const currentCompanyPage = computed(() => companySection.value ? companyPages[companySection.value] : null)
const savedPosts = ref<string[]>([])
const editingPostId = ref('')
const editingTitle = ref('')
const editingContent = ref('')
const commentPostId = ref('')
const commentDrafts = ref<Record<string, string>>({})
const replyingToCommentId = ref('')
const replyDrafts = ref<Record<string, string>>({})
const shareFeedback = ref('')
const messengerOpen = ref(false)
const openConversationIds = ref<string[]>([])
const chatDrafts = ref<Record<string, string>>({})
const notificationOpen = ref(false)
type InboxItem = {
  id: string; title: string; body: string; created_at: string; read_at: string | null
  notification_type: string; source_type?: string; source_id?: string
  payload_json?: { actor_user_id?: string } | null
}
const notifications = ref<InboxItem[]>([])
const notificationStyles: Record<string, [string, string]> = {
  post_like: ['thumb_up', 'bg-blue-600'],
  post_save: ['bookmark', 'bg-violet-600'],
  post_share: ['share', 'bg-cyan-700'],
  comment: ['chat_bubble', 'bg-emerald-600'],
  announcement: ['campaign', 'bg-orange-600'],
  profile_update_request: ['badge', 'bg-indigo-600'],
}
function notificationAvatar(item: InboxItem) {
  if (item.source_type === 'channel') return channels.value.find(channel => channel.id === item.source_id)?.image
  const actor = item.payload_json?.actor_user_id
  if (actor) return resolveAuthor(actor).avatar
}
const notificationLoading = ref(false)
const notificationError = ref('')
const unreadCount = ref(0)
let chatCountTimer: ReturnType<typeof setInterval> | undefined
const searchQuery = ref('')
const contactSearch = ref('')
const conversations = ref<any[]>([])
const openConversations = computed(() => openConversationIds.value
  .map(id => conversations.value.find(conversation => conversation.id === id))
  .filter((conversation): conversation is any => Boolean(conversation)))
const commentPost = computed(() => posts.value.find(post => post.id === commentPostId.value))
const canPublishAnnouncement = computed(() =>
  (props.user.permissions ?? []).includes('social.announcement.publish'),
)
const canPublishNews = computed(() =>
  (props.user.permissions ?? []).includes('social.news.publish'),
)
const canPinPosts = computed(() =>
  (props.user.permissions ?? []).includes('social.posts.pin'),
)
const canManageChannels = computed(() =>
  (props.user.permissions ?? []).includes('social.channels.manage'),
)

const canChoosePostType = computed(() => canPublishAnnouncement.value || canPublishNews.value)

// Channels limit and Facebook-style expand
const visibleChannelLimit = ref(3)
function toggleExpandChannels() {
  if (visibleChannelLimit.value <= 3) {
    visibleChannelLimit.value = 8
  } else {
    visibleChannelLimit.value = 3
  }
}
const displayedChannels = computed(() => channels.value.slice(0, visibleChannelLimit.value))

// Delete Post Confirmation Modal
const showDeleteConfirmModal = ref(false)
const postToDelete = ref<any | null>(null)
function requestDeletePost(post: any) {
  postToDelete.value = post
  showDeleteConfirmModal.value = true
}
function cancelDeletePost() {
  postToDelete.value = null
  showDeleteConfirmModal.value = false
}
async function confirmDeletePost() {
  if (!postToDelete.value) return
  const target = postToDelete.value
  showDeleteConfirmModal.value = false
  await handleDeletePost(target)
  postToDelete.value = null
}

// Pin Post Modal with expiration date
const showPinModal = ref(false)
const postToPin = ref<any | null>(null)
const pinModalExpiresAt = ref('')
const newPostPinExpiresAt = ref('')

function openPinModal(post: any) {
  if (post.pinned) {
    togglePinPost(post)
  } else {
    postToPin.value = post
    pinModalExpiresAt.value = ''
    showPinModal.value = true
  }
}

async function confirmPinPost() {
  if (!postToPin.value) return
  const post = postToPin.value
  const expiresAt = pinModalExpiresAt.value ? new Date(pinModalExpiresAt.value).toISOString() : null
  showPinModal.value = false
  socialBusy.value = true
  socialError.value = ''
  try {
    if (apiConnected.value) {
      await socialApi.pinPost(props.token, post.id, true, expiresAt)
    }
    post.pinned = true
    post.pinExpiresAt = expiresAt
    shareFeedback.value = expiresAt
      ? `Đã ghim bài viết đến ${new Date(expiresAt).toLocaleDateString('vi-VN')}.`
      : 'Đã ghim bài viết lên đầu trang.'
    window.setTimeout(() => { shareFeedback.value = '' }, 3000)
  } catch (cause) {
    socialError.value = cause instanceof Error ? cause.message : 'Không thể ghim bài viết.'
  } finally {
    socialBusy.value = false
    postToPin.value = null
  }
}

// File / Image Attachment in Composer
const fileInputRef = ref<HTMLInputElement | null>(null)
const attachedFile = ref<File | null>(null)
const attachedImagePreview = ref<string>('')
const attachedFileName = ref<string>('')

function triggerFileInput() {
  fileInputRef.value?.click()
}

async function compressPostImage(file: File): Promise<string> {
  const image = await createImageBitmap(file)
  try {
    const canvas = document.createElement('canvas')
    const scale = Math.min(1, 1600 / Math.max(image.width, image.height))
    canvas.width = Math.max(1, Math.round(image.width * scale))
    canvas.height = Math.max(1, Math.round(image.height * scale))
    canvas.getContext('2d')?.drawImage(image, 0, 0, canvas.width, canvas.height)
    for (const quality of [0.85, 0.7, 0.5, 0.3]) {
      const dataUrl = canvas.toDataURL('image/jpeg', quality)
      if (dataUrl.length <= 350000) return dataUrl
    }
    throw new Error('Ảnh quá lớn. Vui lòng chọn ảnh nhỏ hơn để đăng bài.')
  } finally {
    image.close()
  }
}

async function handleFileSelected(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return
  attachedFile.value = file
  attachedFileName.value = file.name
  if (file.type.startsWith('image/')) {
    attachedImagePreview.value = ''
    try {
      const preview = await compressPostImage(file)
      if (attachedFile.value === file) attachedImagePreview.value = preview
    } catch (cause) {
      removeAttachedFile()
      socialError.value = cause instanceof Error ? cause.message : 'Không thể xử lý ảnh đính kèm.'
    }
  } else {
    attachedImagePreview.value = ''
  }
}

function removeAttachedFile() {
  attachedFile.value = null
  attachedImagePreview.value = ''
  attachedFileName.value = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
}

// Birthdays in month (Replaces upcoming events)
const currentMonth = computed(() => new Date().getMonth() + 1)
const birthdaysList = ref<Awaited<ReturnType<typeof socialApi.birthdays>>['data']>([])
const birthdayStatus = ref('Đang tải sinh nhật…')
watch(() => props.token, async (token, _, onCleanup) => {
  let active = true
  onCleanup(() => { active = false })
  birthdaysList.value = []
  birthdayStatus.value = 'Đang tải sinh nhật…'
  try {
    const { data } = await socialApi.birthdays(token, currentMonth.value)
    if (active) {
      birthdaysList.value = data
      birthdayStatus.value = data.length ? '' : 'Tháng này chưa có sinh nhật.'
    }
  } catch {
    if (active) birthdayStatus.value = 'Chưa tải được danh sách sinh nhật.'
  }
}, { immediate: true })

function isPostPinned(post: any): boolean {
  if (!post.pinned) return false
  if (post.pinExpiresAt) {
    return new Date(post.pinExpiresAt).getTime() > Date.now()
  }
  return true
}

const visiblePosts = computed(() => posts.value.filter((post: any) =>
  (activeFeed.value !== 'saved' || savedPosts.value.includes(post.id)) &&
  (!selectedChannel.value || post.channel === selectedChannel.value || post.channelId === selectedChannel.value)
  && (!searchQuery.value.trim() || `${post.title ?? ''} ${post.author} ${post.channel} ${post.content}`.toLocaleLowerCase('vi-VN').includes(searchQuery.value.trim().toLocaleLowerCase('vi-VN')))
).sort((left: any, right: any) => Number(isPostPinned(right)) - Number(isPostPinned(left))))
const filteredConversations = computed(() => conversations.value.filter((conversation: any) =>
  conversation.user.name.toLocaleLowerCase('vi-VN').includes(contactSearch.value.toLocaleLowerCase('vi-VN'))
))

const USER_AVATAR_FALLBACKS: Record<string, string> = {
  '30000000-0000-4000-8000-000000000001': 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000002': 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000003': 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000004': 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000005': 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000006': 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000007': 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000008': 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000009': 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000010': 'https://images.unsplash.com/photo-1567532939604-b6b5b0db2604?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000011': 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000012': 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000013': 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000014': 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000015': 'https://images.unsplash.com/photo-1573496799652-408c2ac9fe98?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000016': 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000017': 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000018': 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
  '30000000-0000-4000-8000-000000000019': 'https://images.unsplash.com/photo-1628157582853-a796fa650a6a?w=150&auto=format&fit=crop&q=80',
}

function resolveAuthor(userId: string) {
  const avatarFallback = USER_AVATAR_FALLBACKS[userId] || DEFAULT_AVATAR
  if (userId === props.user.id) {
    return {
      name: props.user.display_name,
      role: jobTitle.value || 'Thành viên công ty',
      avatar: (props.user as any).profile?.avatar_key || avatarFallback,
    }
  }
  const found = directoryUsers.value[userId]
  if (found) {
    return {
      name: found.display_name,
      role: found.job_title || found.primary_org_unit?.name || 'Thành viên công ty',
      avatar: found.avatar_key || avatarFallback,
    }
  }
  return {
    name: `Thành viên (${userId.slice(0, 8)})`,
    role: 'Thành viên công ty',
    avatar: avatarFallback,
  }
}

function formatTime(iso?: string | null): string {
  if (!iso) return 'Vừa xong'
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) return 'Vừa xong'
  return date.toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

function extractImage(dto: SocialPostDto): string {
  if (dto.image_url) return dto.image_url
  if (dto.body_markdown) {
    const mdMatch = dto.body_markdown.match(/!\[[^\]]*\]\((https?:\/\/[^\s)]+|data:image\/(?:png|jpeg|jpg|gif|webp);base64,[A-Za-z0-9+/=]+)\)/)
    if (mdMatch) return mdMatch[1]
  }
  return ''
}

function extractPostContent(dto: SocialPostDto): string {
  const body = dto.body_markdown || dto.body_plaintext || ''
  return body.replace(/!\[[^\]]*\]\((?:https?:\/\/[^\s)]+|data:image\/(?:png|jpeg|jpg|gif|webp);base64,[A-Za-z0-9+/=]+)\)/g, '').trim()
}

function mapApiPost(dto: SocialPostDto, existingComments: any[] = []) {
  const author = resolveAuthor(dto.author_user_id)
  const matchedChannel = channels.value.find(c => c.id === dto.channel_id || c.name === dto.channel_id)
  const isPinExpired = dto.pin_expires_at ? new Date(dto.pin_expires_at).getTime() <= Date.now() : false
  const isPinned = Boolean(dto.is_pinned) && !isPinExpired
  return {
    id: dto.id,
    channelId: dto.channel_id,
    channel: matchedChannel?.name || 'Toàn công ty',
    authorId: dto.author_user_id,
    author: author.name,
    role: author.role,
    time: formatTime(dto.published_at || dto.created_at),
    avatar: author.avatar,
    title: dto.title,
    content: extractPostContent(dto),
    postType: dto.post_type,
    likes: Number(dto.reaction_count ?? 0),
    commentsCount: Number(dto.comment_count ?? 0),
    isLiked: Boolean(dto.my_reaction),
    isSaved: Boolean(dto.is_saved),
    pinned: isPinned,
    pinExpiresAt: dto.pin_expires_at ?? null,
    commentsLocked: Boolean(dto.comments_locked_at),
    image: extractImage(dto),
    attachments: [] as SocialAttachmentDto[],
    comments: existingComments,
  }
}

function mapApiChannel(dto: SocialChannelDto, index: number): ChannelItem {
  const preset = insideChannels.find(c => c.name.toLocaleLowerCase('vi-VN') === dto.name.toLocaleLowerCase('vi-VN'))
    ?? insideChannels[index % insideChannels.length]
  return {
    id: dto.id,
    code: dto.code,
    name: dto.name,
    image: preset?.image || DEFAULT_AVATAR,
    cover: preset?.cover || DEFAULT_COVER,
    description: dto.description || preset?.description || 'Kênh trao đổi và kết nối nội bộ.',
    visibility: dto.visibility ?? 'organization',
    membershipPolicy: dto.membership_policy ?? 'open',
    ownerUserId: dto.owner_user_id,
    isMember: true,
  }
}

async function loadSocialData() {
  try {
    const dirPage = await socialApi.directoryUsers(props.token)
    if (Array.isArray(dirPage?.data)) {
      const map: Record<string, DirectoryUserDto> = {}
      for (const item of dirPage.data) {
        map[item.id] = item
      }
      directoryUsers.value = map
    }
  } catch { /* Directory lookup is optional when offline. */ }

  try {
    const chPage = await socialApi.channels(props.token)
    if (Array.isArray(chPage?.data) && chPage.data.length > 0) {
      channels.value = chPage.data.map(mapApiChannel)
      if (!channels.value.some(c => c.name === newChannel.value)) {
        newChannel.value = channels.value[0]!.name
      }
      apiConnected.value = true
    }
  } catch (cause) {
    channels.value = []
    socialError.value = cause instanceof Error ? cause.message : 'Không tải được kênh.'
  }

  try {
    const postPage = await socialApi.posts(props.token)
    if (Array.isArray(postPage?.data)) {
      apiConnected.value = true
      const mapped = postPage.data.map(dto => mapApiPost(dto))
      apiPosts.value = mapped
      await Promise.allSettled(mapped.map(async post => {
        const result = await socialApi.attachments(props.token, post.id)
        post.attachments = result.data
      }))
      savedPosts.value = mapped.filter(p => p.isSaved).map(p => p.id)
    }
  } catch (cause) {
    apiConnected.value = false
    apiPosts.value = []
    socialError.value = cause instanceof Error ? cause.message : 'Không tải được bài viết.'
  }
}

async function loadChannelMembersFor(channel: ChannelItem) {
  if (!apiConnected.value) {
    apiChannelMembers.value = null
    return
  }
  try {
    const page = await socialApi.channelMembers(props.token, channel.id)
    if (Array.isArray(page?.data)) {
      apiChannelMembers.value = page.data.map(member => {
        const info = resolveAuthor(member.user_id)
        return {
          id: member.user_id,
          name: info.name,
          role: member.member_role === 'owner' ? `Chủ kênh · ${info.role}` : info.role,
          avatar: info.avatar,
        }
      })
      channel.isMember = page.data.some(m => m.user_id === props.user.id)
    }
  } catch {
    apiChannelMembers.value = null
  }
}

async function toggleChannelMembership(channel: ChannelItem) {
  const currentlyMember = channel.isMember !== false
  socialBusy.value = true
  socialError.value = ''
  try {
    if (apiConnected.value) {
      if (currentlyMember) {
        await socialApi.leaveChannel(props.token, channel.id)
        channel.isMember = false
        shareFeedback.value = `Đã rời kênh ${channel.name}.`
      } else {
        await socialApi.joinChannel(props.token, channel.id)
        channel.isMember = true
        shareFeedback.value = `Đã tham gia kênh ${channel.name}.`
      }
      await loadChannelMembersFor(channel)
    } else {
      channel.isMember = !currentlyMember
      shareFeedback.value = channel.isMember ? `Đã tham gia kênh ${channel.name}.` : `Đã rời kênh ${channel.name}.`
    }
  } catch (cause) {
    socialError.value = cause instanceof Error ? cause.message : 'Không cập nhật được trạng thái tham gia kênh.'
  } finally {
    socialBusy.value = false
    window.setTimeout(() => { shareFeedback.value = '' }, 3000)
  }
}

onMounted(async () => {
  void refreshUnreadChatCount(props.token).catch(() => {})
  chatCountTimer = setInterval(() => { void refreshUnreadChatCount(props.token).catch(() => {}) }, 10000)
  window.addEventListener('popstate', syncCompanySection)
  seedPersonalPosts(props.user.display_name)
  if (window.location.hash) {
    await nextTick()
    document.getElementById(decodeURIComponent(window.location.hash.slice(1)))?.scrollIntoView()
  }
  loadUnreadCount()
  try {
    const profile = await profileApi.me(props.token)
    jobTitle.value = profile.job_title ?? ''
  } catch { /* Hồ sơ vẫn mở được từ thanh trên. */ }
  try {
    const body = await socialApi.followStats(props.token)
    followingCount.value = body.data.following_count
    followersCount.value = body.data.followers_count
  } catch { /* Giữ dấu gạch khi thống kê chưa sẵn sàng. */ }
  await loadSocialData()
})

onUnmounted(() => {
  window.removeEventListener('popstate', syncCompanySection)
  if (chatCountTimer) clearInterval(chatCountTimer)
})
watch(() => props.page, syncCompanySection)

watch(commentPostId, async (postId) => {
  if (!postId || !apiConnected.value) return
  const target = posts.value.find(p => p.id === postId)
  if (!target) return
  try {
    const page = await socialApi.comments(props.token, postId)
    if (Array.isArray(page?.data)) {
      const roots: any[] = []
      const byId: Record<string, any> = {}
      for (const cmt of page.data) {
        if (!cmt.parent_comment_id) {
          const author = resolveAuthor(cmt.author_user_id)
          const item = {
            id: cmt.id,
            authorId: cmt.author_user_id,
            author: author.name,
            avatar: author.avatar,
            content: cmt.body_plaintext,
            time: formatTime(cmt.created_at),
            likes: 0,
            isLiked: false,
            replies: [] as any[],
          }
          roots.push(item)
          byId[cmt.id] = item
        }
      }
      for (const cmt of page.data) {
        if (cmt.parent_comment_id && byId[cmt.parent_comment_id]) {
          const author = resolveAuthor(cmt.author_user_id)
          byId[cmt.parent_comment_id].replies.push({
            id: cmt.id,
            authorId: cmt.author_user_id,
            author: author.name,
            avatar: author.avatar,
            content: cmt.body_plaintext,
            time: formatTime(cmt.created_at),
            likes: 0,
            isLiked: false,
          })
        }
      }
      target.comments = roots
    }
  } catch { /* Giữ bình luận hiện tại nếu chưa tải được. */ }
})

function readCompanySection(): CompanySection | '' {
  const value = new URLSearchParams(window.location.search).get('company')
  return value === 'letter' || value === 'values' || value === 'principles' ? value : ''
}

function syncCompanySection() { companySection.value = readCompanySection() }

function openCompanySection(section: CompanySection) {
  companySection.value = section
  const url = new URL(window.location.href)
  url.searchParams.set('app', 'inside')
  url.searchParams.set('company', section)
  window.history.pushState({ view: 'inside' }, '', url.toString())
  if (props.page === 'profile') emit('select-app', 'inside')
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function leaveCompanySection() {
  companySection.value = ''
  const url = new URL(window.location.href)
  url.searchParams.delete('company')
  window.history.pushState({ view: 'inside' }, '', url.toString())
}

async function loadUnreadCount() {
  try {
    const response = await fetch('/api/v1/notifications/unread-count', { headers: { Authorization: `Bearer ${props.token}`, Accept: 'application/json' } })
    if (response.ok) unreadCount.value = (await response.json()).data.unread_count
  } catch { /* Keep the feed available. */ }
}

function toggleMessenger() {
  notificationOpen.value = false
  emit('select-app', 'message')
}

function openConversation(id: string) {
  notificationOpen.value = false
  messengerOpen.value = false
  const conversation = conversations.value.find(item => item.id === id)
  if (!conversation) return
  if (!openConversationIds.value.includes(id)) {
    openConversationIds.value = [...openConversationIds.value.slice(-1), id]
  }
  if (conversation) conversation.unread = 0
}

function sendChatMessage(id: string) {
  const conversation = conversations.value.find(item => item.id === id)
  const message = chatDrafts.value[id]?.trim()
  if (!conversation || !message) return
  conversation.messages.push({ id: `local-${Date.now()}`, fromMe: true, text: message, time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) })
  conversation.lastMessage = message
  conversation.lastTime = 'Vừa xong'
  chatDrafts.value[id] = ''
}

function closeConversation(id: string) {
  openConversationIds.value = openConversationIds.value.filter(openId => openId !== id)
}

async function toggleNotifications() {
  messengerOpen.value = false
  notificationOpen.value = !notificationOpen.value
  if (!notificationOpen.value) return
  await loadNotifications()
}

async function loadNotifications() {
  notificationLoading.value = true
  notificationError.value = ''
  try {
    const response = await fetch('/api/v1/notifications', { headers: { Authorization: `Bearer ${props.token}`, Accept: 'application/json' } })
    if (!response.ok) throw new Error('Không tải được thông báo. Vui lòng thử lại.')
    notifications.value = (await response.json()).data
  } catch (cause) {
    notificationError.value = cause instanceof Error ? cause.message : 'Không tải được thông báo.'
  } finally { notificationLoading.value = false }
}

async function markNotificationRead(id: string) {
  const item = notifications.value.find(notification => notification.id === id)
  if (!item || item.read_at) return
  try {
    const response = await fetch(`/api/v1/notifications/${encodeURIComponent(id)}/read`, { method: 'PUT', headers: { Authorization: `Bearer ${props.token}`, Accept: 'application/json' } })
    if (!response.ok) throw new Error()
    item.read_at = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch { notificationError.value = 'Chưa đánh dấu đã đọc được thông báo.' }
}

async function openNotification(item: InboxItem) {
  await markNotificationRead(item.id)
  notificationOpen.value = false

  if (item.source_type === 'user' || item.notification_type === 'profile_update_request') {
    emit('select-app', 'inside-profile')
    return
  }
  if (item.source_type === 'channel' && item.source_id) {
    await showChannel(item.source_id)
    return
  }
  if (item.source_type !== 'post' || !item.source_id) return

  showHome()
  searchQuery.value = ''
  const postId = item.source_id
  if (!posts.value.some(post => post.id === postId)) {
    try {
      const response = await socialApi.post(props.token, postId)
      apiPosts.value = [mapApiPost(response.data), ...(apiPosts.value ?? [])]
    } catch {
      socialError.value = 'Không mở được bài viết trong thông báo.'
      return
    }
  }
  await nextTick()
  if (item.notification_type === 'comment') {
    commentPostId.value = postId
  } else {
    document.getElementById(postId)?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}

async function markAllNotificationsRead() {
  try {
    const response = await fetch('/api/v1/notifications/read-all', { method: 'PUT', headers: { Authorization: `Bearer ${props.token}`, Accept: 'application/json' } })
    if (!response.ok) throw new Error()
    notifications.value.forEach(item => { item.read_at = item.read_at || new Date().toISOString() })
    unreadCount.value = 0
  } catch { notificationError.value = 'Chưa đánh dấu đã đọc được thông báo.' }
}

function showHome() {
  if (companySection.value) leaveCompanySection()
  activeFeed.value = 'home'
  selectedChannel.value = ''
  apiChannelMembers.value = null
  if (props.page === 'profile') emit('select-app', 'inside')
}

async function showChannel(channelName: string) {
  if (companySection.value) leaveCompanySection()
  activeFeed.value = 'home'
  selectedChannel.value = channelName
  channelTab.value = 'posts'
  newChannel.value = channelName
  if (props.page === 'profile') emit('select-app', 'inside')
  const found = channels.value.find(c => c.name === channelName || c.id === channelName)
  if (found) {
    await loadChannelMembersFor(found)
  }
}

function showSaved() {
  if (companySection.value) leaveCompanySection()
  activeFeed.value = 'saved'
  selectedChannel.value = ''
  if (props.page === 'profile') emit('select-app', 'inside')
}

async function toggleSaved(postId: string) {
  const isCurrentlySaved = savedPosts.value.includes(postId)
  const nextSaved = !isCurrentlySaved
  savedPosts.value = nextSaved
    ? [...savedPosts.value, postId]
    : savedPosts.value.filter(id => id !== postId)
  const post = posts.value.find(p => p.id === postId)
  if (post) post.isSaved = nextSaved
  if (apiConnected.value) {
    try {
      await socialApi.save(props.token, postId, nextSaved)
    } catch (cause) {
      savedPosts.value = isCurrentlySaved
        ? [...savedPosts.value, postId]
        : savedPosts.value.filter(id => id !== postId)
      if (post) post.isSaved = isCurrentlySaved
      socialError.value = cause instanceof Error ? cause.message : 'Không lưu được bài viết.'
    }
  }
}

function showEvents() {
  if (props.page === 'profile') emit('select-app', 'inside')
  requestAnimationFrame(() => {
    (document.getElementById('birthdays-widget') || document.getElementById('upcoming-events'))?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  })
}

async function toggleLike(post: any) {
  const prevLiked = Boolean(post.isLiked)
  const nextLiked = !prevLiked
  post.isLiked = nextLiked
  post.likes = Math.max(0, (post.likes || 0) + (nextLiked ? 1 : -1))
  if (apiConnected.value) {
    try {
      await socialApi.react(props.token, post.id, nextLiked ? 'like' : null)
    } catch (cause) {
      post.isLiked = prevLiked
      post.likes = Math.max(0, (post.likes || 0) + (prevLiked ? 1 : -1))
      socialError.value = cause instanceof Error ? cause.message : 'Không thể thả cảm xúc cho bài viết.'
    }
  }
}

async function addComment(post: any) {
  const content = commentDrafts.value[post.id]?.trim()
  if (!content) return
  socialError.value = ''
  if (apiConnected.value) {
    try {
      const created = await socialApi.createComment(props.token, post.id, content)
      const cmt: SocialCommentDto = created.data
      post.comments ??= []
      post.comments.push({
        id: cmt.id,
        authorId: cmt.author_user_id,
        author: props.user.display_name,
        avatar: DEFAULT_AVATAR,
        content: cmt.body_plaintext,
        time: formatTime(cmt.created_at),
        likes: 0,
        isLiked: false,
        replies: [],
      })
      post.commentsCount = (post.commentsCount || 0) + 1
      commentDrafts.value[post.id] = ''
      return
    } catch (cause) {
      socialError.value = cause instanceof Error ? cause.message : 'Không thể gửi bình luận.'
      return
    }
  }
  post.comments ??= []
  post.comments.push({
    id: `comment-${Date.now()}`,
    author: props.user.display_name,
    avatar: DEFAULT_AVATAR,
    content,
    time: 'Vừa xong',
    replies: [],
  })
  post.commentsCount = (post.commentsCount || 0) + 1
  commentDrafts.value[post.id] = ''
}

function toggleCommentLike(comment: any) {
  comment.isLiked = !comment.isLiked
  comment.likes = Math.max(0, (comment.likes || 0) + (comment.isLiked ? 1 : -1))
}

async function addReply(post: any, comment: any) {
  const content = replyDrafts.value[comment.id]?.trim()
  if (!content) return
  socialError.value = ''
  if (apiConnected.value) {
    try {
      const created = await socialApi.createComment(props.token, post.id, content, comment.id)
      const cmt: SocialCommentDto = created.data
      comment.replies ??= []
      comment.replies.push({
        id: cmt.id,
        authorId: cmt.author_user_id,
        author: props.user.display_name,
        avatar: DEFAULT_AVATAR,
        content: cmt.body_plaintext,
        time: formatTime(cmt.created_at),
        likes: 0,
        isLiked: false,
      })
      post.commentsCount = (post.commentsCount || 0) + 1
      replyDrafts.value[comment.id] = ''
      replyingToCommentId.value = ''
      return
    } catch (cause) {
      socialError.value = cause instanceof Error ? cause.message : 'Không thể gửi câu trả lời.'
      return
    }
  }
  comment.replies ??= []
  comment.replies.push({
    id: `reply-${Date.now()}`,
    author: props.user.display_name,
    avatar: DEFAULT_AVATAR,
    content,
    time: 'Vừa xong',
    likes: 0,
    isLiked: false,
  })
  post.commentsCount = (post.commentsCount || 0) + 1
  replyDrafts.value[comment.id] = ''
  replyingToCommentId.value = ''
}

async function sharePost(post: any) {
  const url = new URL(window.location.href)
  url.searchParams.set('app', 'inside')
  url.hash = post.id
  try {
    if (navigator.share) {
      await navigator.share({ title: `Bài viết của ${post.author}`, text: post.content, url: url.toString() })
      shareFeedback.value = 'Đã chia sẻ bài viết.'
    } else {
      await navigator.clipboard.writeText(url.toString())
      shareFeedback.value = 'Đã sao chép liên kết bài viết.'
    }
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') return
    shareFeedback.value = 'Không thể chia sẻ bài viết lúc này.'
  }
  window.setTimeout(() => { shareFeedback.value = '' }, 3000)
}

function isPostOwner(post: any): boolean {
  return post.authorId === props.user.id || post.author === props.user.display_name
}

function startEditPost(post: any) {
  editingPostId.value = post.id
  editingTitle.value = post.title || ''
  editingContent.value = post.content || ''
}

async function saveEditPost(post: any) {
  if (!editingContent.value.trim()) return
  socialBusy.value = true
  socialError.value = ''
  try {
    if (apiConnected.value) {
      const updated = await socialApi.updatePost(props.token, post.id, {
        body_markdown: editingContent.value.trim(),
        ...(post.title !== undefined && post.title !== null ? { title: editingTitle.value.trim() || null } : {}),
      })
      post.content = updated.data.body_plaintext
      post.title = updated.data.title
    } else {
      post.content = editingContent.value.trim()
      if (editingTitle.value.trim()) post.title = editingTitle.value.trim()
    }
    editingPostId.value = ''
    shareFeedback.value = 'Đã cập nhật bài viết.'
    window.setTimeout(() => { shareFeedback.value = '' }, 3000)
  } catch (cause) {
    socialError.value = cause instanceof Error ? cause.message : 'Không cập nhật được bài viết.'
  } finally {
    socialBusy.value = false
  }
}

async function handleDeletePost(post: any) {
  socialBusy.value = true
  socialError.value = ''
  try {
    if (apiConnected.value) {
      await socialApi.deletePost(props.token, post.id)
    }
    if (apiPosts.value !== null) {
      apiPosts.value = apiPosts.value.filter(item => item.id !== post.id)
    } else {
      insidePosts.value = insidePosts.value.filter(item => item.id !== post.id)
    }
    savedPosts.value = savedPosts.value.filter(id => id !== post.id)
    shareFeedback.value = 'Đã xóa bài viết.'
    window.setTimeout(() => { shareFeedback.value = '' }, 3000)
  } catch (cause) {
    socialError.value = cause instanceof Error ? cause.message : 'Không xóa được bài viết.'
  } finally {
    socialBusy.value = false
  }
}

async function togglePinPost(post: any) {
  const nextPinned = !post.pinned
  socialBusy.value = true
  socialError.value = ''
  try {
    if (apiConnected.value) {
      await socialApi.pinPost(props.token, post.id, nextPinned)
    }
    post.pinned = nextPinned
    shareFeedback.value = nextPinned ? 'Đã ghim bài viết lên đầu trang.' : 'Đã bỏ ghim bài viết.'
    window.setTimeout(() => { shareFeedback.value = '' }, 3000)
  } catch (cause) {
    socialError.value = cause instanceof Error ? cause.message : 'Không thể thay đổi trạng thái ghim bài viết.'
  } finally {
    socialBusy.value = false
  }
}

async function handleCreateChannel() {
  if (!apiConnected.value) {
    socialError.value = 'Chưa kết nối được mạng xã hội. Vui lòng tải lại trang.'
    return
  }
  const name = newChannelName.value.trim()
  if (!name) return
  const slug = (newChannelCode.value.trim() || name)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/đ/gi, 'd')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 60) || `channel-${Date.now()}`

  socialBusy.value = true
  socialError.value = ''
  try {
    if (apiConnected.value) {
      const created = await socialApi.createChannel(props.token, {
        code: slug,
        name,
        description: newChannelDesc.value.trim() || 'Kênh trao đổi và phối hợp nội bộ.',
        visibility: 'organization',
        membership_policy: 'open',
      })
      const mapped = mapApiChannel(created.data, channels.value.length)
      channels.value.unshift(mapped)
      newChannel.value = mapped.name
    } else {
      const fallbackChannel: ChannelItem = {
        id: slug,
        code: slug,
        name,
        image: DEFAULT_AVATAR,
        cover: DEFAULT_COVER,
        description: newChannelDesc.value.trim() || 'Kênh trao đổi và phối hợp nội bộ.',
        visibility: 'organization',
        membershipPolicy: 'open',
        isMember: true,
      }
      channels.value.unshift(fallbackChannel)
      newChannel.value = fallbackChannel.name
    }
    newChannelName.value = ''
    newChannelCode.value = ''
    newChannelDesc.value = ''
    showCreateChannelModal.value = false
    shareFeedback.value = `Đã tạo kênh "${name}".`
    window.setTimeout(() => { shareFeedback.value = '' }, 3000)
  } catch (cause) {
    socialError.value = cause instanceof Error ? cause.message : 'Không thể tạo kênh mới.'
  } finally {
    socialBusy.value = false
  }
}

async function handleAddPost() {
  if (!apiConnected.value) {
    socialError.value = 'Chưa kết nối được mạng xã hội. Vui lòng tải lại trang.'
    return
  }
  if (!newContent.value.trim() && !attachedFile.value) return
  const shouldPin = pinNewPost.value && canPinPosts.value
  const pinExpiry = shouldPin && newPostPinExpiresAt.value ? new Date(newPostPinExpiresAt.value) : null
  if (pinExpiry && (!Number.isFinite(pinExpiry.getTime()) || pinExpiry.getTime() <= Date.now())) {
    socialError.value = 'Hạn ghim phải là thời điểm trong tương lai. Bài viết chưa được đăng; nội dung đã nhập vẫn được giữ lại.'
    isComposerExpanded.value = true
    return
  }
  socialBusy.value = true
  socialError.value = ''
  try {
    const targetChannel = channels.value.find(c => c.name === newChannel.value || c.id === newChannel.value) ?? channels.value[0]
    if (!targetChannel) throw new Error('Chưa tải được kênh để đăng bài viết.')
    const effectivePostType = canChoosePostType.value ? newPostType.value : 'update'
    const imagePayload = attachedImagePreview.value
    let markdownBody = newContent.value.trim() || (attachedFile.value && !imagePayload ? `Tệp đính kèm: ${attachedFile.value.name}` : '')
    if (imagePayload && !markdownBody.includes('![')) {
      markdownBody += (markdownBody ? '\n\n' : '') + `![Ảnh đính kèm](${imagePayload})`
    }

    if (apiConnected.value && targetChannel) {
      const startsAt = new Date(Date.now() + 24 * 3600 * 1000).toISOString()
      const endsAt = new Date(Date.now() + 26 * 3600 * 1000).toISOString()
      const created = await socialApi.createPost(props.token, {
        channel_id: targetChannel.id,
        body_markdown: markdownBody,
        title: newTitle.value.trim() || (effectivePostType !== 'update' ? 'Thông báo nội bộ' : null),
        post_type: effectivePostType,
        publication_status: 'published',
        ...(effectivePostType === 'event'
          ? {
              event: {
                starts_at: startsAt,
                ends_at: endsAt,
                location_text: eventLocation.value.trim() || 'Văn phòng công ty / Google Meet',
                rsvp_enabled: true,
              },
            }
          : {}),
      })
      const expiresAtIso = pinExpiry?.toISOString() ?? null
      let pinned = false
      if (shouldPin) {
        try {
          await socialApi.pinPost(props.token, created.data.id, true, expiresAtIso)
          created.data.is_pinned = true
          created.data.pin_expires_at = expiresAtIso
          pinned = true
        } catch (cause) {
          socialError.value = `Đã đăng bài nhưng chưa ghim được: ${cause instanceof Error ? cause.message : 'Vui lòng thử lại sau.'}`
        }
      }
      const mapped = mapApiPost(created.data)
      if (pinned) {
        mapped.pinned = true
        mapped.pinExpiresAt = expiresAtIso
      }
      if (imagePayload && !mapped.image) {
        mapped.image = imagePayload
      }
      const targetList = apiPosts.value ?? insidePosts.value
      targetList.unshift(mapped)
      if (attachedFile.value && !imagePayload) {
        await socialApi.uploadAttachment(props.token, created.data.id, attachedFile.value)
        const attachments = await socialApi.attachments(props.token, created.data.id)
        mapped.attachments = attachments.data
      }
    } else {
      const expiresAtIso = newPostPinExpiresAt.value ? new Date(newPostPinExpiresAt.value).toISOString() : null
      insidePosts.value.unshift({
        id: `post-${Date.now()}`,
        authorId: props.user.id,
        author: props.user.display_name,
        role: jobTitle.value || 'Thành viên đóng góp',
        channelId: targetChannel?.id || newChannel.value,
        channel: targetChannel?.name || newChannel.value,
        time: 'Vừa xong',
        avatar: (props.user as any).profile?.avatar_key || USER_AVATAR_FALLBACKS[props.user.id] || DEFAULT_AVATAR,
        title: newTitle.value.trim() || null,
        content: newContent.value.trim(),
        postType: effectivePostType,
        pinned: pinNewPost.value,
        pinExpiresAt: expiresAtIso,
        likes: 0,
        commentsCount: 0,
        isLiked: false,
        image: imagePayload,
        comments: [],
      })
    }
    newTitle.value = ''
    newContent.value = ''
    eventLocation.value = ''
    pinNewPost.value = false
    newPostPinExpiresAt.value = ''
    newPostType.value = 'update'
    removeAttachedFile()
    isComposerExpanded.value = false
  } catch (cause) {
    socialError.value = cause instanceof Error ? cause.message : 'Không thể đăng bài viết.'
  } finally {
    socialBusy.value = false
  }
}
</script>

<template>
  <div class="min-h-screen text-slate-900 antialiased flex flex-col justify-between bg-[#f7f8fa] font-sans">
    
    <!-- TOP APP NAVBAR (03-social-feed.html) -->
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200">
      <div class="mx-auto w-full px-3 sm:px-5 lg:px-6 h-16 flex items-center justify-between gap-2 sm:gap-4">
        
        <!-- Brand Logo -->
        <button
          type="button"
          class="flex items-center gap-2.5 cursor-pointer border-none bg-transparent"
          title="Về cổng Portal"
          @click="emit('back')"
        >
          <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm ">
            <span class="material-symbols-outlined text-base">change_history</span>
          </div>
          <span class="hidden text-base font-extrabold tracking-wider text-slate-900 sm:inline">Openhello</span>
        </button>

        <!-- Center-Right Tools -->
        <div class="flex min-w-0 items-center gap-1 sm:gap-2">
          <div class="relative mr-1 hidden w-44 md:block lg:w-72 xl:w-80">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Tìm kiếm bài viết, người, kênh..."
              class="w-full pl-4 pr-9 py-2.5 bg-slate-50 hover:bg-slate-100 focus:bg-white text-xs rounded-xl border border-slate-200 focus:border-blue-600 outline-none transition"
            >
            <span class="material-symbols-outlined absolute right-2.5 top-2 text-slate-400 text-xl">search</span>
          </div>

          <button
            type="button"
            class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-blue-600"
            title="Tạo nhanh"
            @click="isComposerExpanded = true"
          >
            <span class="material-symbols-outlined text-2xl">add</span>
          </button>

          <button type="button" class="relative grid h-10 w-10 shrink-0 place-items-center rounded-xl transition" :class="messengerOpen ? 'text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-blue-700'" title="Mở danh sách đoạn chat" aria-label="Mở danh sách đoạn chat" :aria-expanded="messengerOpen" @click="toggleMessenger">
            <span class="material-symbols-outlined block">chat</span>
            <span v-if="unreadChatCount > 0" class="absolute -right-1 -top-1 min-w-5 h-5 px-1 rounded-full bg-rose-600 text-white text-[11px] font-bold flex items-center justify-center">{{ unreadChatCount > 99 ? '99+' : unreadChatCount }}</span>
          </button>

          <div class="relative shrink-0">
            <button
              type="button"
              class="grid h-10 w-10 place-items-center rounded-xl transition"
              title="Thông báo"
              aria-label="Xem thông báo"
              :aria-expanded="notificationOpen"
              :class="notificationOpen ? 'text-blue-700' : 'text-slate-600 hover:bg-slate-100'"
              @click="toggleNotifications"
            >
              <span class="material-symbols-outlined text-2xl">notifications</span>
            </button>
            <span v-if="unreadCount" class="pointer-events-none absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-600 px-0.5 text-[9px] font-bold text-white">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
          </div>

          <button
            type="button"
            class="flex min-w-0 items-center gap-2 rounded-xl px-1 py-1 transition hover:bg-slate-100 sm:px-2"
            title="Xem trang cá nhân"
            @click="emit('select-app', 'inside-profile')"
          >
            <img
              :src="DEFAULT_AVATAR"
              alt="Avatar"
              class="w-7 h-7 rounded-full object-cover border border-slate-200"
            >
            <span class="hidden max-w-24 truncate text-xs font-bold text-slate-800 lg:block xl:max-w-36">{{ props.user.display_name }}</span>
          </button>

          <button
            type="button"
            class="grid h-10 w-10 shrink-0 place-items-center rounded-xl text-slate-500 transition hover:bg-rose-50 hover:text-rose-600"
            title="Đăng xuất"
            @click="emit('logout')"
          >
            <span class="material-symbols-outlined text-xl">logout</span>
          </button>
        </div>
      </div>
    </header>

    <!-- Full-width feed, company context and compact contacts -->
    <main class="grid w-full flex-1 grid-cols-1 items-start gap-5 px-3 py-5 sm:px-5 lg:grid-cols-[220px_minmax(0,1fr)_270px] lg:px-6 xl:grid-cols-[240px_minmax(0,1fr)_300px] xl:pr-20">
      
      <!-- One continuous navigation rail; labels divide each group. -->
      <aside class="hidden self-start rounded-xl border border-slate-200 bg-white lg:sticky lg:top-20 lg:block max-h-[calc(100vh-6rem)] overflow-y-auto overscroll-contain">
        <button type="button" class="flex w-full items-center gap-3 border-l-[3px] border-blue-600 px-4 py-3 text-left text-sm font-bold text-blue-700 hover:bg-slate-50" @click="showHome"><span class="material-symbols-outlined text-xl">home</span> Bảng tin Home</button>
        <button type="button" class="flex w-full items-center gap-3 px-4 py-4 text-left hover:bg-slate-50" @click="emit('select-app', 'inside-profile')"><img :src="DEFAULT_AVATAR" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover"><span class="min-w-0"><strong class="block truncate text-sm text-slate-900">{{ user.display_name }}</strong><span class="block truncate text-xs text-slate-500">{{ jobTitle || 'Thành viên công ty' }}</span></span></button>
        <div class="grid grid-cols-2 border-y border-slate-100 text-center text-xs"><div class="border-r border-slate-100 px-2 py-3"><strong class="block text-lg text-slate-900">{{ followingCount ?? '—' }}</strong><span class="text-slate-500">Đang theo dõi</span></div><div class="px-2 py-3"><strong class="block text-lg text-slate-900">{{ followersCount ?? '—' }}</strong><span class="text-slate-500">Người theo dõi</span></div></div>
        <nav class="space-y-1 px-3 py-3 text-sm" aria-label="Điều hướng mạng xã hội"><button type="button" class="flex w-full items-center gap-3 rounded-lg px-2 py-2.5 text-left font-semibold text-slate-700 hover:bg-slate-50" @click="showEvents"><span class="material-symbols-outlined text-xl text-teal-600">event</span> Sự kiện</button><button type="button" class="flex w-full items-center gap-3 rounded-lg px-2 py-2.5 text-left font-semibold hover:bg-slate-50" :class="activeFeed === 'saved' ? 'text-blue-700' : 'text-slate-700'" @click="showSaved"><span class="material-symbols-outlined text-xl text-blue-600">bookmark</span> Đã lưu <span class="ml-auto text-xs text-slate-400">{{ savedPosts.length }}</span></button></nav>
        <div class="border-t border-slate-100 px-3 py-4 text-sm">
          <div class="mb-2 flex items-center justify-between px-2">
            <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">Kênh nội bộ</h2>
            <button
              v-if="canManageChannels"
              type="button"
              class="inline-flex items-center gap-0.5 rounded px-1.5 py-0.5 text-[11px] font-bold text-blue-600 hover:bg-blue-50 cursor-pointer border-none bg-transparent"
              title="Tạo kênh mới"
              @click="showCreateChannelModal = !showCreateChannelModal"
            >
              <span class="material-symbols-outlined text-sm">add</span> Tạo kênh
            </button>
          </div>
          <form v-if="showCreateChannelModal" class="mb-3 space-y-2 rounded-lg border border-blue-100 bg-blue-50/50 p-2.5 text-xs" @submit.prevent="handleCreateChannel">
            <input v-model="newChannelName" type="text" required placeholder="Tên kênh (VD: Dự án Q4)..." class="w-full rounded border border-slate-200 bg-white px-2.5 py-1.5 text-xs outline-none focus:border-blue-600">
            <input v-model="newChannelDesc" type="text" placeholder="Mô tả ngắn..." class="w-full rounded border border-slate-200 bg-white px-2.5 py-1.5 text-xs outline-none focus:border-blue-600">
            <div class="flex justify-end gap-1.5">
              <button type="button" class="rounded bg-slate-200 px-2 py-1 text-[11px] text-slate-700 cursor-pointer border-none" @click="showCreateChannelModal = false">Hủy</button>
              <button type="submit" :disabled="socialBusy || !newChannelName.trim()" class="rounded bg-blue-600 px-2.5 py-1 text-[11px] font-bold text-white cursor-pointer border-none disabled:opacity-50">Tạo</button>
            </div>
          </form>
          <div class="space-y-1">
            <button
              v-for="channel in displayedChannels"
              :key="channel.id"
              type="button"
              class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left text-slate-700 hover:bg-slate-50 transition"
              :class="selectedChannel === channel.name ? 'bg-blue-50 text-blue-700 font-semibold' : ''"
              @click="showChannel(channel.name)"
            >
              <img :src="channel.image" alt="" class="h-8 w-8 shrink-0 rounded-full object-cover">
              <span class="truncate text-xs font-medium">{{ channel.name }}</span>
            </button>
          </div>
          <button
            v-if="channels.length > 3"
            type="button"
            class="mt-1 flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-left text-xs font-semibold text-slate-600 hover:bg-slate-100 transition cursor-pointer border-none bg-transparent"
            @click="toggleExpandChannels"
          >
            <div class="h-7 w-7 rounded-full bg-slate-200 flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-base text-slate-700">{{ visibleChannelLimit > 3 ? 'keyboard_arrow_up' : 'keyboard_arrow_down' }}</span>
            </div>
            <span>{{ visibleChannelLimit > 3 ? 'Thu gọn' : 'Xem thêm' }}</span>
          </button>
        </div>
        <div class="border-t border-slate-100 px-3 py-4 text-sm"><h2 class="mb-2 px-2 text-xs font-bold uppercase tracking-wide text-slate-500">Công ty</h2><div class="space-y-1 text-slate-700"><button type="button" class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left hover:bg-slate-50" @click="openCompanySection('letter')"><span class="material-symbols-outlined text-xl text-slate-400">mail</span> Thư từ Ban Giám đốc</button><button type="button" class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left hover:bg-slate-50" @click="openCompanySection('values')"><span class="material-symbols-outlined text-xl text-slate-400">auto_stories</span> Giá trị cốt lõi</button><button type="button" class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left hover:bg-slate-50" @click="openCompanySection('principles')"><span class="material-symbols-outlined text-xl text-slate-400">flag</span> Nguyên tắc làm việc</button></div></div>
      </aside>
      <!-- CENTER FEED: POST CREATOR & FEED POSTS -->
      <section v-if="currentCompanyPage" class="min-w-0 overflow-hidden rounded-xl bg-white" :aria-label="currentCompanyPage.title">
        <header class="relative h-56 overflow-hidden sm:h-64">
          <img :src="currentCompanyPage.image" alt="" class="h-full w-full object-cover object-center">
          <div class="absolute inset-0 bg-slate-950/55"></div>
          <div class="absolute inset-x-0 bottom-0 p-5 text-white sm:p-7"><p class="text-xs font-semibold uppercase tracking-wider text-blue-100">{{ currentCompanyPage.eyebrow }}</p><h1 class="mt-1 text-xl font-bold sm:text-2xl">{{ currentCompanyPage.title }}</h1></div>
        </header>
        <div class="px-5 py-6 text-sm leading-7 text-slate-700 sm:px-7">
          <p>{{ currentCompanyPage.intro }}</p>
          <article v-for="(section, index) in currentCompanyPage.sections" :key="section.title" class="border-b border-slate-100 py-6 last:border-b-0">
            <h2 class="font-bold text-slate-900"><span class="mr-3 text-blue-600">0{{ index + 1 }}</span>{{ section.title }}</h2>
            <p class="mt-2">{{ section.body }}</p>
          </article>
        </div>
      </section>
      <InsideProfileView v-else-if="page === 'profile'" :user="user" :token="token" :channel-map="channelMap" @back="showHome" @settings="emit('select-app', 'profile')" @comment="commentPostId = $event" @share="sharePost" />
      <section v-else class="min-w-0 space-y-4">

        <p v-if="socialError" role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-700">{{ socialError }}</p>

        <div class="relative md:hidden"><input v-model="searchQuery" type="search" placeholder="Tìm bài viết, người, kênh..." class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm outline-none focus:border-blue-500"><span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400">search</span></div>

        <section v-if="currentChannel" class="overflow-hidden rounded-xl border border-slate-200 bg-white" :aria-label="`Kênh ${currentChannel.name}`">
          <img :src="currentChannel.cover" :alt="`Ảnh bìa kênh ${currentChannel.name}`" class="h-48 w-full object-cover object-center sm:h-60">
          <div class="px-5 pb-4">
            <div class="flex flex-wrap items-end justify-between gap-4">
              <div class="flex items-end gap-4">
                <img :src="currentChannel.image" alt="" class="relative -mt-8 h-16 w-16 shrink-0 rounded-full border-4 border-white object-cover">
                <div class="min-w-0 pb-1">
                  <h1 class="text-lg font-bold text-slate-900">{{ currentChannel.name }}</h1>
                  <p class="text-xs text-slate-500">Kênh nội bộ · {{ channelMembers.length }} thành viên</p>
                </div>
              </div>
              <button
                v-if="currentChannel.membershipPolicy !== 'managed' || currentChannel.isMember !== false"
                type="button"
                :disabled="socialBusy"
                class="rounded-lg px-3.5 py-1.5 text-xs font-semibold transition cursor-pointer border"
                :class="currentChannel.isMember === false ? 'border-blue-600 bg-blue-600 text-white hover:bg-blue-700' : 'border-slate-200 bg-slate-100 text-slate-700 hover:bg-slate-200'"
                @click="toggleChannelMembership(currentChannel)"
              >
                {{ currentChannel.isMember === false ? 'Tham gia kênh' : 'Đã tham gia · Rời kênh' }}
              </button>
            </div>
            <p class="mt-3 text-sm leading-6 text-slate-600">{{ currentChannel.description }}</p>
            <div class="mt-4 flex -space-x-2"><img v-for="member in channelMembers" :key="member.id" :src="member.avatar" :alt="member.name" :title="member.name" class="h-8 w-8 rounded-full border-2 border-white object-cover"></div>
          </div>
          <nav class="flex gap-6 border-t border-slate-100 px-5 text-sm font-semibold" aria-label="Nội dung kênh"><button v-for="tab in [{ id: 'posts', label: 'Bài viết' }, { id: 'members', label: 'Thành viên' }, { id: 'media', label: 'Ảnh' }]" :key="tab.id" type="button" class="border-b-2 py-3" :class="channelTab === tab.id ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="channelTab = tab.id as typeof channelTab">{{ tab.label }}</button></nav>
        </section>

        <section v-if="currentChannel && channelTab === 'members'" class="rounded-xl border border-slate-200 bg-white p-5" aria-label="Thành viên kênh"><h2 class="mb-4 font-bold text-slate-900">Thành viên · {{ channelMembers.length }}</h2><div v-for="member in channelMembers" :key="member.id" class="flex items-center gap-3 border-t border-slate-100 py-3 first:border-t-0"><img :src="member.avatar" alt="" class="h-10 w-10 rounded-full object-cover"><div><strong class="block text-sm">{{ member.name }}</strong><span class="text-xs text-slate-500">{{ member.role }}</span></div></div></section>
        <section v-if="currentChannel && channelTab === 'media'" class="rounded-xl border border-slate-200 bg-white p-5" aria-label="Ảnh trong kênh"><h2 class="mb-4 font-bold text-slate-900">Ảnh trong kênh</h2><div class="grid grid-cols-2 gap-2 sm:grid-cols-3"><img :src="currentChannel.cover" alt="Ảnh bìa kênh" class="aspect-square w-full rounded-lg object-cover"><img v-for="post in visiblePosts.filter((item: any) => item.image)" :key="post.id" :src="post.image" :alt="`Ảnh bài viết của ${post.author}`" class="aspect-square w-full rounded-lg object-cover"></div></section>
        
        <!-- Post Creator Box -->
        <div v-if="!currentChannel || channelTab === 'posts'" class="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
          <div class="flex items-center gap-3">
            <img :src="DEFAULT_AVATAR" class="w-9 h-9 rounded-full object-cover">
            <button type="button" class="flex-1 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-left text-sm text-slate-500 rounded-full border-none transition cursor-pointer" @click="isComposerExpanded = true">Chia sẻ với đồng nghiệp...</button>
          </div>

          <div v-if="isComposerExpanded" class="space-y-3 pt-2 border-t border-slate-100">
            <input
              v-if="newPostType !== 'update'"
              v-model="newTitle"
              type="text"
              placeholder="Tiêu đề thông báo / bản tin..."
              class="w-full px-3 py-2 bg-slate-50 text-xs font-semibold rounded-lg border border-slate-200 outline-none focus:border-blue-600"
            >
            <textarea
              v-model="newContent"
              placeholder="Nhập nội dung thông báo hoặc cập nhật của bạn..."
              rows="3"
              class="w-full p-3 bg-slate-50 text-xs rounded-lg border border-slate-200 outline-none focus:border-blue-600"
            ></textarea>
            
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex flex-wrap items-center gap-2">
                <select
                  v-model="newChannel"
                  aria-label="Chọn kênh đăng bài"
                  class="px-2.5 py-1 text-xs rounded border border-slate-200 bg-white text-slate-700 outline-none"
                >
                  <option v-for="ch in channels" :key="ch.id" :value="ch.name">{{ ch.name }}</option>
                </select>

                <!-- Post Type Selector: Only for authorized roles -->
                <select
                  v-if="canChoosePostType"
                  v-model="newPostType"
                  aria-label="Loại bài viết"
                  class="px-2.5 py-1 text-xs rounded border border-slate-200 bg-white text-slate-700 outline-none"
                >
                  <option value="update">Cập nhật thường</option>
                  <option v-if="canPublishAnnouncement" value="announcement">Thông báo chính thức</option>
                  <option v-if="canPublishNews" value="news">Bản tin truyền thông</option>
                  <option value="event">Hoạt động / Sự kiện sắp tới</option>
                </select>
                <span
                  v-else
                  class="px-2.5 py-1 text-xs rounded border border-slate-200 bg-slate-50 text-slate-500 font-medium cursor-not-allowed"
                  title="Chỉ Quản trị viên và HR có quyền chọn loại bản tin/thông báo"
                >
                  Cập nhật thường
                </span>

                <!-- Attach Image / File Input -->
                <input
                  ref="fileInputRef"
                  type="file"
                  accept="image/*,.pdf,.docx,.xlsx"
                  class="hidden"
                  @change="handleFileSelected"
                />
                <button
                  type="button"
                  class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:text-blue-600 rounded hover:bg-slate-50 border border-slate-200 transition cursor-pointer"
                  title="Đính kèm ảnh hoặc tệp tin"
                  @click="triggerFileInput"
                >
                  <span class="material-symbols-outlined text-sm text-blue-600">image</span>
                  <span>Đính kèm ảnh/file</span>
                </button>

                <label v-if="canPinPosts" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 cursor-pointer">
                  <input v-model="pinNewPost" type="checkbox" class="rounded border-slate-300 text-blue-600">
                  <span>Ghim</span>
                </label>

                <div v-if="pinNewPost && canPinPosts" class="inline-flex items-center gap-1 text-xs text-slate-500">
                  <span class="text-[11px]">Hạn ghim:</span>
                  <input
                    v-model="newPostPinExpiresAt"
                    type="datetime-local"
                    class="px-2 py-0.5 text-xs rounded border border-slate-200 bg-white text-slate-700 outline-none"
                    title="Thời hạn ghim bài viết (tùy chọn)"
                  />
                </div>
              </div>

              <div class="flex justify-end gap-2">
                <button
                  type="button"
                  class="px-3 py-1.5 bg-slate-100 text-slate-600 text-xs rounded-md cursor-pointer border-none"
                  @click="isComposerExpanded = false; removeAttachedFile()"
                >
                  Hủy
                </button>
                <button
                  type="button"
                  :disabled="socialBusy || (!newContent.trim() && !attachedFile)"
                  class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold rounded-md cursor-pointer border-none"
                  @click="handleAddPost"
                >
                  Đăng bài
                </button>
              </div>
            </div>

            <!-- Attachment Preview Box -->
            <div v-if="attachedImagePreview || attachedFileName" class="pt-2">
              <div v-if="attachedImagePreview" class="relative inline-block rounded-xl overflow-hidden border border-slate-200 max-h-48 max-w-xs shadow-xs">
                <img :src="attachedImagePreview" alt="Ảnh đính kèm" class="h-full w-full object-cover" />
                <button
                  type="button"
                  class="absolute top-1.5 right-1.5 h-6 w-6 rounded-full bg-slate-900/70 text-white flex items-center justify-center hover:bg-slate-900 transition"
                  title="Bỏ đính kèm ảnh"
                  @click="removeAttachedFile"
                >
                  <span class="material-symbols-outlined text-sm">close</span>
                </button>
              </div>
              <div v-else class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-100 rounded-lg text-xs text-slate-700">
                <span class="material-symbols-outlined text-base text-slate-500">description</span>
                <span class="truncate max-w-[200px]">{{ attachedFileName }}</span>
                <button type="button" class="text-slate-400 hover:text-rose-600" title="Bỏ tệp đính kèm" @click="removeAttachedFile">
                  <span class="material-symbols-outlined text-sm">close</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Action Items below Creator Input -->
          <div class="flex flex-wrap items-center justify-between text-xs font-semibold text-slate-600 pt-2 border-t border-slate-100">
            <button type="button" class="flex items-center gap-1.5 hover:text-blue-600 py-1 border-none bg-transparent cursor-pointer" @click="newPostType = 'update'; isComposerExpanded = true">
              <span class="material-symbols-outlined text-sm text-blue-500">chat</span>
              <span>Cập nhật nhanh</span>
            </button>
            <span class="text-slate-200">|</span>
            <button
              type="button"
              class="flex items-center gap-1.5 py-1 border-none bg-transparent cursor-pointer"
              :class="canChoosePostType ? 'hover:text-blue-600 text-slate-600' : 'text-slate-300 cursor-not-allowed'"
              :title="canChoosePostType ? 'Đăng thông báo chính thức' : 'Chỉ Quản trị viên và HR có quyền đăng thông báo'"
              @click="if (canChoosePostType) { newPostType = 'announcement'; isComposerExpanded = true }"
            >
              <span class="material-symbols-outlined text-sm text-amber-500">campaign</span>
              <span>Thông báo</span>
            </button>
            <span class="text-slate-200">|</span>
            <button type="button" class="flex items-center gap-1.5 hover:text-blue-600 py-1 border-none bg-transparent cursor-pointer" @click="isComposerExpanded = true">
              <span class="material-symbols-outlined text-sm text-emerald-500">poll</span>
              <span>Tạo bình chọn</span>
            </button>
            <span class="text-slate-200">|</span>
            <button
              type="button"
              class="flex items-center gap-1.5 py-1 border-none bg-transparent cursor-pointer"
              :class="canChoosePostType ? 'hover:text-blue-600 text-slate-600' : 'text-slate-300 cursor-not-allowed'"
              :title="canChoosePostType ? 'Tạo sự kiện mới' : 'Chỉ Quản trị viên có quyền tạo sự kiện'"
              @click="if (canChoosePostType) { newPostType = 'event'; isComposerExpanded = true }"
            >
              <span class="material-symbols-outlined text-sm text-teal-500">event</span>
              <span>Tạo sự kiện</span>
            </button>
          </div>
        </div>

        <!-- Posts Loop -->
        <article
          v-for="post in (currentChannel && channelTab !== 'posts' ? [] : visiblePosts)"
          :key="post.id"
          :id="post.id"
          class="bg-white rounded-xl border border-slate-200 overflow-hidden space-y-4 p-5"
        >
          <div v-if="isPostPinned(post)" class="flex items-center justify-between border-b border-slate-100 pb-3 text-xs font-semibold text-blue-600">
            <div class="flex items-center gap-1.5">
              <span class="material-symbols-outlined text-base">push_pin</span>
              <span>Bài viết được ghim</span>
            </div>
            <span v-if="post.pinExpiresAt" class="text-[11px] font-normal text-slate-400">
              Hạn ghim: {{ new Date(post.pinExpiresAt).toLocaleDateString('vi-VN') }}
            </span>
          </div>
          <!-- Author Info -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <img :src="post.avatar" class="w-11 h-11 rounded-full object-cover">
              <div>
                <div class="flex items-center gap-1.5 flex-wrap text-xs">
                  <span class="font-bold text-slate-900">{{ post.author }}</span>
                  <span class="text-slate-400">•</span>
                  <span class="px-2 py-0.5 rounded text-blue-700 font-semibold text-[10px]">{{ post.channel }}</span>
                </div>
                <div class="text-[11px] text-slate-400">{{ post.role }} • {{ post.time }}</div>
              </div>
            </div>
            <div class="flex items-center gap-1">
              <template v-if="isPostOwner(post)">
                <button
                  type="button"
                  class="text-slate-400 hover:text-blue-600 border-none bg-transparent cursor-pointer p-1"
                  title="Chỉnh sửa bài viết"
                  :aria-label="`Sửa bài viết của ${post.author}`"
                  @click="startEditPost(post)"
                >
                  <span class="material-symbols-outlined text-base">edit</span>
                </button>
                <button
                  type="button"
                  class="text-slate-400 hover:text-rose-600 border-none bg-transparent cursor-pointer p-1"
                  title="Xóa bài viết"
                  :aria-label="`Xóa bài viết của ${post.author}`"
                  @click="requestDeletePost(post)"
                >
                  <span class="material-symbols-outlined text-base">delete</span>
                </button>
              </template>
              <button
                v-if="canPinPosts"
                type="button"
                class="border-none bg-transparent cursor-pointer p-1 transition"
                :class="post.pinned ? 'text-blue-600 hover:text-blue-800' : 'text-slate-400 hover:text-blue-600'"
                :title="post.pinned ? 'Bỏ ghim bài viết' : 'Ghim bài viết lên đầu trang'"
                @click="openPinModal(post)"
              >
                <span class="material-symbols-outlined text-base">push_pin</span>
              </button>
              <button type="button" class="text-slate-400 hover:text-blue-600 border-none bg-transparent cursor-pointer p-1" :title="savedPosts.includes(post.id) ? 'Bỏ lưu bài viết' : 'Lưu bài viết'" @click="toggleSaved(post.id)">
                <span class="material-symbols-outlined text-base">{{ savedPosts.includes(post.id) ? 'bookmark_added' : 'bookmark_add' }}</span>
              </button>
            </div>
          </div>

          <!-- Inline Edit Mode -->
          <div v-if="editingPostId === post.id" class="space-y-2 rounded-lg bg-slate-50 p-3 border border-slate-200">
            <input
              v-if="post.title !== null && post.title !== undefined"
              v-model="editingTitle"
              type="text"
              class="w-full rounded border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold outline-none focus:border-blue-600"
              placeholder="Tiêu đề bài viết"
            >
            <textarea
              v-model="editingContent"
              rows="3"
              class="w-full rounded border border-slate-200 bg-white p-2.5 text-xs outline-none focus:border-blue-600"
            ></textarea>
            <div class="flex justify-end gap-2">
              <button type="button" class="rounded bg-slate-200 px-3 py-1 text-xs text-slate-700" @click="editingPostId = ''">Hủy</button>
              <button type="button" :disabled="socialBusy || !editingContent.trim()" class="rounded bg-blue-600 px-3 py-1 text-xs font-bold text-white" @click="saveEditPost(post)">Lưu thay đổi</button>
            </div>
          </div>

          <!-- Content Body -->
          <template v-else>
            <h2 v-if="post.title" class="text-lg font-bold text-slate-900">{{ post.title }}</h2>
            <p class="text-sm text-slate-800 leading-relaxed whitespace-pre-line">
              {{ post.content }}
            </p>
          </template>

          <!-- Attached Image if any -->
          <div v-if="post.image" class="rounded-xl overflow-hidden border border-slate-100 max-h-[520px]">
            <img :src="post.image" :alt="`Ảnh đính kèm bài viết của ${post.author}`" class="w-full h-full object-cover">
          </div>

          <div v-if="post.attachments?.length" class="space-y-2" aria-label="Tệp đính kèm">
            <button
              v-for="attachment in post.attachments"
              :key="attachment.id"
              type="button"
              class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-left hover:border-blue-300 hover:bg-blue-50"
              @click="openAttachment(post.id, attachment)"
            >
              <span class="material-symbols-outlined text-2xl text-blue-600">{{ attachment.mime_type === 'application/pdf' ? 'picture_as_pdf' : 'description' }}</span>
              <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold text-slate-800">{{ attachment.original_filename }}</span>
                <span class="text-xs text-slate-500">{{ (attachment.size_bytes / 1024).toFixed(1) }} KB · {{ attachment.mime_type === 'application/pdf' ? 'Xem trước PDF' : 'Mở hoặc tải tệp' }}</span>
              </span>
              <span class="material-symbols-outlined text-slate-500">open_in_new</span>
            </button>
          </div>

          <!-- Compact actions -->
          <div class="flex items-center gap-6 border-t border-slate-100 pt-3 text-sm text-slate-500">
            <button type="button" class="inline-flex items-center gap-1.5 hover:text-blue-600" :class="post.isLiked ? 'text-blue-600' : ''" :aria-label="`Thích bài viết của ${post.author}`" @click="toggleLike(post)">
              <span class="material-symbols-outlined text-xl">thumb_up</span><span>{{ post.likes }}</span>
            </button>
            <button type="button" class="inline-flex items-center gap-1.5 hover:text-blue-600" :aria-label="`Xem bình luận bài viết của ${post.author}`" @click="commentPostId = post.id">
              <span class="material-symbols-outlined text-xl">chat_bubble</span><span>{{ post.commentsCount }}</span>
            </button>
            <button type="button" class="inline-flex items-center gap-1.5 hover:text-blue-600" :aria-label="`Chia sẻ bài viết của ${post.author}`" @click="sharePost(post)">
              <span class="material-symbols-outlined text-xl">share</span><span v-if="post.sharesCount">{{ post.sharesCount }}</span>
            </button>
          </div>
        </article>

        <p v-if="shareFeedback" role="status" class="fixed bottom-5 left-1/2 z-50 -translate-x-1/2 rounded-full bg-slate-900 px-4 py-2 text-sm text-white">{{ shareFeedback }}</p>

        <div v-if="(!currentChannel || channelTab === 'posts') && !visiblePosts.length" class="rounded-xl border border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-500 ">{{ searchQuery ? 'Không tìm thấy bài viết phù hợp.' : activeFeed === 'saved' ? 'Bạn chưa lưu bài viết nào.' : 'Chưa có bài viết trong kênh này.' }}</div>

      </section>

      <!-- RIGHT SIDEBAR: TRENDING & ANNOUNCEMENTS -->
      <aside class="space-y-4 lg:sticky lg:top-20 self-start">
        
        <section class="rounded-xl border border-slate-200 bg-white p-5 text-sm leading-relaxed text-slate-700" aria-label="Tầm nhìn và sứ mệnh công ty"><div class="flex gap-3"><span class="material-symbols-outlined text-xl text-rose-500">target</span><div><h2 class="font-bold text-slate-900">Tầm nhìn</h2><p class="mt-1">Chúng tôi tin rằng nền tảng công nghệ hoàn chỉnh giúp doanh nghiệp phát triển đột phá và mỗi thành viên cùng tiến xa hơn.</p></div></div><div class="mt-5 flex gap-3"><span class="material-symbols-outlined text-xl text-emerald-600">eco</span><div><h2 class="font-bold text-slate-900">Sứ mệnh</h2><p class="mt-1">Xây dựng nền tảng mở, thông minh với các ứng dụng hữu ích cho công việc, kết nối và chia sẻ tri thức.</p></div></div></section>
        <!-- Birthdays in Month Widget (Replaces upcoming events per Image 3) -->
        <div id="birthdays-widget" class="bg-white rounded-xl border border-slate-200 p-5 space-y-3.5 text-xs shadow-xs">
          <div class="flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-xs flex items-center gap-2">
              <span class="text-base">🎂</span>
              <span class="tracking-wide uppercase font-extrabold text-[12px] text-slate-800">Sinh nhật tháng {{ currentMonth }}</span>
            </h3>
            <span v-if="!birthdayStatus || birthdaysList.length" class="text-[11px] text-slate-400 font-semibold">{{ birthdaysList.length }} người</span>
          </div>
          <div class="max-h-[320px] overflow-y-auto space-y-1 divide-y divide-slate-100 pr-1">
            <p v-if="birthdayStatus" class="text-slate-500">{{ birthdayStatus }}</p>
            <div v-for="person in birthdaysList" :key="person.id" class="flex items-center justify-between py-2.5 first:pt-0 last:pb-0 gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <img v-if="person.avatar" :src="person.avatar" :alt="person.name" class="w-10 h-10 rounded-full object-cover shrink-0 border border-slate-100" />
                <span v-else class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100">{{ person.name.trim().slice(0, 1) }}</span>
                <div class="min-w-0">
                  <div class="font-bold text-slate-900 text-xs truncate">{{ person.name }}</div>
                  <div class="text-[11px] text-slate-500 truncate">{{ person.position || 'Chưa cập nhật vị trí' }}</div>
                </div>
              </div>
              <div class="shrink-0 bg-[#2A2B2E] text-white text-[11px] font-semibold px-2.5 py-1 rounded-full shadow-xs">
                {{ person.date }}
              </div>
            </div>
          </div>
        </div>


      </aside>


    </main>
  <div v-if="attachmentPreview" class="fixed inset-0 z-50 flex flex-col bg-slate-900/80 p-4" role="dialog" aria-modal="true" aria-label="Xem trước tệp PDF">
    <div class="flex items-center justify-between rounded-t-xl bg-white px-4 py-3">
      <strong class="truncate text-sm text-slate-800">{{ attachmentPreview.name }}</strong>
      <button type="button" class="rounded p-1 text-slate-600 hover:bg-slate-100" aria-label="Đóng xem trước" @click="attachmentPreview = null">✕</button>
    </div>
    <iframe :src="attachmentPreview.url" :title="attachmentPreview.name" class="min-h-0 flex-1 rounded-b-xl bg-white"></iframe>
  </div>

    <aside class="fixed right-3 bottom-5 z-30 hidden flex-col items-center gap-3 xl:flex" aria-label="Liên hệ nhanh">
      <button v-for="conversation in conversations.slice(0, 5)" :key="conversation.id" type="button" class="relative rounded-full" :title="`Nhắn tin với ${conversation.user.name}`" @click="openConversation(conversation.id)"><img :src="conversation.user.avatar" :alt="conversation.user.name" class="h-11 w-11 rounded-full object-cover"><span v-if="conversation.user.online" class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-emerald-500"></span><span v-if="conversation.unread" class="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full bg-blue-600"></span></button>
      <button type="button" class="grid h-11 w-11 place-items-center rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-blue-600" title="Xem tất cả đoạn chat" @click="toggleMessenger"><span class="material-symbols-outlined">edit_square</span></button>
    </aside>

    <div v-if="messengerOpen || notificationOpen" class="fixed inset-0 z-40" :class="{ 'bg-slate-950/25': notificationOpen }" @click="messengerOpen = false; notificationOpen = false"></div>
    <section v-if="messengerOpen" class="fixed right-4 top-[72px] z-50 flex max-h-[min(650px,calc(100vh-88px))] w-[min(360px,calc(100vw-32px))] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white " aria-label="Danh sách đoạn chat">
      <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h2 class="text-lg font-bold">Đoạn chat</h2>
        <button type="button" class="rounded-full p-1 text-slate-500 hover:bg-slate-100" aria-label="Đóng danh sách chat" @click="messengerOpen = false"><span class="material-symbols-outlined block">close</span></button>
      </div>
      <div class="px-4 pt-3"><input v-model="contactSearch" type="search" placeholder="Tìm kiếm trên Messenger" class="w-full rounded-full bg-slate-100 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"></div>
      <p class="px-5 pt-2 text-[11px] text-slate-400">Danh sách xem trước từ bản thiết kế</p>
      <div class="min-h-0 flex-1 overflow-y-auto p-2">
        <button v-for="conversation in filteredConversations" :key="conversation.id" type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left hover:bg-slate-50" @click="openConversation(conversation.id)">
          <span class="relative shrink-0"><img :src="conversation.user.avatar" :alt="conversation.user.name" class="h-12 w-12 rounded-full object-cover"><span v-if="conversation.user.online" class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-emerald-500"></span></span>
          <span class="min-w-0 flex-1"><strong class="block truncate text-sm text-slate-900">{{ conversation.user.name }}</strong><span class="block truncate text-xs" :class="conversation.unread ? 'font-bold text-slate-900' : 'text-slate-500'">{{ conversation.lastMessage }}</span></span>
          <span v-if="conversation.unread" class="h-2.5 w-2.5 shrink-0 rounded-full bg-blue-600"></span>
        </button>
        <p v-if="!filteredConversations.length" class="p-4 text-center text-sm text-slate-500">Không tìm thấy đoạn chat.</p>
      </div>
      <button type="button" class="border-t border-slate-100 px-4 py-3 text-center text-sm font-bold text-blue-600 hover:bg-blue-50" @click="emit('select-app', 'message')">Xem tất cả trong Messenger</button>
    </section>

    <section v-for="(activeConversation, index) in openConversations" :key="activeConversation.id" class="fixed bottom-0 z-50 flex h-[min(480px,calc(100vh-80px))] w-[min(360px,calc(100vw-32px))] flex-col overflow-hidden rounded-t-xl bg-white" :class="index === openConversations.length - 1 ? 'right-4 xl:right-20' : 'right-[456px] max-lg:hidden'" aria-label="Khung trò chuyện">
      <div class="flex items-center gap-2 px-4 py-3"><img :src="activeConversation.user.avatar" :alt="activeConversation.user.name" class="h-10 w-10 rounded-full object-cover"><div class="min-w-0 flex-1"><h2 class="truncate text-sm font-bold text-slate-900">{{ activeConversation.user.name }}</h2><p class="text-xs text-slate-500">{{ activeConversation.user.online ? 'Đang hoạt động' : 'Ngoại tuyến' }}</p></div><button type="button" class="grid h-8 w-8 place-items-center rounded-full text-blue-600 hover:bg-blue-50" aria-label="Gọi thoại"><span class="material-symbols-outlined">call</span></button><button type="button" class="grid h-8 w-8 place-items-center rounded-full text-blue-600 hover:bg-blue-50" aria-label="Gọi video"><span class="material-symbols-outlined">videocam</span></button><button type="button" class="grid h-8 w-8 place-items-center rounded-full text-blue-600 hover:bg-blue-50" aria-label="Đóng khung chat" @click="closeConversation(activeConversation.id)"><span class="material-symbols-outlined">close</span></button></div>
      <div class="min-h-0 flex-1 overflow-y-auto px-3 py-4"><div v-for="message in activeConversation.messages" :key="message.id" class="mb-2 flex items-end gap-2" :class="message.fromMe ? 'justify-end' : 'justify-start'"><img v-if="!message.fromMe" :src="activeConversation.user.avatar" :alt="activeConversation.user.name" class="h-7 w-7 shrink-0 rounded-full object-cover"><div class="max-w-[78%]"><p class="rounded-2xl px-3.5 py-2 text-sm leading-relaxed" :class="message.fromMe ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-900'">{{ message.text }}</p><span class="mt-1 block px-1 text-[10px] text-slate-400" :class="message.fromMe ? 'text-right' : ''">{{ message.time }}</span></div></div></div>
      <form class="flex items-center gap-1 px-2 py-3" @submit.prevent="sendChatMessage(activeConversation.id)"><button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-blue-600 hover:bg-blue-50" aria-label="Thêm nội dung"><span class="material-symbols-outlined">add_circle</span></button><button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-blue-600 hover:bg-blue-50" aria-label="Thêm ảnh"><span class="material-symbols-outlined">image</span></button><div class="flex min-w-0 flex-1 items-center rounded-full bg-slate-100 pr-2"><input v-model="chatDrafts[activeConversation.id]" type="text" :aria-label="`Nhắn tin cho ${activeConversation.user.name}`" placeholder="Aa" class="min-w-0 flex-1 bg-transparent px-3 py-2 text-sm outline-none"><span class="material-symbols-outlined text-blue-600">mood</span></div><button type="submit" :disabled="!chatDrafts[activeConversation.id]?.trim()" class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-blue-600 disabled:text-slate-300" aria-label="Gửi tin nhắn"><span class="material-symbols-outlined">{{ chatDrafts[activeConversation.id]?.trim() ? 'send' : 'thumb_up' }}</span></button></form>
    </section>

    <div v-if="commentPost" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-3 sm:p-6" @click.self="commentPostId = ''" @keydown.esc="commentPostId = ''">
      <section role="dialog" aria-modal="true" :aria-label="`Bài viết của ${commentPost.author}`" tabindex="-1" class="flex h-[min(820px,calc(100vh-32px))] w-full max-w-[760px] flex-col overflow-hidden rounded-xl bg-white text-slate-900">
        <header class="relative flex shrink-0 items-center justify-center px-14 py-4">
          <h2 class="truncate text-lg font-bold">Bài viết của {{ commentPost.author }}</h2>
          <button type="button" class="absolute right-4 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200" aria-label="Đóng bình luận" @click="commentPostId = ''"><span class="material-symbols-outlined">close</span></button>
        </header>
        <div class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100 px-4 py-5 sm:px-6">
          <div class="flex items-center gap-3"><img :src="commentPost.avatar" alt="" class="h-10 w-10 rounded-full object-cover"><div><strong class="text-sm text-slate-900">{{ commentPost.author }}</strong><p class="text-xs text-slate-500">{{ commentPost.time }} · {{ commentPost.channel }}</p></div></div>
          <p class="mt-4 whitespace-pre-line text-sm leading-6">{{ commentPost.content }}</p>
          <img v-if="commentPost.image" :src="commentPost.image" :alt="`Ảnh bài viết của ${commentPost.author}`" class="mt-4 max-h-[360px] w-full rounded-lg object-cover">
          <div class="mt-4 flex items-center gap-6 border-b border-slate-100 pb-4 text-sm text-slate-500">
            <button type="button" class="inline-flex items-center gap-1.5 hover:text-blue-600" :class="commentPost.isLiked ? 'text-blue-600' : ''" :aria-label="`Thích bài viết của ${commentPost.author}`" @click="toggleLike(commentPost)"><span class="material-symbols-outlined text-xl">thumb_up</span>{{ commentPost.likes }}</button>
            <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-xl">chat_bubble</span>{{ commentPost.commentsCount }}</span>
            <button type="button" class="inline-flex items-center gap-1.5 hover:text-blue-600" :aria-label="`Chia sẻ bài viết của ${commentPost.author}`" @click="sharePost(commentPost)"><span class="material-symbols-outlined text-xl">share</span></button>
          </div>
          <div class="space-y-5 pt-5">
            <div v-for="cmt in commentPost.comments || []" :key="cmt.id" class="flex items-start gap-3">
              <img :src="cmt.avatar" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover">
              <div class="min-w-0 flex-1">
                <p class="text-sm"><strong>{{ cmt.author }}</strong><span class="ml-2 text-xs text-slate-400">{{ cmt.time }}</span></p>
                <p class="mt-1 whitespace-pre-line text-sm leading-5 text-slate-700">{{ cmt.content }}</p>
                <div class="mt-1 flex items-center gap-4 text-xs font-semibold text-slate-500">
                  <button type="button" :class="cmt.isLiked ? 'text-blue-600' : 'hover:text-blue-600'" :aria-label="`Thích bình luận của ${cmt.author}`" @click="toggleCommentLike(cmt)">Thích<span v-if="cmt.likes"> · {{ cmt.likes }}</span></button>
                  <button type="button" class="hover:text-blue-600" :aria-label="`Trả lời bình luận của ${cmt.author}`" @click="replyingToCommentId = replyingToCommentId === cmt.id ? '' : cmt.id">Trả lời</button>
                </div>
                <div v-for="reply in cmt.replies || []" :key="reply.id" class="mt-3 flex items-start gap-2.5"><img :src="reply.avatar" alt="" class="h-7 w-7 shrink-0 rounded-full object-cover"><div><p class="text-sm"><strong>{{ reply.author }}</strong><span class="ml-2 text-xs text-slate-400">{{ reply.time }}</span></p><p class="mt-1 whitespace-pre-line text-sm leading-5 text-slate-700">{{ reply.content }}</p><button type="button" class="mt-1 text-xs font-semibold text-slate-500 hover:text-blue-600" :class="reply.isLiked ? 'text-blue-600' : ''" :aria-label="`Thích câu trả lời của ${reply.author}`" @click="toggleCommentLike(reply)">Thích<span v-if="reply.likes"> · {{ reply.likes }}</span></button></div></div>
                <form v-if="replyingToCommentId === cmt.id" class="mt-3 flex items-center gap-2" @submit.prevent="addReply(commentPost, cmt)"><input v-model="replyDrafts[cmt.id]" type="text" :aria-label="`Trả lời bình luận của ${cmt.author}`" :placeholder="`Trả lời ${cmt.author}...`" class="min-w-0 flex-1 rounded-full bg-slate-100 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"><button type="submit" :disabled="!replyDrafts[cmt.id]?.trim()" class="text-sm font-semibold text-blue-600 disabled:text-slate-300">Gửi</button></form>
              </div>
            </div>
          </div>
        </div>
        <form v-if="!commentPost.commentsLocked" class="flex shrink-0 items-center gap-3 px-4 py-3 sm:px-6" @submit.prevent="addComment(commentPost)"><input v-model="commentDrafts[commentPost.id]" type="text" :aria-label="`Viết bình luận cho bài viết của ${commentPost.author}`" placeholder="Viết bình luận..." class="min-w-0 flex-1 rounded-full bg-slate-100 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-blue-500"><button type="submit" :disabled="!commentDrafts[commentPost.id]?.trim()" class="grid h-9 w-9 place-items-center rounded-full text-blue-600 disabled:text-slate-300" aria-label="Đăng bình luận"><span class="material-symbols-outlined">send</span></button></form>
        <p v-else class="px-6 py-3 text-center text-xs font-semibold text-slate-500">Bài viết này đã khóa bình luận.</p>
      </section>
    </div>

    <section v-if="notificationOpen" class="fixed right-4 top-[72px] z-50 flex max-h-[min(650px,calc(100vh-88px))] w-[min(390px,calc(100vw-32px))] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/30" aria-label="Thông báo">
      <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"><div><h2 class="text-lg font-bold">Thông báo</h2></div><button type="button" class="grid h-8 w-8 place-items-center rounded-full text-slate-500 hover:bg-slate-100" aria-label="Đóng thông báo" @click="notificationOpen = false"><span class="material-symbols-outlined">close</span></button></div>
      <button v-if="unreadCount" type="button" class="self-end px-5 py-3 text-xs font-bold text-blue-600 hover:underline" @click="markAllNotificationsRead">Đánh dấu tất cả đã đọc</button>
      <p v-if="notificationError" role="alert" class="mx-4 my-3 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ notificationError }} <button class="font-semibold underline" @click="loadNotifications">Thử lại</button></p>
      <p v-if="notificationLoading" class="p-6 text-center text-sm text-slate-500">Đang tải thông báo...</p>
      <div v-else class="min-h-0 flex-1 overflow-y-auto p-2"><button v-for="item in notifications" :key="item.id" type="button" class="flex w-full gap-3 rounded-xl px-3 py-3 text-left hover:bg-slate-50" :class="!item.read_at ? 'bg-slate-50' : ''" @click="openNotification(item)"><span class="relative grid h-11 w-11 shrink-0 place-items-center">
          <img v-if="notificationAvatar(item)" :src="notificationAvatar(item)" alt="" class="h-11 w-11 rounded-full object-cover">
          <span aria-hidden="true" class="grid place-items-center rounded-full text-white" :class="[notificationStyles[item.notification_type]?.[1] || 'bg-slate-600', notificationAvatar(item) ? 'absolute -bottom-0.5 -right-0.5 h-5 w-5 ring-2 ring-white' : 'h-11 w-11']">
            <span class="material-symbols-outlined" :style="{ fontSize: notificationAvatar(item) ? '12px' : '22px' }">{{ notificationStyles[item.notification_type]?.[0] || 'notifications' }}</span>
          </span>
        </span><span class="min-w-0 flex-1"><strong class="block text-sm text-slate-900">{{ item.title }}</strong><span class="mt-1 block text-xs leading-5 text-slate-600">{{ item.body }}</span><span class="mt-1 block text-[11px] text-slate-400">{{ new Date(item.created_at).toLocaleString('vi-VN') }}</span></span><span v-if="!item.read_at" class="mt-2 h-2 w-2 shrink-0 rounded-full bg-blue-600"></span></button><p v-if="!notifications.length && !notificationError" class="p-8 text-center text-sm text-slate-500">Bạn chưa có thông báo nào.</p></div>
    </section>

    <!-- DELETE POST CONFIRMATION MODAL -->
    <div
      v-if="showDeleteConfirmModal && postToDelete"
      class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4"
      @click.self="cancelDeletePost"
      @keydown.esc="cancelDeletePost"
    >
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl space-y-4 text-slate-800 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center gap-3">
          <div class="h-11 w-11 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-2xl">warning</span>
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">Xác nhận xóa bài viết</h3>
            <p class="text-xs text-slate-500">Hành động này không thể hoàn tác</p>
          </div>
        </div>

        <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-700 border border-slate-200">
          <p v-if="postToDelete.title" class="font-bold text-slate-900 line-clamp-1 mb-1">{{ postToDelete.title }}</p>
          <p class="line-clamp-2 text-slate-600">{{ postToDelete.content }}</p>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">
          Bạn có chắc chắn muốn xóa bài viết này không? Bài viết và toàn bộ tương tác, bình luận liên quan sẽ bị xóa khỏi hệ thống.
        </p>

        <div class="flex justify-end gap-2.5 pt-2 border-t border-slate-100">
          <button
            type="button"
            class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer border-none"
            @click="cancelDeletePost"
          >
            Hủy
          </button>
          <button
            type="button"
            :disabled="socialBusy"
            class="px-4 py-2 text-xs font-bold rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition cursor-pointer border-none disabled:opacity-50"
            @click="confirmDeletePost"
          >
            {{ socialBusy ? 'Đang xóa...' : 'Xóa bài viết' }}
          </button>
        </div>
      </div>
    </div>

    <!-- PIN POST MODAL WITH EXPIRATION DATE -->
    <div
      v-if="showPinModal && postToPin"
      class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4"
      @click.self="showPinModal = false"
      @keydown.esc="showPinModal = false"
    >
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl space-y-4 text-slate-800 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center gap-3">
          <div class="h-11 w-11 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-2xl">push_pin</span>
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">Ghim bài viết lên đầu trang</h3>
            <p class="text-xs text-slate-500">Đặt lịch hẹn kết thúc hiển thị bài viết ghim</p>
          </div>
        </div>

        <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-700 border border-slate-200">
          <p v-if="postToPin.title" class="font-bold text-slate-900 line-clamp-1 mb-1">{{ postToPin.title }}</p>
          <p class="line-clamp-2 text-slate-600">{{ postToPin.content }}</p>
        </div>

        <div class="space-y-1.5">
          <label class="block text-xs font-semibold text-slate-700">Thời hạn kết thúc ghim (tùy chọn):</label>
          <input
            v-model="pinModalExpiresAt"
            type="datetime-local"
            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-white text-slate-700 outline-none focus:border-blue-600"
          />
          <p class="text-[11px] text-slate-400">Nếu để trống, bài viết sẽ được ghim liên tục cho đến khi người quản trị gỡ ghim.</p>
        </div>

        <div class="flex justify-end gap-2.5 pt-2 border-t border-slate-100">
          <button
            type="button"
            class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer border-none"
            @click="showPinModal = false"
          >
            Hủy
          </button>
          <button
            type="button"
            :disabled="socialBusy"
            class="px-4 py-2 text-xs font-bold rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition cursor-pointer border-none disabled:opacity-50"
            @click="confirmPinPost"
          >
            {{ socialBusy ? 'Đang lưu...' : 'Xác nhận ghim' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
