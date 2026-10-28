<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import type { AuthUser } from '../../api/auth'
import { profileApi } from '../../api/profile'
import { MOCK_CONVERSATIONS, MOCK_USERS } from '../../mock/mockData.js'
import { companyPages, insideChannels, type CompanySection } from './insideContent'
import { insidePosts, seedPersonalPosts } from './insidePosts'
import InsideProfileView from './InsideProfileView.vue'

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

const posts = insidePosts
const isComposerExpanded = ref(false)
const newContent = ref('')
const newChannel = ref('Tech & Architecture')
const jobTitle = ref('')
const followingCount = ref<number | null>(null)
const followersCount = ref<number | null>(null)
const activeFeed = ref<'home' | 'saved'>('home')
const selectedChannel = ref('')
const channelTab = ref<'posts' | 'members' | 'media'>('posts')
const currentChannel = computed(() => insideChannels.find(channel => channel.name === selectedChannel.value))
const channelMembers = MOCK_USERS
const companySection = ref<CompanySection | ''>(readCompanySection())
const currentCompanyPage = computed(() => companySection.value ? companyPages[companySection.value] : null)
const savedPosts = ref<string[]>([])
const commentPostId = ref('')
const commentDrafts = ref<Record<string, string>>({})
const replyingToCommentId = ref('')
const replyDrafts = ref<Record<string, string>>({})
const shareFeedback = ref('')
const messengerOpen = ref(false)
const openConversationIds = ref<string[]>([])
const chatDrafts = ref<Record<string, string>>({})
const notificationOpen = ref(false)
const notifications = ref<Array<{ id: string; title: string; body: string; created_at: string; read_at: string | null }>>([])
const notificationLoading = ref(false)
const notificationError = ref('')
const unreadCount = ref(0)
const searchQuery = ref('')
const contactSearch = ref('')
const conversations = ref<any[]>(MOCK_CONVERSATIONS.map((conversation: any) => ({ ...conversation, user: { ...conversation.user }, messages: [...conversation.messages] })))
const openConversations = computed(() => openConversationIds.value
  .map(id => conversations.value.find(conversation => conversation.id === id))
  .filter((conversation): conversation is any => Boolean(conversation)))
const commentPost = computed(() => posts.value.find(post => post.id === commentPostId.value))
const visiblePosts = computed(() => posts.value.filter((post: any) =>
  (activeFeed.value !== 'saved' || savedPosts.value.includes(post.id)) &&
  (!selectedChannel.value || post.channel === selectedChannel.value)
  && (!searchQuery.value.trim() || `${post.author} ${post.channel} ${post.content}`.toLocaleLowerCase('vi-VN').includes(searchQuery.value.trim().toLocaleLowerCase('vi-VN')))
).sort((left: any, right: any) => Number(Boolean(right.pinned)) - Number(Boolean(left.pinned))))
const filteredConversations = computed(() => conversations.value.filter((conversation: any) =>
  conversation.user.name.toLocaleLowerCase('vi-VN').includes(contactSearch.value.toLocaleLowerCase('vi-VN'))
))

onMounted(async () => {
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
    const response = await fetch('/api/v1/social/follow-stats', { headers: { Authorization: `Bearer ${props.token}`, Accept: 'application/json' } })
    if (response.ok) {
      const body = await response.json()
      followingCount.value = body.data.following_count
      followersCount.value = body.data.followers_count
    }
  } catch { /* Giữ dấu gạch khi thống kê chưa sẵn sàng. */ }
})

onUnmounted(() => window.removeEventListener('popstate', syncCompanySection))
watch(() => props.page, syncCompanySection)

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
  messengerOpen.value = !messengerOpen.value
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
  if (props.page === 'profile') emit('select-app', 'inside')
}

function showChannel(channel: string) {
  if (companySection.value) leaveCompanySection()
  activeFeed.value = 'home'
  selectedChannel.value = channel
  channelTab.value = 'posts'
  newChannel.value = channel
  if (props.page === 'profile') emit('select-app', 'inside')
}

function showSaved() {
  if (companySection.value) leaveCompanySection()
  activeFeed.value = 'saved'
  selectedChannel.value = ''
  if (props.page === 'profile') emit('select-app', 'inside')
}

function toggleSaved(postId: string) {
  savedPosts.value = savedPosts.value.includes(postId)
    ? savedPosts.value.filter(id => id !== postId)
    : [...savedPosts.value, postId]
}

function showEvents() {
  if (props.page === 'profile') emit('select-app', 'inside')
  requestAnimationFrame(() => {
  document.getElementById('upcoming-events')?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  })
}

function toggleLike(post: any) {
  post.isLiked = !post.isLiked
  post.likes += post.isLiked ? 1 : -1
}

function addComment(post: any) {
  const content = commentDrafts.value[post.id]?.trim()
  if (!content) return
  post.comments ??= []
  post.comments.push({
    id: `comment-${Date.now()}`,
    author: props.user.display_name,
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80',
    content,
    time: 'Vừa xong'
  })
  post.commentsCount = (post.commentsCount || 0) + 1
  commentDrafts.value[post.id] = ''
}

function toggleCommentLike(comment: any) {
  comment.isLiked = !comment.isLiked
  comment.likes = Math.max(0, (comment.likes || 0) + (comment.isLiked ? 1 : -1))
}

function addReply(post: any, comment: any) {
  const content = replyDrafts.value[comment.id]?.trim()
  if (!content) return
  comment.replies ??= []
  comment.replies.push({
    id: `reply-${Date.now()}`,
    author: props.user.display_name,
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80',
    content,
    time: 'Vừa xong',
    likes: 0,
    isLiked: false
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

function handleAddPost() {
  if (!newContent.value.trim()) return
  posts.value.unshift({
    id: `post-${Date.now()}`,
    author: props.user.display_name,
    role: 'Thành viên đóng góp',
    channel: newChannel.value,
    time: 'Vừa xong',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80',
    content: newContent.value.trim(),
    likes: 0,
    commentsCount: 0,
    isLiked: false,
    image: '',
    comments: []
  })
  newContent.value = ''
  isComposerExpanded.value = false
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
          <span class="hidden text-base font-extrabold tracking-wider text-slate-900 sm:inline">BASE.VN</span>
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
            <span v-if="conversations.some((conversation: any) => conversation.unread > 0)" class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-rose-500"></span>
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
              src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80"
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
      <aside class="hidden self-start overflow-hidden rounded-xl border border-slate-200 bg-white lg:sticky lg:top-20 lg:block">
        <button type="button" class="flex w-full items-center gap-3 border-l-[3px] border-blue-600 px-4 py-3 text-left text-sm font-bold text-blue-700 hover:bg-slate-50" @click="showHome"><span class="material-symbols-outlined text-xl">home</span> Bảng tin Home</button>
        <button type="button" class="flex w-full items-center gap-3 px-4 py-4 text-left hover:bg-slate-50" @click="emit('select-app', 'inside-profile')"><img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover"><span class="min-w-0"><strong class="block truncate text-sm text-slate-900">{{ user.display_name }}</strong><span class="block truncate text-xs text-slate-500">{{ jobTitle || 'Thành viên công ty' }}</span></span></button>
        <div class="grid grid-cols-2 border-y border-slate-100 text-center text-xs"><div class="border-r border-slate-100 px-2 py-3"><strong class="block text-lg text-slate-900">{{ followingCount ?? '—' }}</strong><span class="text-slate-500">Đang theo dõi</span></div><div class="px-2 py-3"><strong class="block text-lg text-slate-900">{{ followersCount ?? '—' }}</strong><span class="text-slate-500">Người theo dõi</span></div></div>
        <nav class="space-y-1 px-3 py-3 text-sm" aria-label="Điều hướng mạng xã hội"><button type="button" class="flex w-full items-center gap-3 rounded-lg px-2 py-2.5 text-left font-semibold text-slate-700 hover:bg-slate-50" @click="showEvents"><span class="material-symbols-outlined text-xl text-teal-600">event</span> Sự kiện</button><button type="button" class="flex w-full items-center gap-3 rounded-lg px-2 py-2.5 text-left font-semibold hover:bg-slate-50" :class="activeFeed === 'saved' ? 'text-blue-700' : 'text-slate-700'" @click="showSaved"><span class="material-symbols-outlined text-xl text-blue-600">bookmark</span> Đã lưu <span class="ml-auto text-xs text-slate-400">{{ savedPosts.length }}</span></button></nav>
        <div class="border-t border-slate-100 px-3 py-4 text-sm"><h2 class="mb-2 px-2 text-xs font-bold uppercase tracking-wide text-slate-500">Kênh nội bộ</h2><div class="space-y-1"><button v-for="channel in insideChannels" :key="channel.name" type="button" class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left text-slate-700 hover:bg-slate-50" @click="showChannel(channel.name)"><img :src="channel.image" alt="" class="h-8 w-8 shrink-0 rounded-full object-cover"><span class="truncate">{{ channel.name }}</span></button></div></div>
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
      <InsideProfileView v-else-if="page === 'profile'" :user="user" :token="token" @back="showHome" @settings="emit('select-app', 'profile')" @comment="commentPostId = $event" @share="sharePost" />
      <section v-else class="min-w-0 space-y-4">

        <div class="relative md:hidden"><input v-model="searchQuery" type="search" placeholder="Tìm bài viết, người, kênh..." class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm outline-none focus:border-blue-500"><span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400">search</span></div>

        <section v-if="currentChannel" class="overflow-hidden rounded-xl border border-slate-200 bg-white" :aria-label="`Kênh ${currentChannel.name}`">
          <img :src="currentChannel.cover" :alt="`Ảnh bìa kênh ${currentChannel.name}`" class="h-48 w-full object-cover object-center sm:h-60">
          <div class="px-5 pb-4"><div class="flex items-end gap-4"><img :src="currentChannel.image" alt="" class="relative -mt-8 h-16 w-16 shrink-0 rounded-full border-4 border-white object-cover"><div class="min-w-0 pb-1"><h1 class="text-lg font-bold text-slate-900">{{ currentChannel.name }}</h1><p class="text-xs text-slate-500">Kênh nội bộ · {{ channelMembers.length }} thành viên</p></div></div><p class="mt-3 text-sm leading-6 text-slate-600">{{ currentChannel.description }}</p><div class="mt-4 flex -space-x-2"><img v-for="member in channelMembers" :key="member.id" :src="member.avatar" :alt="member.name" :title="member.name" class="h-8 w-8 rounded-full border-2 border-white object-cover"></div></div>
          <nav class="flex gap-6 border-t border-slate-100 px-5 text-sm font-semibold" aria-label="Nội dung kênh"><button v-for="tab in [{ id: 'posts', label: 'Bài viết' }, { id: 'members', label: 'Thành viên' }, { id: 'media', label: 'Ảnh' }]" :key="tab.id" type="button" class="border-b-2 py-3" :class="channelTab === tab.id ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="channelTab = tab.id as typeof channelTab">{{ tab.label }}</button></nav>
        </section>

        <section v-if="currentChannel && channelTab === 'members'" class="rounded-xl border border-slate-200 bg-white p-5" aria-label="Thành viên kênh"><h2 class="mb-4 font-bold text-slate-900">Thành viên · {{ channelMembers.length }}</h2><div v-for="member in channelMembers" :key="member.id" class="flex items-center gap-3 border-t border-slate-100 py-3 first:border-t-0"><img :src="member.avatar" alt="" class="h-10 w-10 rounded-full object-cover"><div><strong class="block text-sm">{{ member.name }}</strong><span class="text-xs text-slate-500">{{ member.role }}</span></div></div></section>
        <section v-if="currentChannel && channelTab === 'media'" class="rounded-xl border border-slate-200 bg-white p-5" aria-label="Ảnh trong kênh"><h2 class="mb-4 font-bold text-slate-900">Ảnh trong kênh</h2><div class="grid grid-cols-2 gap-2 sm:grid-cols-3"><img :src="currentChannel.cover" alt="Ảnh bìa kênh" class="aspect-square w-full rounded-lg object-cover"><img v-for="post in visiblePosts.filter((item: any) => item.image)" :key="post.id" :src="post.image" :alt="`Ảnh bài viết của ${post.author}`" class="aspect-square w-full rounded-lg object-cover"></div></section>
        
        <!-- Post Creator Box -->
        <div v-if="!currentChannel || channelTab === 'posts'" class="bg-white rounded-xl border border-slate-200 p-4  space-y-3">
          <div class="flex items-center gap-3">
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80" class="w-9 h-9 rounded-full object-cover">
            <button type="button" class="flex-1 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-left text-sm text-slate-500 rounded-full border-none transition cursor-pointer" @click="isComposerExpanded = true">Chia sẻ với đồng nghiệp...</button>
          </div>

          <div v-if="isComposerExpanded" class="space-y-3 pt-2 border-t border-slate-100">
            <textarea
              v-model="newContent"
              placeholder="Nhập nội dung thông báo hoặc cập nhật của bạn..."
              rows="3"
              class="w-full p-3 bg-slate-50 text-xs rounded-lg border border-slate-200 outline-none focus:border-blue-600"
            ></textarea>
            
            <div class="flex items-center justify-between">
              <select
                v-model="newChannel"
                class="px-2.5 py-1 text-xs rounded border border-slate-200 bg-white text-slate-700 outline-none"
              >
                <option value="Tech & Architecture">Tech & Architecture</option>
                <option value="Toàn công ty">Toàn công ty</option>
                <option value="AI & Knowledge Graph">AI & Knowledge Graph</option>
                <option value="HR & Văn hóa Doanh nghiệp">HR & Văn hóa Doanh nghiệp</option>
              </select>

              <div class="flex justify-end gap-2">
                <button
                  type="button"
                  class="px-3 py-1.5 bg-slate-100 text-slate-600 text-xs rounded-md cursor-pointer border-none"
                  @click="isComposerExpanded = false"
                >
                  Hủy
                </button>
                <button
                  type="button"
                  class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-md  cursor-pointer border-none"
                  @click="handleAddPost"
                >
                  Đăng bài
                </button>
              </div>
            </div>
          </div>

          <!-- Action Items below Creator Input -->
          <div class="flex flex-wrap items-center justify-between text-xs font-semibold text-slate-600 pt-2 border-t border-slate-100">
            <button type="button" class="flex items-center gap-1.5 hover:text-blue-600 py-1 border-none bg-transparent cursor-pointer" @click="isComposerExpanded = true">
              <span class="material-symbols-outlined text-sm text-blue-500">chat</span>
              <span>Cập nhật nhanh</span>
            </button>
            <span class="text-slate-200">|</span>
            <button type="button" class="flex items-center gap-1.5 hover:text-blue-600 py-1 border-none bg-transparent cursor-pointer" @click="isComposerExpanded = true">
              <span class="material-symbols-outlined text-sm text-amber-500">campaign</span>
              <span>Thông báo</span>
            </button>
            <span class="text-slate-200">|</span>
            <button type="button" class="flex items-center gap-1.5 hover:text-blue-600 py-1 border-none bg-transparent cursor-pointer" @click="isComposerExpanded = true">
              <span class="material-symbols-outlined text-sm text-emerald-500">poll</span>
              <span>Tạo bình chọn</span>
            </button>
            <span class="text-slate-200">|</span>
            <button type="button" class="flex items-center gap-1.5 hover:text-blue-600 py-1 border-none bg-transparent cursor-pointer" @click="isComposerExpanded = true">
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
          <div v-if="post.pinned" class="flex items-center gap-1.5 border-b border-slate-100 pb-3 text-xs font-semibold text-blue-600"><span class="material-symbols-outlined text-base">push_pin</span> Bài viết được ghim</div>
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
            <button type="button" class="text-slate-400 hover:text-blue-600 border-none bg-transparent cursor-pointer" :title="savedPosts.includes(post.id) ? 'Bỏ lưu bài viết' : 'Lưu bài viết'" @click="toggleSaved(post.id)">
              <span class="material-symbols-outlined text-base">{{ savedPosts.includes(post.id) ? 'bookmark_added' : 'bookmark_add' }}</span>
            </button>
          </div>

          <!-- Content Body -->
          <h2 v-if="post.title" class="text-lg font-bold text-slate-900">{{ post.title }}</h2>
          <p class="text-sm text-slate-800 leading-relaxed whitespace-pre-line">
            {{ post.content }}
          </p>

          <!-- Attached Image if any -->
          <div v-if="post.image" class="rounded-xl overflow-hidden border border-slate-100 max-h-[520px]">
            <img :src="post.image" :alt="`Ảnh đính kèm bài viết của ${post.author}`" class="w-full h-full object-cover">
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
        <!-- Events Widget -->
        <div id="upcoming-events" class="bg-white rounded-xl border border-slate-200 p-5  space-y-3 text-xs">
          <h3 class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
            <span class="material-symbols-outlined text-teal-600 text-base">event</span>
            <span>Sự kiện sắp tới</span>
          </h3>
          <div class="space-y-2">
            <div class="border-l-2 border-teal-500 pl-2.5 py-1 text-slate-700">
              <span class="font-bold block text-slate-900">Chia sẻ Tri thức Graph RAG</span>
              <span class="text-[11px] text-slate-400">Thứ 6, 14:00 • Phòng họp 3</span>
            </div>
            <div class="border-l-2 border-blue-500 pl-2.5 py-1 text-slate-700">
              <span class="font-bold block text-slate-900">Demo Base Square v2</span>
              <span class="text-[11px] text-slate-400">Thứ 2 tới • Online Google Meet</span>
            </div>
          </div>
        </div>


      </aside>


    </main>

    <aside class="fixed right-3 bottom-5 z-30 hidden flex-col items-center gap-3 xl:flex" aria-label="Liên hệ nhanh">
      <button v-for="conversation in conversations" :key="conversation.id" type="button" class="relative rounded-full" :title="`Nhắn tin với ${conversation.user.name}`" @click="openConversation(conversation.id)"><img :src="conversation.user.avatar" :alt="conversation.user.name" class="h-11 w-11 rounded-full object-cover"><span v-if="conversation.user.online" class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-emerald-500"></span><span v-if="conversation.unread" class="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full bg-blue-600"></span></button>
      <button type="button" class="grid h-11 w-11 place-items-center rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-blue-600" title="Xem tất cả đoạn chat" @click="toggleMessenger"><span class="material-symbols-outlined">edit_square</span></button>
    </aside>

    <div v-if="messengerOpen || notificationOpen" class="fixed inset-0 z-40" @click="messengerOpen = false; notificationOpen = false"></div>
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
        <form class="flex shrink-0 items-center gap-3 px-4 py-3 sm:px-6" @submit.prevent="addComment(commentPost)"><input v-model="commentDrafts[commentPost.id]" type="text" :aria-label="`Viết bình luận cho bài viết của ${commentPost.author}`" placeholder="Viết bình luận..." class="min-w-0 flex-1 rounded-full bg-slate-100 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-blue-500"><button type="submit" :disabled="!commentDrafts[commentPost.id]?.trim()" class="grid h-9 w-9 place-items-center rounded-full text-blue-600 disabled:text-slate-300" aria-label="Đăng bình luận"><span class="material-symbols-outlined">send</span></button></form>
      </section>
    </div>

    <section v-if="notificationOpen" class="fixed right-4 top-[72px] z-50 flex max-h-[min(650px,calc(100vh-88px))] w-[min(390px,calc(100vw-32px))] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white " aria-label="Thông báo">
      <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"><div><h2 class="text-lg font-bold">Thông báo</h2><p class="text-xs text-slate-500">{{ unreadCount }} thông báo chưa đọc</p></div><button type="button" class="grid h-8 w-8 place-items-center rounded-full text-slate-500 hover:bg-slate-100" aria-label="Đóng thông báo" @click="notificationOpen = false"><span class="material-symbols-outlined">close</span></button></div>
      <button v-if="unreadCount" type="button" class="self-end px-5 py-3 text-xs font-bold text-blue-600 hover:underline" @click="markAllNotificationsRead">Đánh dấu tất cả đã đọc</button>
      <p v-if="notificationError" role="alert" class="mx-4 my-3 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ notificationError }} <button class="font-semibold underline" @click="loadNotifications">Thử lại</button></p>
      <p v-if="notificationLoading" class="p-6 text-center text-sm text-slate-500">Đang tải thông báo...</p>
      <div v-else class="min-h-0 flex-1 overflow-y-auto p-2"><button v-for="item in notifications" :key="item.id" type="button" class="flex w-full gap-3 rounded-xl px-3 py-3 text-left hover:bg-slate-50" :class="!item.read_at ? 'bg-slate-50' : ''" @click="markNotificationRead(item.id)"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-full text-blue-600"><span class="material-symbols-outlined text-xl">notifications</span></span><span class="min-w-0 flex-1"><strong class="block text-sm text-slate-900">{{ item.title }}</strong><span class="mt-1 block text-xs leading-5 text-slate-600">{{ item.body }}</span><span class="mt-1 block text-[11px] text-slate-400">{{ new Date(item.created_at).toLocaleString('vi-VN') }}</span></span><span v-if="!item.read_at" class="mt-2 h-2 w-2 shrink-0 rounded-full bg-blue-600"></span></button><p v-if="!notifications.length && !notificationError" class="p-8 text-center text-sm text-slate-500">Bạn chưa có thông báo nào.</p></div>
    </section>

  </div>
</template>
