<script setup lang="ts">
import { ref } from 'vue'
import type { AuthUser } from '../../api/auth'

const props = defineProps<{
  user: AuthUser
  currentApp?: string
  appTitle?: string
}>()

const emit = defineEmits<{
  (e: 'back'): void
  (e: 'select-app', appId: string): void
  (e: 'logout'): void
}>()

const showSwitcher = ref(false)

const appList = [
  { id: 'square', name: 'Base Square (Hỏi đáp & Tri thức)', icon: 'school' },
  { id: 'inside', name: 'Base Inside (Mạng xã hội)', icon: 'hub' },
  { id: 'message', name: 'Base Message (Tin nhắn)', icon: 'chat' },
  { id: 'library', name: 'Base Library (Thư viện)', icon: 'local_library' },
  { id: 'knowledge', name: 'Knowledge Graph (Cây tri thức)', icon: 'account_tree' },
  { id: 'search', name: 'AI Hybrid Search (Tìm kiếm AI)', icon: 'troubleshoot' },
  { id: 'reward', name: 'Base Reward (Vinh danh & Xu)', icon: 'emoji_events' },
  { id: 'profile', name: 'Hồ sơ cá nhân', icon: 'manage_accounts' },
  { id: 'admin', name: 'Quản trị hệ thống', icon: 'admin_panel_settings' }
]

function switchApp(id: string) {
  showSwitcher.value = false
  emit('select-app', id)
}
</script>

<template>
  <header class="sticky top-0 z-40 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between gap-4">
      
      <!-- Left: Logo & Breadcrumb Navigation -->
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="flex items-center gap-2 p-1 rounded hover:bg-slate-100 cursor-pointer border-none bg-transparent"
          title="Về cổng ứng dụng"
          @click="emit('back')"
        >
          <div class="w-8 h-8 rounded-md bg-slate-900 text-white flex items-center justify-center font-bold text-sm">
            <span class="material-symbols-outlined text-base">grid_view</span>
          </div>
          <span class="text-sm font-extrabold text-slate-900 tracking-tight hidden sm:inline">OpenHello</span>
        </button>

        <!-- Current App Pill & Switcher -->
        <div v-if="currentApp" class="relative flex items-center gap-1.5 pl-2 border-l border-slate-200 text-xs">
          <span class="text-slate-400 font-medium">/</span>
          <button
            type="button"
            class="flex items-center gap-1 px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 font-bold text-slate-800 cursor-pointer border-none"
            @click="showSwitcher = !showSwitcher"
          >
            <span>{{ appTitle || currentApp }}</span>
            <span class="material-symbols-outlined text-sm text-slate-500">arrow_drop_down</span>
          </button>

          <!-- App Switcher Dropdown -->
          <div
            v-if="showSwitcher"
            class="absolute left-2 top-8 mt-1 w-64 bg-white border border-slate-200 rounded-md shadow-xs p-1.5 z-50 space-y-0.5"
          >
            <div class="px-2 py-1 text-[10px] font-bold text-slate-400 uppercase">Chuyển nhanh ứng dụng</div>
            <button
              v-for="app in appList"
              :key="app.id"
              type="button"
              class="w-full text-left px-2.5 py-1.5 rounded flex items-center gap-2 text-xs font-semibold cursor-pointer border-none"
              :class="currentApp === app.id ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50'"
              @click="switchApp(app.id)"
            >
              <span class="material-symbols-outlined text-base">{{ app.icon }}</span>
              <span>{{ app.name }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Right: User status & Logout -->
      <div class="flex items-center gap-3">
        <button
          v-if="currentApp"
          type="button"
          class="hidden md:flex items-center gap-1 px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer border-none"
          @click="emit('back')"
        >
          <span class="material-symbols-outlined text-sm">home</span>
          <span>Về cổng Portal</span>
        </button>

        <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          <span>Đã đăng nhập</span>
        </div>

        <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
          <img
            src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80"
            alt="Avatar"
            class="w-7 h-7 rounded-md object-cover border border-slate-200"
          >
          <div class="text-left hidden lg:block leading-tight text-xs">
            <span class="font-bold text-slate-900 block">{{ user.display_name }}</span>
            <span class="text-[10px] text-slate-500 block">{{ user.email }}</span>
          </div>
        </div>

        <button
          type="button"
          class="p-1.5 rounded hover:bg-rose-50 text-rose-600 cursor-pointer border-none bg-transparent"
          title="Đăng xuất"
          @click="emit('logout')"
        >
          <span class="material-symbols-outlined text-lg">logout</span>
        </button>
      </div>

    </div>
  </header>
</template>
