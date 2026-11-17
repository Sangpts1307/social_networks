<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import type { AuthUser } from '../../api/auth'
import { chatApi, type ChatMessageDto, type ConversationDto, type ConversationMemberDto } from '../../api/chat'
import { socialApi, type DirectoryUserDto } from '../../api/social'
import { MOCK_CONVERSATIONS } from '../../mock/mockData.js'

interface UiAttachment {
  id: string
  name: string
  size: string
  type: 'image' | 'pdf' | 'doc' | 'sheet' | 'archive' | 'file'
  url?: string
  mimeType?: string
}

interface UiMessage {
  id: string
  fromMe: boolean
  senderName?: string
  senderAvatar?: string
  text: string
  time: string
  attachments?: UiAttachment[]
}

interface UiConversation {
  id: string
  type: 'direct' | 'group'
  title: string
  subtitle: string
  avatar: string
  online: boolean
  lastMessage: string
  lastTime: string
  unread: number
  memberCount: number
  directPeerId?: string
  messages: UiMessage[]
  membersLoaded?: ConversationMemberDto[]
}

interface StagedAttachment {
  id: string
  file: File
  name: string
  sizeText: string
  type: 'image' | 'pdf' | 'doc' | 'sheet' | 'archive' | 'file'
  previewUrl?: string
}

const DEFAULT_AVATAR = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80'

const props = defineProps<{
  user: AuthUser
  token?: string
}>()

const emit = defineEmits<{
  (e: 'back'): void
  (e: 'select-app', appId: string): void
  (e: 'logout'): void
}>()

const canCreateGroup = computed(() =>
  (props.user.permissions ?? []).includes('chat.groups.create'),
)

const apiConnected = ref(false)
const loading = ref(false)
const busy = ref(false)
const errorMsg = ref('')

const directory = ref<Record<string, DirectoryUserDto>>({})
const directoryList = computed(() =>
  Object.values(directory.value).filter(u => u.id !== props.user.id),
)

const conversations = ref<UiConversation[]>([])
const activeConvId = ref('')
const messageInput = ref('')
const activeTab = ref<'all' | 'group'>('all')
const searchQuery = ref('')

// File upload & attachment state
const fileInputRef = ref<HTMLInputElement | null>(null)
const stagedAttachments = ref<StagedAttachment[]>([])
const previewImageModal = ref<{ url: string; name: string } | null>(null)

// Create Direct / Group modals
const showNewDirectModal = ref(false)
const showCreateGroupModal = ref(false)
const modalSearch = ref('')
const newGroupTitle = ref('')
const selectedMemberIds = ref<string[]>([])

// Group management in Panel 3
const showAddMemberPicker = ref(false)
const editingTitle = ref(false)
const editTitleValue = ref('')

const activeConversation = computed(() =>
  conversations.value.find(c => c.id === activeConvId.value) ?? null,
)

const filteredConversations = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  return conversations.value.filter(conv => {
    if (activeTab.value === 'group' && conv.type !== 'group') return false
    if (!q) return true
    return (
      conv.title.toLowerCase().includes(q) ||
      conv.lastMessage.toLowerCase().includes(q) ||
      (conv.subtitle && conv.subtitle.toLowerCase().includes(q))
    )
  })
})

const filteredDirectory = computed(() => {
  const q = modalSearch.value.trim().toLowerCase()
  if (!q) return directoryList.value
  return directoryList.value.filter(u =>
    u.display_name.toLowerCase().includes(q) ||
    (u.job_title ?? '').toLowerCase().includes(q),
  )
})

const isGroupOwner = computed(() => {
  const conv = activeConversation.value
  if (!conv || conv.type !== 'group' || !conv.membersLoaded) return false
  return conv.membersLoaded.some(m => m.userId === props.user.id && m.memberRole === 'owner')
})

const availableToAdd = computed(() => {
  const conv = activeConversation.value
  if (!conv) return []
  const existing = new Set((conv.membersLoaded ?? []).map(m => m.userId))
  return directoryList.value.filter(u => !existing.has(u.id))
})

// All attachments shared across messages in the active conversation
const conversationAttachments = computed(() => {
  const conv = activeConversation.value
  if (!conv) return []
  const list: { messageTime: string; senderName: string; attachment: UiAttachment }[] = []
  for (const m of conv.messages) {
    if (m.attachments && m.attachments.length > 0) {
      for (const att of m.attachments) {
        list.push({
          messageTime: m.time,
          senderName: m.fromMe ? 'Bạn' : (m.senderName || conv.title),
          attachment: att,
        })
      }
    }
  }
  return list
})

function formatTime(iso?: string | null): string {
  if (!iso) return ''
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

function resolveUserName(userId: string): string {
  if (userId === props.user.id) return props.user.display_name
  return directory.value[userId]?.display_name ?? 'Đồng nghiệp'
}

function resolveUserRole(userId: string): string {
  return directory.value[userId]?.job_title ?? 'Thành viên'
}

function formatFileSize(bytes: number): string {
  if (!bytes || Number.isNaN(bytes)) return '1.2 MB'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function detectFileType(filename: string, mime?: string): UiAttachment['type'] {
  const m = (mime || '').toLowerCase()
  const ext = (filename || '').split('.').pop()?.toLowerCase() || ''
  if (m.startsWith('image/') || ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'].includes(ext)) return 'image'
  if (m === 'application/pdf' || ext === 'pdf') return 'pdf'
  if (['doc', 'docx', 'txt', 'rtf', 'md', 'yaml', 'json'].includes(ext) || m.includes('word') || m.includes('document')) return 'doc'
  if (['xls', 'xlsx', 'csv'].includes(ext) || m.includes('sheet') || m.includes('excel')) return 'sheet'
  if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext) || m.includes('zip') || m.includes('archive')) return 'archive'
  return 'file'
}

function getFileIcon(type: UiAttachment['type']): string {
  switch (type) {
    case 'image':
      return 'image'
    case 'pdf':
      return 'picture_as_pdf'
    case 'sheet':
      return 'table_chart'
    case 'doc':
      return 'description'
    case 'archive':
      return 'folder_zip'
    default:
      return 'draft'
  }
}

function getFileBadgeClass(type: UiAttachment['type'], fromMe: boolean): string {
  if (fromMe) {
    return 'bg-white/20 text-white'
  }
  switch (type) {
    case 'pdf':
      return 'bg-rose-100 text-rose-600'
    case 'sheet':
      return 'bg-emerald-100 text-emerald-600'
    case 'doc':
      return 'bg-blue-100 text-blue-600'
    case 'archive':
      return 'bg-amber-100 text-amber-700'
    case 'image':
      return 'bg-purple-100 text-purple-600'
    default:
      return 'bg-slate-100 text-slate-600'
  }
}

function mapDtoConversation(dto: ConversationDto): UiConversation {
  const id = String(dto.id ?? dto._id ?? '')
  let directPeerId: string | undefined
  if (dto.type === 'direct' && dto.directKey) {
    directPeerId = dto.directKey.split(':').find(uid => uid !== props.user.id)
  }
  const title = dto.type === 'group'
    ? (dto.title?.trim() || 'Nhóm chat')
    : (directPeerId ? resolveUserName(directPeerId) : 'Trò chuyện trực tiếp')
  const subtitle = dto.type === 'group'
    ? `${dto.memberCount} thành viên`
    : (directPeerId ? resolveUserRole(directPeerId) : 'Nội bộ')

  return {
    id,
    type: dto.type,
    title,
    subtitle,
    avatar: DEFAULT_AVATAR,
    online: dto.type === 'direct',
    lastMessage: dto.lastMessage ? 'Đã có tin nhắn mới' : 'Bắt đầu cuộc trò chuyện',
    lastTime: formatTime(dto.lastMessage?.sentAt ?? dto.updatedAt ?? dto.createdAt),
    unread: dto.unread_count ?? 0,
    memberCount: dto.memberCount,
    directPeerId,
    messages: [],
  }
}

function mapDtoMessage(dto: ChatMessageDto): UiMessage {
  const attachments: UiAttachment[] = (dto.attachments ?? []).map(a => ({
    id: a.id,
    name: a.filename,
    size: formatFileSize(a.size_bytes),
    type: detectFileType(a.filename, a.mime_type),
    url: a.url,
    mimeType: a.mime_type,
  }))

  return {
    id: String(dto.id ?? dto._id ?? dto.clientMessageId),
    fromMe: dto.senderUserId === props.user.id,
    senderName: resolveUserName(dto.senderUserId),
    text: dto.bodyPlaintext || dto.bodyMarkdown || '',
    time: formatTime(dto.sentAt ?? dto.createdAt),
    attachments: attachments.length > 0 ? attachments : undefined,
  }
}

function mapMockConversations(): UiConversation[] {
  return MOCK_CONVERSATIONS.map((c: any) => ({
    id: c.id,
    type: c.type || 'direct',
    title: c.type === 'group' ? c.title : (c.user?.name || c.title),
    subtitle: c.type === 'group' ? (c.subtitle || `${c.memberCount || 5} thành viên`) : (c.user?.role || c.subtitle),
    avatar: c.type === 'group' ? '' : (c.user?.avatar || c.avatar || DEFAULT_AVATAR),
    online: Boolean(c.user?.online || c.online),
    lastMessage: c.lastMessage,
    lastTime: c.lastTime,
    unread: c.unread ?? 0,
    memberCount: c.memberCount || (c.members?.length ?? (c.type === 'group' ? 5 : 2)),
    membersLoaded: c.members ? c.members.map((m: any) => ({
      userId: m.userId || `u-${Math.random()}`,
      memberRole: m.memberRole || 'member',
      joinedAt: '2026-09-01T00:00:00Z',
    })) : undefined,
    messages: (c.messages ?? []).map((m: any) => ({
      id: m.id,
      fromMe: Boolean(m.fromMe),
      senderName: m.senderName,
      senderAvatar: m.senderAvatar,
      text: m.text,
      time: m.time,
      attachments: m.attachments ?? [],
    })),
  }))
}

async function loadConversationDetails(conv: UiConversation) {
  if (!apiConnected.value || !props.token) return
  try {
    const [detailRes, msgRes] = await Promise.all([
      chatApi.conversation(props.token, conv.id),
      chatApi.messages(props.token, conv.id),
    ])
    conv.membersLoaded = detailRes.members
    conv.memberCount = detailRes.members.length
    if (conv.type === 'group') {
      conv.subtitle = `${conv.memberCount} thành viên`
    } else if (!conv.directPeerId) {
      const peer = detailRes.members.find(m => m.userId !== props.user.id)
      if (peer) {
        conv.directPeerId = peer.userId
        conv.title = resolveUserName(peer.userId)
        conv.subtitle = resolveUserRole(peer.userId)
      }
    }
    const msgs = [...msgRes.data].reverse().map(mapDtoMessage)
    conv.messages = msgs
    if (msgs.length > 0) {
      const last = msgs[msgs.length - 1]!
      conv.lastMessage = last.text || (last.attachments?.length ? `📎 ${last.attachments[0]?.name}` : '')
      conv.lastTime = last.time
    }
    if (conv.unread > 0) {
      conv.unread = 0
      await chatApi.markRead(props.token, conv.id).catch(() => {})
    }
  } catch (err) {
    errorMsg.value = err instanceof Error ? err.message : 'Không thể tải chi tiết cuộc trò chuyện.'
  }
}

async function loadInitial() {
  if (!props.token) {
    conversations.value = mapMockConversations()
    if (conversations.value.length > 0) {
      activeConvId.value = conversations.value[0]!.id
      conversations.value[0]!.unread = 0 // Clear unread on initial active item
    }
    return
  }

  loading.value = true
  errorMsg.value = ''
  try {
    const [usersRes, convsRes] = await Promise.all([
      socialApi.directoryUsers(props.token).catch(() => ({ data: [] as DirectoryUserDto[] })),
      chatApi.conversations(props.token),
    ])
    const map: Record<string, DirectoryUserDto> = {}
    for (const u of usersRes.data) {
      map[u.id] = u
    }
    directory.value = map
    apiConnected.value = true
    conversations.value = convsRes.data.map(mapDtoConversation)
    if (conversations.value.length > 0) {
      activeConvId.value = conversations.value[0]!.id
      conversations.value[0]!.unread = 0 // Clear unread on initial active item
      await loadConversationDetails(conversations.value[0]!)
    }
  } catch (err) {
    // Fallback to rich mock data when backend is not connected
    apiConnected.value = false
    conversations.value = mapMockConversations()
    if (conversations.value.length > 0) {
      activeConvId.value = conversations.value[0]!.id
      conversations.value[0]!.unread = 0
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadInitial()
})

async function selectConversation(conv: UiConversation) {
  activeConvId.value = conv.id
  conv.unread = 0 // Immediately clear badge on click (matching Image 3 effect)
  editingTitle.value = false
  showAddMemberPicker.value = false
  if (apiConnected.value && props.token) {
    await loadConversationDetails(conv)
  }
}

// File Attachment handling
function triggerFileInput() {
  fileInputRef.value?.click()
}

function handleFileSelected(e: Event) {
  const input = e.target as HTMLInputElement
  if (!input.files || input.files.length === 0) return
  const files = Array.from(input.files)
  for (const f of files) {
    const fType = detectFileType(f.name, f.type)
    const staged: StagedAttachment = {
      id: `staged-${Date.now()}-${Math.random().toString(36).substring(2, 7)}`,
      file: f,
      name: f.name,
      sizeText: formatFileSize(f.size),
      type: fType,
      previewUrl: fType === 'image' ? URL.createObjectURL(f) : undefined,
    }
    stagedAttachments.value.push(staged)
  }
  input.value = ''
}

function removeStagedAttachment(id: string) {
  const idx = stagedAttachments.value.findIndex(a => a.id === id)
  if (idx !== -1) {
    const item = stagedAttachments.value[idx]!
    if (item.previewUrl) URL.revokeObjectURL(item.previewUrl)
    stagedAttachments.value.splice(idx, 1)
  }
}

function downloadAttachment(att: UiAttachment) {
  if (att.url) {
    const a = document.createElement('a')
    a.href = att.url
    a.download = att.name
    a.target = '_blank'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
  } else {
    // Generate sample blob for downloadable mock document
    const content = `Tài liệu Openhello.vn: ${att.name}\nKích thước: ${att.size}\nLoại định dạng: ${att.type.toUpperCase()}\nThời gian đồng bộ: ${new Date().toLocaleString('vi-VN')}\n\nTệp tin đã được xác thực an toàn bởi Openhello File Guard Service.`
    const blob = new Blob([content], { type: att.type === 'pdf' ? 'application/pdf' : 'text/plain;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = att.name
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => URL.revokeObjectURL(url), 1000)
  }
}

function openImageModal(att: UiAttachment) {
  if (att.url) {
    previewImageModal.value = { url: att.url, name: att.name }
  }
}

async function sendMessage() {
  const text = messageInput.value.trim()
  const conv = activeConversation.value
  const hasFiles = stagedAttachments.value.length > 0
  if ((!text && !hasFiles) || !conv) return

  const attachmentsToSend: UiAttachment[] = stagedAttachments.value.map(sf => ({
    id: `att-${Date.now()}-${Math.random().toString(36).substring(2, 7)}`,
    name: sf.name,
    size: sf.sizeText,
    type: sf.type,
    url: sf.previewUrl || URL.createObjectURL(sf.file),
    mimeType: sf.file.type,
  }))

  const filesToUpload = [...stagedAttachments.value]
  stagedAttachments.value = []
  messageInput.value = ''

  if (apiConnected.value && props.token) {
    const clientMsgId = crypto.randomUUID()
    try {
      const uploadedIds: string[] = []
      for (const item of filesToUpload) {
        try {
          const upRes = await chatApi.uploadAttachment(props.token, conv.id, item.file)
          if (upRes?.data?.id) uploadedIds.push(upRes.data.id)
        } catch (e) {
          console.warn('API attachment upload fallback', e)
        }
      }
      const res = await chatApi.sendMessage(
        props.token,
        conv.id,
        text || (attachmentsToSend.length ? `[Đã gửi ${attachmentsToSend.length} tệp đính kèm]` : ''),
        clientMsgId,
        uploadedIds,
      )
      const mapped = mapDtoMessage(res.data)
      if (attachmentsToSend.length > 0) {
        mapped.attachments = attachmentsToSend
      }
      conv.messages.push(mapped)
      conv.lastMessage = mapped.text || `${attachmentsToSend[0]?.name || 'Tệp đính kèm'}`
      conv.lastTime = mapped.time || 'Vừa xong'
    } catch (err) {
      errorMsg.value = err instanceof Error ? err.message : 'Không gửi được tin nhắn.'
    }
  } else {
    // Mock / Standalone mode
    const newMsg: UiMessage = {
      id: `m-${Date.now()}`,
      fromMe: true,
      text,
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }),
      attachments: attachmentsToSend.length > 0 ? attachmentsToSend : undefined,
    }
    conv.messages.push(newMsg)
    conv.lastMessage = text || (attachmentsToSend.length ? `📎 ${attachmentsToSend[0]?.name}` : '')
    conv.lastTime = 'Vừa xong'
  }
}

function openNewDirect() {
  modalSearch.value = ''
  errorMsg.value = ''
  showNewDirectModal.value = true
}

function openCreateGroup() {
  modalSearch.value = ''
  newGroupTitle.value = ''
  selectedMemberIds.value = []
  errorMsg.value = ''
  showCreateGroupModal.value = true
}

function toggleMemberSelection(userId: string) {
  if (selectedMemberIds.value.includes(userId)) {
    selectedMemberIds.value = selectedMemberIds.value.filter(id => id !== userId)
  } else {
    selectedMemberIds.value.push(userId)
  }
}

async function startDirectChat(peer: DirectoryUserDto) {
  showNewDirectModal.value = false
  if (apiConnected.value && props.token) {
    busy.value = true
    errorMsg.value = ''
    try {
      const res = await chatApi.createConversation(props.token, {
        type: 'direct',
        member_ids: [peer.id],
      })
      const mapped = mapDtoConversation(res.data)
      if (!conversations.value.some(c => c.id === mapped.id)) {
        conversations.value.unshift(mapped)
      }
      const target = conversations.value.find(c => c.id === mapped.id) ?? mapped
      await selectConversation(target)
    } catch (err) {
      errorMsg.value = err instanceof Error ? err.message : 'Không thể tạo cuộc trò chuyện.'
    } finally {
      busy.value = false
    }
  } else {
    const existing = conversations.value.find(c => c.title === peer.display_name)
    if (existing) {
      await selectConversation(existing)
    } else {
      const conv: UiConversation = {
        id: `local-${Date.now()}`,
        type: 'direct',
        title: peer.display_name,
        subtitle: peer.job_title ?? 'Đồng nghiệp',
        avatar: DEFAULT_AVATAR,
        online: true,
        lastMessage: 'Bắt đầu cuộc trò chuyện',
        lastTime: 'Vừa xong',
        unread: 0,
        memberCount: 2,
        messages: [],
      }
      conversations.value.unshift(conv)
      activeConvId.value = conv.id
    }
  }
}

async function handleCreateGroup() {
  const title = newGroupTitle.value.trim()
  if (!title || selectedMemberIds.value.length === 0) return

  busy.value = true
  errorMsg.value = ''
  try {
    if (apiConnected.value && props.token) {
      const res = await chatApi.createConversation(props.token, {
        type: 'group',
        title,
        member_ids: selectedMemberIds.value,
      })
      const mapped = mapDtoConversation(res.data)
      conversations.value.unshift(mapped)
      showCreateGroupModal.value = false
      await selectConversation(mapped)
    } else {
      const conv: UiConversation = {
        id: `group-${Date.now()}`,
        type: 'group',
        title,
        subtitle: `${selectedMemberIds.value.length + 1} thành viên`,
        avatar: '',
        online: false,
        lastMessage: 'Nhóm chat mới được tạo',
        lastTime: 'Vừa xong',
        unread: 0,
        memberCount: selectedMemberIds.value.length + 1,
        messages: [],
      }
      conversations.value.unshift(conv)
      showCreateGroupModal.value = false
      activeConvId.value = conv.id
    }
  } catch (err) {
    errorMsg.value = err instanceof Error ? err.message : 'Không thể tạo nhóm chat.'
  } finally {
    busy.value = false
  }
}

async function handleRenameGroup() {
  const conv = activeConversation.value
  const title = editTitleValue.value.trim()
  if (!conv || !title) return
  if (apiConnected.value && props.token) {
    busy.value = true
    try {
      await chatApi.updateConversation(props.token, conv.id, title)
      conv.title = title
      editingTitle.value = false
    } catch (err) {
      errorMsg.value = err instanceof Error ? err.message : 'Không thể đổi tên nhóm.'
    } finally {
      busy.value = false
    }
  } else {
    conv.title = title
    editingTitle.value = false
  }
}

async function handleAddGroupMember(userId: string) {
  const conv = activeConversation.value
  if (!conv) return
  if (apiConnected.value && props.token) {
    busy.value = true
    try {
      await chatApi.addMember(props.token, conv.id, userId)
      showAddMemberPicker.value = false
      await loadConversationDetails(conv)
    } catch (err) {
      errorMsg.value = err instanceof Error ? err.message : 'Không thể thêm thành viên.'
    } finally {
      busy.value = false
    }
  } else {
    showAddMemberPicker.value = false
    conv.memberCount += 1
    conv.subtitle = `${conv.memberCount} thành viên`
  }
}

async function handleRemoveOrLeaveMember(userId: string) {
  const conv = activeConversation.value
  if (!conv) return
  if (apiConnected.value && props.token) {
    busy.value = true
    try {
      await chatApi.removeMember(props.token, conv.id, userId)
      if (userId === props.user.id) {
        conversations.value = conversations.value.filter(c => c.id !== conv.id)
        activeConvId.value = conversations.value[0]?.id ?? ''
      } else {
        await loadConversationDetails(conv)
      }
    } catch (err) {
      errorMsg.value = err instanceof Error ? err.message : 'Thao tác không thành công.'
    } finally {
      busy.value = false
    }
  } else {
    if (userId === props.user.id) {
      conversations.value = conversations.value.filter(c => c.id !== conv.id)
      activeConvId.value = conversations.value[0]?.id ?? ''
    } else {
      conv.memberCount = Math.max(1, conv.memberCount - 1)
      conv.subtitle = `${conv.memberCount} thành viên`
    }
  }
}
</script>

<template>
  <div class="h-screen flex flex-col bg-slate-100 font-sans text-slate-800 overflow-hidden select-text">
    <!-- TOP APP NAVBAR -->
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200 flex-shrink-0">
      <div class="max-w-[1440px] mx-auto px-4 lg:px-8 h-14 flex items-center justify-between gap-4">
        <button
          type="button"
          class="flex items-center gap-2.5 cursor-pointer border-none bg-transparent"
          title="Về cổng Portal"
          @click="emit('back')"
        >
          <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
            <span class="material-symbols-outlined text-base">change_history</span>
          </div>
          <span class="text-base font-extrabold tracking-wider text-slate-900">Openhello.vn.vn</span>
        </button>

        <div class="flex items-center gap-3">
          <!-- SEARCH BAR WITH VERTICALLY CENTERED ICON -->
          <div class="relative w-64 sm:w-80 hidden sm:block">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Tìm kiếm tin nhắn, người, nhóm..."
              class="w-full h-9 pl-3.5 pr-9 bg-slate-50 hover:bg-slate-100 focus:bg-white text-xs rounded-lg border border-slate-200 focus:border-blue-600 outline-none transition"
            >
            <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-base pointer-events-none flex items-center justify-center leading-none">search</span>
          </div>

          <button
            type="button"
            class="flex items-center gap-2 p-1 rounded-md hover:bg-slate-100 cursor-pointer border-none bg-transparent"
            title="Xem trang cá nhân"
            @click="emit('select-app', 'profile')"
          >
            <img
              :src="DEFAULT_AVATAR"
              alt="Avatar"
              class="w-7 h-7 rounded-full object-cover border border-slate-200"
            >
            <span class="text-xs font-bold text-slate-800 hidden sm:block">{{ props.user.display_name }}</span>
          </button>

          <button
            type="button"
            class="px-2.5 py-1 text-xs font-semibold text-slate-500 hover:text-rose-600 rounded cursor-pointer border-none bg-transparent"
            title="Đăng xuất"
            @click="emit('logout')"
          >
            <span class="material-symbols-outlined text-base align-middle">logout</span>
          </button>
        </div>
      </div>
    </header>

    <div v-if="errorMsg" class="max-w-[1440px] mx-auto w-full px-4 lg:px-8 pt-2">
      <div class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700 flex items-center justify-between">
        <span>{{ errorMsg }}</span>
        <button type="button" class="text-rose-600 font-bold bg-transparent border-none cursor-pointer" @click="errorMsg = ''">Đóng</button>
      </div>
    </div>

    <!-- 3-PANEL MESSENGER CONTAINER -->
    <main class="max-w-[1440px] mx-auto w-full px-4 lg:px-8 py-3 flex-1 flex gap-4 h-[calc(100vh-61px)] overflow-hidden">
      <!-- PANEL 1: CONVERSATIONS LIST -->
      <section class="w-80 bg-white rounded-lg border border-slate-200 flex flex-col shadow-xs flex-shrink-0 overflow-hidden">
        <div class="p-3.5 border-b border-slate-100 space-y-2.5">
          <!-- TITLE (CLEAN HEADER, REMOVED "+" AND "POST_ADD") -->
          <div class="flex items-center justify-between gap-2">
            <h1 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <span class="material-symbols-outlined text-blue-600 text-lg">chat</span>
              Hộp thoại Message
            </h1>
          </div>

          <!-- ONLY 2 TABS: "TẤT CẢ" VÀ "NHÓM CHAT" -->
          <div class="flex bg-slate-100 p-0.5 rounded-lg text-xs font-semibold text-slate-600">
            <button
              type="button"
              class="flex-1 py-1.5 rounded-md transition border-none cursor-pointer text-center"
              :class="activeTab === 'all' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'hover:text-slate-900 bg-transparent'"
              @click="activeTab = 'all'"
            >
              Tất cả
            </button>
            <button
              type="button"
              class="flex-1 py-1.5 rounded-md transition border-none cursor-pointer text-center"
              :class="activeTab === 'group' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'hover:text-slate-900 bg-transparent'"
              @click="activeTab = 'group'"
            >
              Nhóm chat
            </button>
          </div>
        </div>

        <!-- CONVERSATIONS SCROLL LIST (CLICK EFFECT IDENTICAL TO IMAGE 3, NO HORIZONTAL BLUE LINE) -->
        <div class="flex-1 overflow-y-auto text-xs">
          <div v-if="loading" class="p-4 text-center text-slate-400">Đang tải hội thoại...</div>
          <div v-else-if="filteredConversations.length === 0" class="p-6 text-center text-slate-400 space-y-2">
            <p>Chưa có cuộc trò chuyện nào phù hợp.</p>
            <button
              v-if="activeTab === 'group' && canCreateGroup"
              type="button"
              class="px-3 py-1.5 rounded-md bg-blue-50 hover:bg-blue-100 text-blue-600 font-semibold border border-blue-200 cursor-pointer text-xs"
              @click="openCreateGroup"
            >
              + Tạo nhóm chat mới
            </button>
            <button
              v-else-if="activeTab === 'all'"
              type="button"
              class="px-3 py-1.5 rounded-md bg-blue-50 hover:bg-blue-100 text-blue-600 font-semibold border border-blue-200 cursor-pointer text-xs"
              @click="openNewDirect"
            >
              Nhắn tin cho đồng nghiệp
            </button>
          </div>
          <div
            v-for="conv in filteredConversations"
            :key="conv.id"
            class="p-3 flex items-start gap-3 cursor-pointer transition border-l-4"
            :class="conv.id === activeConvId
              ? 'bg-blue-50/70 border-l-blue-600 border-t-0 border-r-0 border-b-0'
              : 'border-l-transparent hover:bg-slate-50 border-b border-slate-100/60'"
            @click="selectConversation(conv)"
          >
            <!-- Avatar / Icon -->
            <div class="relative flex-shrink-0">
              <div v-if="conv.type === 'group'" class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-lg">groups</span>
              </div>
              <img v-else :src="conv.avatar" class="w-10 h-10 rounded-full object-cover">
              <span
                v-if="conv.type === 'direct' && conv.online"
                class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white"
              ></span>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-1">
                <span class="font-bold text-slate-900 truncate">{{ conv.title }}</span>
                <span class="text-[10px] text-slate-400 flex-shrink-0">{{ conv.lastTime }}</span>
              </div>
              <p class="text-slate-500 truncate mt-0.5">{{ conv.lastMessage }}</p>
            </div>

            <!-- Unread badge (Only shown if unread > 0 and NOT the active conversation) -->
            <div v-if="conv.unread > 0 && conv.id !== activeConvId" class="flex-shrink-0">
              <span class="w-4 h-4 rounded-full bg-blue-600 text-white text-[9px] font-bold flex items-center justify-center">
                {{ conv.unread }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- PANEL 2: ACTIVE CHAT THREAD -->
      <section v-if="activeConversation" class="flex-1 bg-white rounded-lg border border-slate-200 flex flex-col shadow-xs overflow-hidden">
        <!-- Thread Header -->
        <div class="p-3.5 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
          <div class="flex items-center gap-3">
            <div v-if="activeConversation.type === 'group'" class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center">
              <span class="material-symbols-outlined text-lg">groups</span>
            </div>
            <img v-else :src="activeConversation.avatar" class="w-9 h-9 rounded-full object-cover">
            <div>
              <h2 class="text-sm font-bold text-slate-900 leading-tight">{{ activeConversation.title }}</h2>
              <span class="text-[11px] text-slate-400 block">
                {{ activeConversation.subtitle }}
                <template v-if="activeConversation.type === 'direct'"> • {{ activeConversation.online ? 'Đang hoạt động' : 'Offline' }}</template>
              </span>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <span v-if="activeConversation.type === 'group'" class="text-[11px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-semibold border border-blue-200">
              Nhóm chat nội bộ
            </span>
          </div>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 p-4 overflow-y-auto space-y-3 text-xs bg-slate-50/40">
          <div v-if="activeConversation.messages.length === 0" class="text-center text-slate-400 py-8">
            Hãy gửi tin nhắn đầu tiên để bắt đầu cuộc trò chuyện.
          </div>
          <div
            v-for="msg in activeConversation.messages"
            :key="msg.id"
            class="flex items-end gap-2"
            :class="msg.fromMe ? 'justify-end' : 'justify-start'"
          >
            <img
              v-if="!msg.fromMe"
              :src="msg.senderAvatar || activeConversation.avatar || DEFAULT_AVATAR"
              class="w-7 h-7 rounded-full object-cover mb-1 flex-shrink-0 border border-slate-200 shadow-2xs"
            >
            <div
              class="max-w-[75%] p-3 rounded-2xl leading-relaxed space-y-2"
              :class="msg.fromMe ? 'bg-blue-600 text-white rounded-br-xs shadow-xs' : 'bg-white border border-slate-200 text-slate-800 rounded-bl-xs shadow-2xs'"
            >
              <!-- Group sender name -->
              <span v-if="!msg.fromMe && (activeConversation.type === 'group' || msg.senderName)" class="block text-[11px] font-bold text-blue-600 mb-0.5">
                {{ msg.senderName || activeConversation.title }}
              </span>

              <!-- Text body -->
              <p v-if="msg.text" class="whitespace-pre-wrap text-xs">{{ msg.text }}</p>

              <!-- Attachments in Message -->
              <div v-if="msg.attachments && msg.attachments.length > 0" class="space-y-2 pt-1">
                <div
                  v-for="att in msg.attachments"
                  :key="att.id"
                  class="rounded-xl overflow-hidden transition"
                >
                  <!-- Image Preview -->
                  <div
                    v-if="att.type === 'image'"
                    class="relative group/img rounded-lg overflow-hidden border"
                    :class="msg.fromMe ? 'border-blue-400/40 bg-black/10' : 'border-slate-200 bg-slate-50'"
                  >
                    <img
                      :src="att.url || DEFAULT_AVATAR"
                      :alt="att.name"
                      class="max-h-60 w-auto rounded-lg object-contain cursor-pointer transition hover:opacity-95"
                      @click="openImageModal(att)"
                    >
                    <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover/img:opacity-100 transition flex items-center justify-center gap-2">
                      <button
                        type="button"
                        class="p-1.5 rounded-full bg-white/90 hover:bg-white text-slate-800 border-none cursor-pointer flex items-center justify-center shadow-sm"
                        title="Xem ảnh"
                        @click="openImageModal(att)"
                      >
                        <span class="material-symbols-outlined text-base">zoom_in</span>
                      </button>
                      <button
                        type="button"
                        class="p-1.5 rounded-full bg-white/90 hover:bg-white text-slate-800 border-none cursor-pointer flex items-center justify-center shadow-sm"
                        title="Tải ảnh về"
                        @click="downloadAttachment(att)"
                      >
                        <span class="material-symbols-outlined text-base">download</span>
                      </button>
                    </div>
                  </div>

                  <!-- File / Document Card (PDF, Excel, Word, Archive) -->
                  <div
                    v-else
                    class="p-2.5 rounded-lg flex items-center justify-between gap-3 border transition"
                    :class="msg.fromMe ? 'bg-white/15 border-white/25 text-white hover:bg-white/20' : 'bg-slate-50 border-slate-200 text-slate-800 hover:bg-slate-100'"
                  >
                    <div class="flex items-center gap-2.5 min-w-0">
                      <div
                        class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                        :class="getFileBadgeClass(att.type, msg.fromMe)"
                      >
                        <span class="material-symbols-outlined text-lg">{{ getFileIcon(att.type) }}</span>
                      </div>
                      <div class="min-w-0">
                        <p class="font-bold text-xs truncate max-w-[190px] sm:max-w-[240px]" :title="att.name">{{ att.name }}</p>
                        <p class="text-[10px]" :class="msg.fromMe ? 'text-blue-100' : 'text-slate-400'">{{ att.size }}</p>
                      </div>
                    </div>
                    <button
                      type="button"
                      class="p-1.5 rounded-lg transition border-none cursor-pointer flex items-center justify-center flex-shrink-0"
                      :class="msg.fromMe ? 'bg-white/20 hover:bg-white text-white hover:text-blue-600' : 'bg-white text-slate-600 hover:text-blue-600 hover:bg-blue-50 border border-slate-200'"
                      title="Tải về để xem"
                      @click="downloadAttachment(att)"
                    >
                      <span class="material-symbols-outlined text-base">download</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Timestamp -->
              <span
                class="block text-[10px] mt-1 text-right"
                :class="msg.fromMe ? 'text-blue-100' : 'text-slate-400'"
              >
                {{ msg.time }}
              </span>
            </div>
          </div>
        </div>

        <!-- STAGED ATTACHMENT PREVIEW TRAY -->
        <div v-if="stagedAttachments.length > 0" class="px-3 py-2 bg-slate-50 border-t border-slate-100 flex items-center gap-2 overflow-x-auto">
          <div
            v-for="att in stagedAttachments"
            :key="att.id"
            class="flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-2.5 py-1 text-xs shadow-2xs flex-shrink-0"
          >
            <div class="w-6 h-6 rounded flex items-center justify-center" :class="getFileBadgeClass(att.type, false)">
              <span class="material-symbols-outlined text-sm">{{ getFileIcon(att.type) }}</span>
            </div>
            <div class="max-w-[140px] truncate">
              <span class="font-medium text-slate-800 text-[11px] truncate block">{{ att.name }}</span>
              <span class="text-[10px] text-slate-400">{{ att.sizeText }}</span>
            </div>
            <button
              type="button"
              class="text-slate-400 hover:text-rose-500 bg-transparent border-none cursor-pointer p-0.5 rounded"
              title="Xóa tệp"
              @click="removeStagedAttachment(att.id)"
            >
              <span class="material-symbols-outlined text-sm">close</span>
            </button>
          </div>
        </div>

        <!-- MESSAGE INPUT COMPOSER -->
        <div class="p-3 border-t border-slate-100 flex items-center gap-2 bg-white flex-shrink-0">
          <input
            ref="fileInputRef"
            type="file"
            multiple
            accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar"
            class="hidden"
            @change="handleFileSelected"
          >
          <button
            type="button"
            class="p-2 text-slate-500 hover:text-blue-600 hover:bg-slate-100 rounded-full flex items-center justify-center cursor-pointer border-none bg-transparent transition"
            title="Đính kèm tệp (Ảnh, PDF, tài liệu...)"
            @click="triggerFileInput"
          >
            <span class="material-symbols-outlined text-xl">attach_file</span>
          </button>
          <input
            v-model="messageInput"
            type="text"
            placeholder="Nhập tin nhắn..."
            class="flex-1 px-4 py-2 bg-slate-100 focus:bg-white text-xs rounded-full border border-transparent focus:border-blue-600 outline-none transition"
            @keyup.enter="sendMessage"
          >
          <button
            type="button"
            :disabled="!messageInput.trim() && stagedAttachments.length === 0"
            class="p-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:hover:bg-blue-600 text-white rounded-full flex items-center justify-center cursor-pointer border-none shadow-xs transition"
            @click="sendMessage"
          >
            <span class="material-symbols-outlined text-base">send</span>
          </button>
        </div>
      </section>

      <section v-else class="flex-1 bg-white rounded-lg border border-slate-200 flex flex-col items-center justify-center text-slate-400 text-xs">
        <span class="material-symbols-outlined text-4xl mb-2 text-slate-300">forum</span>
        <p>Chọn một cuộc trò chuyện để bắt đầu trao đổi</p>
      </section>

      <!-- PANEL 3: CONVERSATION / GROUP DETAILS & SHARED ATTACHMENTS -->
      <aside v-if="activeConversation" class="w-72 bg-white rounded-lg border border-slate-200 hidden lg:flex flex-col shadow-xs p-4 space-y-4 flex-shrink-0 overflow-y-auto text-xs">
        <div class="text-center pb-4 border-b border-slate-100">
          <div v-if="activeConversation.type === 'group'" class="w-16 h-16 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center mx-auto shadow-xs">
            <span class="material-symbols-outlined text-3xl">groups</span>
          </div>
          <img v-else :src="activeConversation.avatar" class="w-16 h-16 rounded-full object-cover mx-auto shadow-xs">

          <div v-if="editingTitle" class="mt-2 flex gap-1">
            <input v-model="editTitleValue" type="text" class="flex-1 rounded border border-slate-300 px-2 py-1 text-xs outline-none focus:border-blue-600">
            <button type="button" :disabled="busy" class="rounded bg-blue-600 px-2 py-1 text-white font-bold border-none cursor-pointer" @click="handleRenameGroup">Lưu</button>
          </div>
          <div v-else class="mt-2 flex items-center justify-center gap-1">
            <h3 class="font-bold text-slate-900 text-sm">{{ activeConversation.title }}</h3>
            <button
              v-if="isGroupOwner"
              type="button"
              class="text-slate-400 hover:text-blue-600 bg-transparent border-none cursor-pointer p-0.5"
              title="Đổi tên nhóm"
              @click="editTitleValue = activeConversation.title; editingTitle = true"
            >
              <span class="material-symbols-outlined text-sm">edit</span>
            </button>
          </div>
          <p class="text-slate-500 text-[11px]">{{ activeConversation.subtitle }}</p>
        </div>

        <!-- GROUP MEMBERS SECTION -->
        <div v-if="activeConversation.type === 'group' && activeConversation.membersLoaded" class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="font-bold text-slate-400 uppercase text-[10px] tracking-wider">THÀNH VIÊN ({{ activeConversation.membersLoaded.length }})</span>
            <button
              v-if="isGroupOwner"
              type="button"
              class="text-[11px] font-bold text-blue-600 hover:underline bg-transparent border-none cursor-pointer"
              @click="showAddMemberPicker = !showAddMemberPicker"
            >
              + Thêm
            </button>
          </div>

          <div v-if="showAddMemberPicker" class="rounded border border-blue-100 bg-blue-50/50 p-2 space-y-1 max-h-36 overflow-y-auto">
            <div v-if="availableToAdd.length === 0" class="text-slate-400 text-[11px]">Không còn nhân sự để thêm.</div>
            <button
              v-for="u in availableToAdd"
              :key="u.id"
              type="button"
              class="w-full text-left px-2 py-1 rounded hover:bg-blue-100 text-slate-700 bg-transparent border-none cursor-pointer flex items-center justify-between"
              @click="handleAddGroupMember(u.id)"
            >
              <span class="truncate">{{ u.display_name }}</span>
              <span class="text-blue-600 font-bold">+</span>
            </button>
          </div>

          <div class="space-y-1.5 max-h-52 overflow-y-auto">
            <div
              v-for="m in activeConversation.membersLoaded"
              :key="m.userId"
              class="flex items-center justify-between gap-2 py-1"
            >
              <div class="min-w-0">
                <p class="font-semibold text-slate-800 truncate">{{ resolveUserName(m.userId) }}</p>
                <p class="text-[10px] text-slate-400">{{ m.memberRole === 'owner' ? 'Trưởng nhóm' : 'Thành viên' }}</p>
              </div>
              <button
                v-if="isGroupOwner && m.userId !== props.user.id"
                type="button"
                class="text-[11px] text-rose-500 hover:text-rose-700 bg-transparent border-none cursor-pointer"
                title="Xóa khỏi nhóm"
                @click="handleRemoveOrLeaveMember(m.userId)"
              >
                Xóa
              </button>
            </div>
          </div>

          <button
            v-if="!isGroupOwner"
            type="button"
            :disabled="busy"
            class="w-full py-1.5 px-2 rounded bg-rose-50 hover:bg-rose-100 text-rose-600 font-semibold border-none cursor-pointer mt-2"
            @click="handleRemoveOrLeaveMember(props.user.id)"
          >
            Rời khỏi nhóm
          </button>
        </div>

        <!-- SHARED ATTACHMENTS SECTION -->
        <div class="space-y-2 pt-3 border-t border-slate-100">
          <div class="flex items-center justify-between">
            <span class="font-bold text-slate-400 uppercase text-[10px] tracking-wider">
              TỆP ĐÃ CHIA SẺ ({{ conversationAttachments.length }})
            </span>
          </div>

          <div v-if="conversationAttachments.length === 0" class="text-slate-400 text-[11px] py-2 text-center">
            Chưa có tệp đính kèm nào được chia sẻ.
          </div>

          <div v-else class="space-y-1.5 max-h-56 overflow-y-auto">
            <div
              v-for="item in conversationAttachments"
              :key="item.attachment.id"
              class="p-2 rounded-lg border border-slate-100 hover:bg-slate-50 flex items-center justify-between gap-2 transition"
            >
              <div class="flex items-center gap-2 min-w-0">
                <div class="w-7 h-7 rounded flex items-center justify-center flex-shrink-0" :class="getFileBadgeClass(item.attachment.type, false)">
                  <span class="material-symbols-outlined text-sm">{{ getFileIcon(item.attachment.type) }}</span>
                </div>
                <div class="min-w-0">
                  <p class="font-semibold text-slate-800 text-[11px] truncate" :title="item.attachment.name">{{ item.attachment.name }}</p>
                  <p class="text-[10px] text-slate-400">{{ item.attachment.size }} • {{ item.senderName }}</p>
                </div>
              </div>
              <button
                type="button"
                class="p-1 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded bg-transparent border-none cursor-pointer flex-shrink-0"
                title="Tải về"
                @click="downloadAttachment(item.attachment)"
              >
                <span class="material-symbols-outlined text-base">download</span>
              </button>
            </div>
          </div>
        </div>
      </aside>
    </main>

    <!-- MODAL: IMAGE PREVIEW LIGHTBOX -->
    <div
      v-if="previewImageModal"
      class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4"
      @click="previewImageModal = null"
    >
      <div class="relative max-w-4xl max-h-[90vh] flex flex-col items-center" @click.stop>
        <img
          :src="previewImageModal.url"
          :alt="previewImageModal.name"
          class="max-w-full max-h-[80vh] rounded-lg object-contain shadow-2xl"
        >
        <div class="mt-3 flex items-center gap-3 bg-slate-900/90 px-4 py-2 rounded-full text-white text-xs">
          <span class="truncate max-w-xs font-semibold">{{ previewImageModal.name }}</span>
          <button
            type="button"
            class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-md border-none cursor-pointer flex items-center gap-1 font-semibold"
            @click="downloadAttachment({ id: 'preview', name: previewImageModal.name, size: '', type: 'image', url: previewImageModal.url })"
          >
            <span class="material-symbols-outlined text-sm">download</span>
            Tải về
          </button>
          <button
            type="button"
            class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-md border-none cursor-pointer font-semibold"
            @click="previewImageModal = null"
          >
            Đóng
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: NEW DIRECT CHAT -->
    <div v-if="showNewDirectModal" class="fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4">
      <div class="bg-white rounded-xl border border-slate-200 w-full max-w-md shadow-xl overflow-hidden text-xs">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-sm font-bold text-slate-900">Nhắn tin mới</h3>
          <button type="button" class="text-slate-400 hover:text-slate-700 bg-transparent border-none cursor-pointer" @click="showNewDirectModal = false">
            <span class="material-symbols-outlined text-lg">close</span>
          </button>
        </div>
        <div class="p-4 space-y-3">
          <input
            v-model="modalSearch"
            type="text"
            placeholder="Tìm đồng nghiệp..."
            class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-blue-600"
          >
          <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
            <div v-if="filteredDirectory.length === 0" class="py-6 text-center text-slate-400">
              Không tìm thấy đồng nghiệp phù hợp.
            </div>
            <button
              v-for="u in filteredDirectory"
              :key="u.id"
              type="button"
              class="w-full py-2.5 px-2 flex items-center justify-between hover:bg-slate-50 text-left bg-transparent border-none cursor-pointer"
              @click="startDirectChat(u)"
            >
              <div>
                <p class="font-bold text-slate-800">{{ u.display_name }}</p>
                <p class="text-[11px] text-slate-400">{{ u.job_title ?? 'Thành viên' }}</p>
              </div>
              <span class="text-blue-600 font-semibold">Nhắn tin</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: CREATE GROUP CHAT -->
    <div v-if="showCreateGroupModal && canCreateGroup" class="fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4">
      <form class="bg-white rounded-xl border border-slate-200 w-full max-w-md shadow-xl overflow-hidden text-xs" @submit.prevent="handleCreateGroup">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-sm font-bold text-slate-900">Tạo nhóm chat mới</h3>
          <button type="button" class="text-slate-400 hover:text-slate-700 bg-transparent border-none cursor-pointer" @click="showCreateGroupModal = false">
            <span class="material-symbols-outlined text-lg">close</span>
          </button>
        </div>
        <div class="p-4 space-y-3">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Tên nhóm chat</label>
            <input
              v-model="newGroupTitle"
              type="text"
              required
              placeholder="VD: Nhóm dự án Q4..."
              class="w-full rounded-lg border border-slate-200 px-3 py-2 outline-none focus:border-blue-600"
            >
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Chọn thành viên ({{ selectedMemberIds.length }} đã chọn)</label>
            <input
              v-model="modalSearch"
              type="text"
              placeholder="Tìm đồng nghiệp..."
              class="w-full rounded-lg border border-slate-200 px-3 py-2 mb-2 outline-none focus:border-blue-600"
            >
            <div class="max-h-52 overflow-y-auto border border-slate-100 rounded-lg divide-y divide-slate-100">
              <div v-if="filteredDirectory.length === 0" class="py-4 text-center text-slate-400">
                Không có đồng nghiệp nào.
              </div>
              <label
                v-for="u in filteredDirectory"
                :key="u.id"
                class="flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :checked="selectedMemberIds.includes(u.id)"
                  @change="toggleMemberSelection(u.id)"
                >
                <div class="min-w-0 flex-1">
                  <p class="font-semibold text-slate-800 truncate">{{ u.display_name }}</p>
                  <p class="text-[10px] text-slate-400 truncate">{{ u.job_title ?? 'Thành viên' }}</p>
                </div>
              </label>
            </div>
          </div>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 flex justify-end gap-2 bg-slate-50">
          <button type="button" class="px-3 py-1.5 rounded-md bg-slate-200 text-slate-700 font-semibold border-none cursor-pointer" @click="showCreateGroupModal = false">Hủy</button>
          <button
            type="submit"
            :disabled="busy || !newGroupTitle.trim() || selectedMemberIds.length === 0"
            class="px-4 py-1.5 rounded-md bg-blue-600 text-white font-bold border-none cursor-pointer disabled:opacity-50"
          >
            {{ busy ? 'Đang tạo...' : 'Tạo nhóm' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
