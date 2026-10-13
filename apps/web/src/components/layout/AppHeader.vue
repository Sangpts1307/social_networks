<script setup lang="ts">
import { ref, computed } from 'vue'
import { store, type AppScreen } from '../../mock/store'

const emit = defineEmits<{
  (e: 'logout'): void
}>()

const showNotifications = ref(false)
const showUserMenu = ref(false)

const navItems: { key: AppScreen; label: string; icon: string; adminOnly?: boolean }[] = [
  { key: 'portal', label: 'Cổng', icon: 'grid_view' },
  { key: 'social', label: 'Bảng tin', icon: 'feed' },
  { key: 'messenger', label: 'Tin nhắn', icon: 'chat' },
  { key: 'qa', label: 'Hỏi đáp', icon: 'school' },
  { key: 'library', label: 'Tài liệu', icon: 'menu_book' },
  { key: 'graph', label: 'Cây tri thức', icon: 'account_tree' },
  { key: 'search', label: 'Tìm kiếm AI', icon: 'search' },
  { key: 'leaderboard', label: 'Điểm thưởng', icon: 'military_tech' },
  { key: 'admin', label: 'Quản trị', icon: 'admin_panel_settings', adminOnly: true }
]

const unreadCount = computed(() => store.notifications.filter(n => !n.isRead).length)
const unreadMessagesCount = computed(() => store.conversations.reduce((acc, c) => acc + c.unreadCount, 0))

function handleSelectScreen(screen: AppScreen) {
  store.setScreen(screen)
  showNotifications.value = false
  showUserMenu.value = false
}

function openNotification(target?: AppScreen) {
  if (target) store.setScreen(target)
  showNotifications.value = false
  store.markNotificationsRead()
}
</script>

<template>
  <header class="sticky top-0 z-50 bg-white border-b border-slate-200">
    <div class="max-w-[1440px] mx-auto px-4 h-14 flex items-center justify-between gap-4">
      
      <!-- Brand & Module Quick Nav -->
      <div class="flex items-center gap-6">
        <button 
          type="button" 
          class="flex items-center gap-2 cursor-pointer border-none bg-transparent p-0 text-left"
          @click="handleSelectScreen('portal')"
        >
          <div class="w-8 h-8 rounded-md bg-slate-900 text-white flex items-center justify-center font-bold text-sm">
            <span class="material-symbols-outlined text-lg">hub</span>
          </div>
          <div>
            <span class="text-sm font-bold text-slate-900 tracking-tight block leading-none">NexaHub</span>
            <span class="text-[10px] text-slate-500 font-medium leading-tight">Enterprise</span>
          </div>
        </button>

        <!-- Navigation Tabs -->
        <nav class="hidden md:flex items-center gap-1">
          <button
            v-for="item in navItems.filter(i => !i.adminOnly || store.currentUser.role === 'admin')"
            :key="item.key"
            type="button"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition cursor-pointer border-none"
            :class="store.currentScreen === item.key 
              ? 'bg-slate-100 text-slate-900 font-bold' 
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
            @click="handleSelectScreen(item.key)"
          >
            <span class="material-symbols-outlined text-base">{{ item.icon }}</span>
            <span>{{ item.label }}</span>
            <span 
              v-if="item.key === 'messenger' && unreadMessagesCount > 0" 
              class="w-4 h-4 rounded-full bg-blue-600 text-white text-[10px] flex items-center justify-center font-bold"
            >
              {{ unreadMessagesCount }}
            </span>
          </button>
        </nav>
      </div>

      <!-- Center / Right Actions -->
      <div class="flex items-center gap-2.5">
        <!-- Logged in status badge -->
        <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          <span>Đã đăng nhập</span>
        </div>

        <!-- Quick Search button -->
        <button 
          type="button"
          class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium transition cursor-pointer border-none"
          @click="handleSelectScreen('search')"
        >
          <span class="material-symbols-outlined text-sm">search</span>
          <span>Tìm kiếm tài liệu, hỏi đáp, nhân sự...</span>
          <kbd class="ml-2 px-1.5 py-0.5 rounded bg-white border border-slate-200 text-[10px] text-slate-500">Ctrl K</kbd>
        </button>

        <!-- Notifications Bell -->
        <div class="relative">
          <button
            type="button"
            class="p-2 rounded-md hover:bg-slate-100 text-slate-600 relative transition cursor-pointer border-none bg-transparent"
            title="Thông báo"
            @click="showNotifications = !showNotifications; showUserMenu = false"
          >
            <span class="material-symbols-outlined text-xl">notifications</span>
            <span 
              v-if="unreadCount > 0" 
              class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-rose-600 text-white text-[10px] font-bold flex items-center justify-center leading-none"
            >
              {{ unreadCount }}
            </span>
          </button>

          <!-- Notifications Dropdown -->
          <div 
            v-if="showNotifications"
            class="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-md shadow-xs p-2 z-50 text-xs"
          >
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 px-2">
              <span class="font-bold text-slate-900">Thông báo gần đây</span>
              <button 
                type="button" 
                class="text-blue-600 hover:underline text-[11px] font-medium border-none bg-transparent cursor-pointer p-0"
                @click="store.markNotificationsRead()"
              >
                Đánh dấu đã đọc
              </button>
            </div>
            <div class="space-y-1 mt-1 max-h-72 overflow-y-auto">
              <div 
                v-for="notif in store.notifications" 
                :key="notif.id"
                class="p-2 rounded hover:bg-slate-50 cursor-pointer transition flex items-start gap-2.5"
                :class="!notif.isRead ? 'bg-blue-50/50' : ''"
                @click="openNotification(notif.screenTarget)"
              >
                <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0" :class="notif.isRead ? 'bg-slate-300' : 'bg-blue-600'"></span>
                <div class="flex-1">
                  <p class="text-slate-800 leading-snug font-medium">{{ notif.title }}</p>
                  <span class="text-[10px] text-slate-400 mt-0.5 block">{{ notif.time }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- User Profile Dropdown -->
        <div class="relative">
          <button 
            type="button"
            class="flex items-center gap-2 p-1 rounded-md hover:bg-slate-100 transition cursor-pointer border-none bg-transparent"
            @click="showUserMenu = !showUserMenu; showNotifications = false"
          >
            <img 
              :src="store.currentUser.avatar" 
              :alt="store.currentUser.name" 
              class="w-7 h-7 rounded-md object-cover border border-slate-200"
            >
            <div class="text-left hidden lg:block leading-tight">
              <span class="text-xs font-semibold text-slate-800 block">{{ store.currentUser.name }}</span>
              <span class="text-[10px] text-slate-500 block">{{ store.currentUser.email }}</span>
            </div>
            <span class="material-symbols-outlined text-slate-400 text-sm">expand_more</span>
          </button>

          <!-- User Dropdown Menu -->
          <div 
            v-if="showUserMenu"
            class="absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-md shadow-xs p-1.5 z-50 text-xs"
          >
            <div class="px-2 py-1.5 border-b border-slate-100">
              <p class="font-bold text-slate-900">{{ store.currentUser.name }}</p>
              <p class="text-[11px] text-slate-500">{{ store.currentUser.email }}</p>
              <div class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-medium">
                <span>Xu: {{ store.currentUser.coins }}</span>
                <span>•</span>
                <span>Điểm: {{ store.currentUser.points }}</span>
              </div>
            </div>

            <div class="py-1">
              <button 
                type="button" 
                class="w-full text-left px-2 py-1.5 rounded hover:bg-slate-50 flex items-center gap-2 text-slate-700 cursor-pointer border-none bg-transparent"
                @click="handleSelectScreen('profile')"
              >
                <span class="material-symbols-outlined text-sm">person</span>
                <span>Hồ sơ cá nhân</span>
              </button>
              <button 
                type="button" 
                class="w-full text-left px-2 py-1.5 rounded hover:bg-slate-50 flex items-center gap-2 text-slate-700 cursor-pointer border-none bg-transparent"
                @click="handleSelectScreen('leaderboard')"
              >
                <span class="material-symbols-outlined text-sm">military_tech</span>
                <span>Bảng vinh danh</span>
              </button>
              <button 
                type="button" 
                class="w-full text-left px-2 py-1.5 rounded hover:bg-slate-50 flex items-center gap-2 text-slate-700 cursor-pointer border-none bg-transparent"
                @click="store.toggleRole(); showUserMenu = false"
              >
                <span class="material-symbols-outlined text-sm">swap_horiz</span>
                <span>Đổi vai trò: {{ store.currentUser.role === 'admin' ? 'Nhân viên' : 'Admin' }}</span>
              </button>
            </div>

            <div class="pt-1 border-t border-slate-100">
              <button 
                type="button" 
                class="w-full text-left px-2 py-1.5 rounded hover:bg-rose-50 text-rose-600 flex items-center gap-2 cursor-pointer border-none bg-transparent font-medium"
                @click="emit('logout'); showUserMenu = false"
              >
                <span class="material-symbols-outlined text-sm">logout</span>
                <span>Đăng xuất</span>
              </button>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- Mobile Sub-navigation Bar -->
    <div class="md:hidden flex items-center overflow-x-auto px-4 py-1.5 border-t border-slate-100 gap-1 bg-slate-50">
      <button
        v-for="item in navItems.filter(i => !i.adminOnly || store.currentUser.role === 'admin')"
        :key="item.key"
        type="button"
        class="flex-shrink-0 px-2.5 py-1 rounded text-xs font-semibold cursor-pointer border-none"
        :class="store.currentScreen === item.key ? 'bg-slate-900 text-white' : 'text-slate-600 bg-white border border-slate-200'"
        @click="handleSelectScreen(item.key)"
      >
        {{ item.label }}
      </button>
    </div>
  </header>
</template>
