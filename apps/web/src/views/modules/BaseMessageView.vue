<script setup lang="ts">
import { ref } from 'vue'
import type { AuthUser } from '../../api/auth'
import { MOCK_CONVERSATIONS } from '../../mock/mockData.js'

const props = defineProps<{
  user: AuthUser
}>()

const emit = defineEmits<{
  (e: 'back'): void
  (e: 'select-app', appId: string): void
  (e: 'logout'): void
}>()

const conversations = ref([...MOCK_CONVERSATIONS])
const activeConvId = ref(conversations.value[0]?.id || '')
const messageInput = ref('')
const activeTab = ref('all')

const activeConversation = ref(conversations.value[0])

function selectConversation(conv: any) {
  activeConvId.value = conv.id
  activeConversation.value = conv
  conv.unread = 0
}

function sendMessage() {
  if (!messageInput.value.trim() || !activeConversation.value) return
  activeConversation.value.messages.push({
    id: `m-${Date.now()}`,
    fromMe: true,
    text: messageInput.value.trim(),
    time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
  })
  activeConversation.value.lastMessage = messageInput.value.trim()
  activeConversation.value.lastTime = 'Vừa xong'
  messageInput.value = ''
}
</script>

<template>
  <div class="h-screen text-slate-900 antialiased flex flex-col justify-between overflow-hidden bg-[#F8FAFC] font-sans">
    
    <!-- TOP APP NAVBAR (05-messenger.html) -->
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200 flex-shrink-0">
      <div class="max-w-[1440px] mx-auto px-4 lg:px-8 h-14 flex items-center justify-between gap-4">
        
        <!-- Brand Logo -->
        <button
          type="button"
          class="flex items-center gap-2.5 cursor-pointer border-none bg-transparent"
          title="Về cổng Portal"
          @click="emit('back')"
        >
          <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
            <span class="material-symbols-outlined text-base">change_history</span>
          </div>
          <span class="text-base font-extrabold tracking-wider text-slate-900">BASE.VN</span>
        </button>

        <!-- Center-Right Tools -->
        <div class="flex items-center gap-3">
          <div class="relative w-64 sm:w-80 hidden sm:block">
            <input
              type="text"
              placeholder="Tìm kiếm tin nhắn, người, nhóm..."
              class="w-full pl-4 pr-9 py-1.5 bg-slate-50 hover:bg-slate-100 focus:bg-white text-xs rounded-md border border-slate-200 focus:border-blue-600 outline-none transition"
            >
            <span class="material-symbols-outlined absolute right-2.5 top-2 text-slate-400 text-base">search</span>
          </div>

          <button
            type="button"
            class="flex items-center gap-2 p-1 rounded-md hover:bg-slate-100 cursor-pointer border-none bg-transparent"
            title="Xem trang cá nhân"
            @click="emit('select-app', 'profile')"
          >
            <img
              src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80"
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

    <!-- 3-PANEL MESSENGER CONTAINER -->
    <main class="max-w-[1440px] mx-auto w-full px-4 lg:px-8 py-3 flex-1 flex gap-4 h-[calc(100vh-61px)] overflow-hidden">
      
      <!-- PANEL 1: CONVERSATIONS LIST -->
      <section class="w-80 bg-white rounded-lg border border-slate-200 flex flex-col shadow-xs flex-shrink-0 overflow-hidden">
        <!-- Search & Filters -->
        <div class="p-3.5 border-b border-slate-100 space-y-2.5">
          <div class="flex items-center justify-between">
            <h1 class="text-sm font-bold text-slate-900">Hộp thoại Message</h1>
            <button
              type="button"
              class="p-1 text-slate-600 hover:text-blue-600 hover:bg-slate-100 rounded border-none bg-transparent cursor-pointer"
              title="Tạo trò chuyện mới"
            >
              <span class="material-symbols-outlined text-base">post_add</span>
            </button>
          </div>
          <div class="flex bg-slate-100 p-0.5 rounded-md text-xs font-semibold text-slate-600">
            <button
              type="button"
              class="flex-1 py-1 rounded transition border-none cursor-pointer"
              :class="activeTab === 'all' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'hover:text-slate-900 bg-transparent'"
              @click="activeTab = 'all'"
            >
              Tất cả
            </button>
            <button
              type="button"
              class="flex-1 py-1 rounded transition border-none cursor-pointer"
              :class="activeTab === 'direct' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'hover:text-slate-900 bg-transparent'"
              @click="activeTab = 'direct'"
            >
              Trực tiếp
            </button>
            <button
              type="button"
              class="flex-1 py-1 rounded transition border-none cursor-pointer"
              :class="activeTab === 'group' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'hover:text-slate-900 bg-transparent'"
              @click="activeTab = 'group'"
            >
              Nhóm dự án
            </button>
          </div>
        </div>

        <!-- Scrollable Conversation items -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-50 text-xs">
          <div
            v-for="conv in conversations"
            :key="conv.id"
            class="p-3 flex items-start gap-3 cursor-pointer transition"
            :class="conv.id === activeConvId ? 'bg-blue-50/70 border-l-4 border-blue-600' : 'hover:bg-slate-50'"
            @click="selectConversation(conv)"
          >
            <div class="relative flex-shrink-0">
              <img :src="conv.user.avatar" class="w-10 h-10 rounded-full object-cover">
              <span
                v-if="conv.user.online"
                class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white"
              ></span>
            </div>
            
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-900 truncate">{{ conv.user.name }}</span>
                <span class="text-[10px] text-slate-400">{{ conv.lastTime }}</span>
              </div>
              <p class="text-slate-500 truncate mt-0.5">{{ conv.lastMessage }}</p>
            </div>

            <div v-if="conv.unread > 0" class="flex-shrink-0">
              <span class="w-4 h-4 rounded-full bg-blue-600 text-white text-[9px] font-bold flex items-center justify-center">
                {{ conv.unread }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- PANEL 2: ACTIVE CHAT THREAD -->
      <section v-if="activeConversation" class="flex-1 bg-white rounded-lg border border-slate-200 flex flex-col shadow-xs overflow-hidden">
        
        <!-- Chat Header -->
        <div class="p-3.5 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
          <div class="flex items-center gap-3">
            <img :src="activeConversation.user.avatar" class="w-9 h-9 rounded-full object-cover">
            <div>
              <h2 class="text-sm font-bold text-slate-900 leading-tight">{{ activeConversation.user.name }}</h2>
              <span class="text-[11px] text-slate-400 block">{{ activeConversation.user.role }} • {{ activeConversation.user.online ? 'Đang hoạt động' : 'Offline' }}</span>
            </div>
          </div>
          <div class="flex items-center gap-1 text-slate-500">
            <button type="button" class="p-1.5 hover:bg-slate-100 rounded border-none bg-transparent cursor-pointer">
              <span class="material-symbols-outlined text-base">call</span>
            </button>
            <button type="button" class="p-1.5 hover:bg-slate-100 rounded border-none bg-transparent cursor-pointer">
              <span class="material-symbols-outlined text-base">videocam</span>
            </button>
          </div>
        </div>

        <!-- Chat Messages History -->
        <div class="flex-1 p-4 overflow-y-auto space-y-3 text-xs bg-slate-50/40">
          <div
            v-for="msg in activeConversation.messages"
            :key="msg.id"
            class="flex items-end gap-2"
            :class="msg.fromMe ? 'justify-end' : 'justify-start'"
          >
            <img
              v-if="!msg.fromMe"
              :src="activeConversation.user.avatar"
              class="w-6 h-6 rounded-full object-cover mb-1"
            >
            <div
              class="max-w-[70%] p-3 rounded-2xl leading-relaxed"
              :class="msg.fromMe ? 'bg-blue-600 text-white rounded-br-xs shadow-xs' : 'bg-white border border-slate-200 text-slate-800 rounded-bl-xs shadow-2xs'"
            >
              <p>{{ msg.text }}</p>
              <span
                class="block text-[10px] mt-1 text-right"
                :class="msg.fromMe ? 'text-blue-100' : 'text-slate-400'"
              >
                {{ msg.time }}
              </span>
            </div>
          </div>
        </div>

        <!-- Message Composer Input Bar -->
        <div class="p-3 border-t border-slate-100 flex items-center gap-2 bg-white flex-shrink-0">
          <button type="button" class="p-1.5 hover:bg-slate-100 text-slate-500 rounded border-none bg-transparent cursor-pointer">
            <span class="material-symbols-outlined text-lg">attach_file</span>
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
            class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center cursor-pointer border-none shadow-xs"
            @click="sendMessage"
          >
            <span class="material-symbols-outlined text-base">send</span>
          </button>
        </div>

      </section>

      <!-- PANEL 3: USER DETAILS -->
      <aside v-if="activeConversation" class="w-72 bg-white rounded-lg border border-slate-200 hidden lg:flex flex-col shadow-xs p-4 space-y-4 flex-shrink-0 overflow-y-auto text-xs">
        <div class="text-center pb-4 border-b border-slate-100">
          <img :src="activeConversation.user.avatar" class="w-16 h-16 rounded-full object-cover mx-auto shadow-xs">
          <h3 class="font-bold text-slate-900 mt-2 text-sm">{{ activeConversation.user.name }}</h3>
          <p class="text-slate-500 text-[11px]">{{ activeConversation.user.role }}</p>
        </div>

        <div class="space-y-2">
          <span class="font-bold text-slate-400 uppercase text-[10px] tracking-wider block">TÀI LIỆU CHIA SẺ</span>
          <div class="p-2 rounded bg-slate-50 border border-slate-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-rose-500 text-base">picture_as_pdf</span>
            <div class="truncate">
              <span class="font-semibold block truncate">Knowledge_Graph_v1.pdf</span>
              <span class="text-[10px] text-slate-400">3.4 MB</span>
            </div>
          </div>
        </div>

        <div class="space-y-1 pt-2 border-t border-slate-100">
          <button type="button" class="w-full text-left py-1.5 px-2 hover:bg-slate-50 text-slate-700 rounded flex items-center justify-between border-none bg-transparent cursor-pointer">
            <span>Tìm kiếm trong trò chuyện</span>
            <span class="material-symbols-outlined text-sm text-slate-400">search</span>
          </button>
          <button type="button" class="w-full text-left py-1.5 px-2 hover:bg-slate-50 text-rose-600 rounded flex items-center justify-between border-none bg-transparent cursor-pointer">
            <span>Tắt thông báo</span>
            <span class="material-symbols-outlined text-sm text-rose-400">notifications_off</span>
          </button>
        </div>
      </aside>

    </main>

  </div>
</template>
