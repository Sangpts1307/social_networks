<script setup lang="ts">
import { ref, computed } from 'vue'
import { store } from '../../mock/store'

const searchQuery = ref('')
const messageText = ref('')

const activeConv = computed(() => {
  return store.conversations.find(c => c.id === store.activeConversationId) || store.conversations[0]
})

const filteredConversations = computed(() => {
  if (!searchQuery.value.trim()) return store.conversations
  const q = searchQuery.value.toLowerCase()
  return store.conversations.filter(c => 
    c.user.name.toLowerCase().includes(q) || 
    c.user.department.toLowerCase().includes(q)
  )
})

function selectConv(id: string) {
  store.activeConversationId = id
  const conv = store.conversations.find(c => c.id === id)
  if (conv) conv.unreadCount = 0
}

function handleSendMessage() {
  if (!messageText.value.trim() || !activeConv.value) return
  store.sendMessage(activeConv.value.id, messageText.value)
  messageText.value = ''
}
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 py-6">
    <div class="bg-white border border-slate-200 rounded-md overflow-hidden grid grid-cols-1 md:grid-cols-12 min-h-[640px]">
      
      <!-- LEFT COLUMN: CONVERSATION LIST (4 cols) -->
      <aside class="md:col-span-4 border-r border-slate-200 flex flex-col bg-slate-50/50">
        <!-- Search Colleagues -->
        <div class="p-3 border-b border-slate-200 bg-white">
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Tìm đồng nghiệp hoặc phòng ban..."
              class="w-full pl-8 pr-3 py-1.5 bg-slate-50 text-xs rounded border border-slate-200 outline-none"
            >
            <span class="material-symbols-outlined absolute left-2.5 top-2 text-slate-400 text-sm">search</span>
          </div>
        </div>

        <!-- Conversation Item List -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
          <div
            v-for="conv in filteredConversations"
            :key="conv.id"
            class="p-3 flex items-start gap-2.5 cursor-pointer transition"
            :class="store.activeConversationId === conv.id ? 'bg-white font-semibold' : 'hover:bg-slate-100/60'"
            @click="selectConv(conv.id)"
          >
            <!-- Avatar with Online indicator -->
            <div class="relative flex-shrink-0">
              <img :src="conv.user.avatar" :alt="conv.user.name" class="w-9 h-9 rounded-md object-cover border border-slate-200">
              <span
                v-if="conv.user.online"
                class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white"
              ></span>
            </div>

            <!-- Details -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-900 truncate">{{ conv.user.name }}</span>
                <span class="text-[10px] text-slate-400 flex-shrink-0">{{ conv.lastTime }}</span>
              </div>
              <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ conv.lastMessage }}</p>
              <div class="flex items-center justify-between mt-1">
                <span class="text-[10px] text-slate-400">{{ conv.user.department }}</span>
                <span
                  v-if="conv.unreadCount > 0"
                  class="px-1.5 py-0.2 rounded-full bg-blue-600 text-white text-[9px] font-bold"
                >
                  {{ conv.unreadCount }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </aside>

      <!-- RIGHT COLUMN: CHAT PANEL (8 cols) -->
      <main class="md:col-span-8 flex flex-col bg-white">
        <!-- Active User Header -->
        <div v-if="activeConv" class="p-3.5 border-b border-slate-200 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <img :src="activeConv.user.avatar" :alt="activeConv.user.name" class="w-8 h-8 rounded-md object-cover border border-slate-200">
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-xs font-bold text-slate-900 leading-tight">{{ activeConv.user.name }}</h2>
                <span 
                  class="w-1.5 h-1.5 rounded-full" 
                  :class="activeConv.user.online ? 'bg-emerald-500' : 'bg-slate-300'"
                ></span>
              </div>
              <p class="text-[10px] text-slate-500">{{ activeConv.user.role }} • {{ activeConv.user.department }}</p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button 
              type="button" 
              class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium cursor-pointer border-none"
              @click="store.setScreen('profile')"
            >
              Hồ sơ
            </button>
          </div>
        </div>

        <!-- Messages Flow -->
        <div v-if="activeConv" class="flex-1 p-4 overflow-y-auto space-y-3 bg-slate-50/30">
          <div
            v-for="msg in activeConv.messages"
            :key="msg.id"
            class="flex flex-col"
            :class="msg.isMe ? 'items-end' : 'items-start'"
          >
            <div
              class="max-w-[80%] rounded px-3 py-2 text-xs leading-relaxed"
              :class="msg.isMe 
                ? 'bg-slate-900 text-white rounded-br-none' 
                : 'bg-white border border-slate-200 text-slate-900 rounded-bl-none'"
            >
              {{ msg.text }}
            </div>
            <span class="text-[9px] text-slate-400 mt-0.5 px-1">{{ msg.time }}</span>
          </div>
        </div>

        <!-- Message Composer -->
        <div v-if="activeConv" class="p-3 border-t border-slate-200 bg-white flex items-center gap-2">
          <input
            v-model="messageText"
            type="text"
            placeholder="Nhập nội dung trao đổi..."
            class="flex-1 px-3 py-2 bg-slate-50 focus:bg-white text-xs text-slate-900 rounded border border-slate-200 outline-none"
            @keyup.enter="handleSendMessage"
          >
          <button
            type="button"
            class="px-4 py-2 rounded bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold cursor-pointer border-none transition"
            @click="handleSendMessage"
          >
            Gửi
          </button>
        </div>
      </main>

    </div>
  </div>
</template>
