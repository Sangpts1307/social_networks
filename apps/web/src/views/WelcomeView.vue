<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import type { AuthUser } from '../api/auth'
import { MOCK_APPS, MOCK_ANNOUNCEMENTS } from '../mock/mockData.js'

const props = defineProps<{
  user: AuthUser
}>()

const emit = defineEmits<{
  (e: 'logout'): void
  (e: 'select-app', appId: string): void
}>()

const activeCategory = ref('ALL')
const searchQuery = ref('')
const currentTime = ref('19:13:00')
const currentDate = ref('Hà Nội, Tối Thứ 5, 24/08/2026')

let timer: number | undefined

function updateClock() {
  const now = new Date()
  const h = String(now.getHours()).padStart(2, '0')
  const m = String(now.getMinutes()).padStart(2, '0')
  const s = String(now.getSeconds()).padStart(2, '0')
  currentTime.value = `${h}:${m}:${s}`
}

onMounted(() => {
  updateClock()
  timer = window.setInterval(updateClock, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const filteredApps = computed(() => {
  return MOCK_APPS.filter((app: any) => {
    const matchCat =
      activeCategory.value === 'ALL' ||
      (activeCategory.value === 'ADMIN' && app.category === 'ADMIN') ||
      app.category === activeCategory.value
    const matchQuery =
      !searchQuery.value ||
      app.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      app.desc.toLowerCase().includes(searchQuery.value.toLowerCase())
    return matchCat && matchQuery
  })
})
</script>

<template>
  <div class="welcome-container min-h-screen flex flex-col justify-between p-4 sm:p-8 antialiased selection:bg-teal-500 selection:text-white text-white font-sans">
    
    <!-- TOP HEADER (BASE.VN PORTAL STYLE - 01-welcome.html) -->
    <header class="max-w-[1440px] mx-auto w-full flex items-center justify-between py-2">
      <!-- Brand -->
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-white/10 backdrop-blur flex items-center justify-center font-bold text-base text-white border border-white/20">
          <span class="material-symbols-outlined text-lg">grid_view</span>
        </div>
        <span class="text-base font-extrabold tracking-wider uppercase">BASE.VN</span>
      </div>

      <!-- Right User Tools -->
      <div class="flex items-center gap-3">
        <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-semibold">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span>Đã đăng nhập</span>
        </div>
        
        <button
          type="button"
          class="flex items-center gap-2 p-1 pr-3 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur border border-white/15 transition cursor-pointer text-left text-white"
          title="Xem hồ sơ"
          @click="emit('select-app', 'profile')"
        >
          <img
            src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80"
            alt="Avatar"
            class="w-7 h-7 rounded-full object-cover"
          >
          <div class="text-left">
            <span class="text-xs font-semibold text-white block leading-tight">{{ props.user.display_name }}</span>
            <span class="text-[10px] text-white/60 block leading-none">{{ props.user.email }}</span>
          </div>
        </button>

        <button
          type="button"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 border border-rose-400/30 text-xs font-semibold transition cursor-pointer"
          title="Đăng xuất khỏi hệ thống"
          @click="emit('logout')"
        >
          <span class="material-symbols-outlined text-sm">logout</span>
          <span class="hidden sm:inline">Đăng xuất</span>
        </button>
      </div>
    </header>

    <!-- CENTER MAIN CONTENT -->
    <main class="max-w-[1440px] mx-auto w-full my-auto py-6 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
      
      <!-- LEFT CATEGORIES RAIL -->
      <nav class="lg:col-span-2 space-y-2 text-xs font-bold uppercase tracking-wider text-white/60">
        <div
          class="cursor-pointer transition py-1.5 pl-2 rounded-r flex items-center gap-2"
          :class="activeCategory === 'ALL' ? 'text-emerald-400 font-extrabold border-l-2 border-emerald-400 bg-white/5' : 'hover:text-white'"
          @click="activeCategory = 'ALL'"
        >
          <span>TẤT CẢ ỨNG DỤNG</span>
        </div>
        <div
          class="cursor-pointer transition py-1 pl-2 rounded-r flex items-center gap-2"
          :class="activeCategory === 'WORK+' ? 'text-emerald-400 font-extrabold border-l-2 border-emerald-400 bg-white/5' : 'hover:text-white'"
          @click="activeCategory = 'WORK+'"
        >
          <span>WORK+</span>
        </div>
        <div
          class="cursor-pointer transition py-1 pl-2 rounded-r flex items-center gap-2"
          :class="activeCategory === 'HRM+' ? 'text-emerald-400 font-extrabold border-l-2 border-emerald-400 bg-white/5' : 'hover:text-white'"
          @click="activeCategory = 'HRM+'"
        >
          <span>HRM+</span>
        </div>
        <div
          class="cursor-pointer transition py-1 pl-2 rounded-r flex items-center gap-2"
          :class="activeCategory === 'INFO+' ? 'text-emerald-400 font-extrabold border-l-2 border-emerald-400 bg-white/5' : 'hover:text-white'"
          @click="activeCategory = 'INFO+'"
        >
          <span>INFO+</span>
        </div>
        <div
          class="cursor-pointer transition py-1 pl-2 rounded-r flex items-center gap-2"
          :class="activeCategory === 'AI PLATFORM' ? 'text-emerald-400 font-extrabold border-l-2 border-emerald-400 bg-white/5' : 'hover:text-white'"
          @click="activeCategory = 'AI PLATFORM'"
        >
          <span>AI PLATFORM</span>
        </div>
        
        <div class="pt-4 border-t border-white/10">
          <button
            type="button"
            class="flex items-center gap-2 text-amber-300 hover:text-amber-200 transition py-1 pl-2 text-[11px] font-bold border-none bg-transparent cursor-pointer w-full text-left"
            @click="emit('select-app', 'admin')"
          >
            <span class="material-symbols-outlined text-sm">admin_panel_settings</span>
            <span>Phân hệ Quản trị</span>
          </button>
        </div>
      </nav>

      <!-- CENTER APPS GRID -->
      <section class="lg:col-span-10 space-y-6">
        
        <!-- Search Apps Box & Quick Notice -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div class="relative w-full max-w-md">
            <span class="material-symbols-outlined absolute left-3.5 top-3 text-white/50 text-lg">search</span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Tìm kiếm ứng dụng nhanh..."
              class="w-full pl-10 pr-4 py-2.5 bg-black/25 hover:bg-black/35 focus:bg-black/40 text-xs sm:text-sm text-white placeholder-white/50 rounded-lg border border-white/15 outline-none transition backdrop-blur"
            >
          </div>
          <p class="text-xs text-white/70">
            Nhấn vào bất kỳ ứng dụng nào để bắt đầu làm việc ngay.
          </p>
        </div>

        <!-- App Launcher Icons Grid (Base & AI Apps) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-5 pt-2">
          <div
            v-for="app in filteredApps"
            :key="app.id"
            class="group flex flex-col items-center text-center p-4 rounded-xl bg-white/5 hover:bg-white/15 border border-white/10 hover:border-white/20 transition cursor-pointer"
            @click="emit('select-app', app.id)"
          >
            <div
              class="w-14 h-14 rounded-2xl text-white flex items-center justify-center shadow-lg group-hover:scale-105 transition"
              :class="app.color"
            >
              <span class="material-symbols-outlined text-2xl">{{ app.icon }}</span>
            </div>
            <span class="text-sm font-bold text-white mt-3 block">{{ app.name }}</span>
            <span class="text-[11px] text-white/70 mt-0.5 line-clamp-1">{{ app.desc }}</span>
          </div>
        </div>

        <!-- Pagination dots -->
        <div class="flex justify-center gap-1.5 pt-2">
          <span class="w-2 h-2 rounded-full bg-white"></span>
          <span class="w-2 h-2 rounded-full bg-white/30"></span>
          <span class="w-2 h-2 rounded-full bg-white/30"></span>
        </div>

      </section>
    </main>

    <!-- BOTTOM LIVE CLOCK & ANNOUNCEMENTS WIDGET (01-welcome.html) -->
    <footer class="max-w-[1440px] mx-auto w-full pt-6 border-t border-white/15 grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
      
      <!-- Clock & Weather -->
      <div class="md:col-span-5 flex items-center gap-6">
        <div>
          <div class="text-5xl sm:text-6xl font-extrabold tracking-tight">
            {{ currentTime }}
          </div>
          <p class="text-xs font-semibold text-white/80 mt-1 uppercase tracking-wide">
            {{ currentDate }}
          </p>
          <div class="flex items-center gap-3 text-[11px] text-white/70 mt-1">
            <span>🌧️ 29 °C</span>
            <span>💧 82%</span>
            <span>🌅 05:38 - 18:20</span>
          </div>
        </div>
        <div class="text-sky-300">
          <span class="material-symbols-outlined text-5xl">rainy</span>
        </div>
      </div>

      <!-- Greeting & Global Company Notifications Stream -->
      <div class="md:col-span-7 space-y-2">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-bold text-white">Good evening, {{ props.user.display_name }}</h2>
          <div class="flex items-center gap-2 text-[11px] font-bold text-white/70 uppercase">
            <span>THÔNG BÁO TOÀN CÔNG TY</span>
          </div>
        </div>

        <!-- Notification list rows -->
        <div class="space-y-1.5 text-xs text-white/90">
          <div
            v-for="ann in MOCK_ANNOUNCEMENTS"
            :key="ann.id"
            class="flex items-center justify-between p-2 rounded-lg bg-black/20 hover:bg-black/30 transition cursor-pointer"
          >
            <div class="flex items-center gap-2.5 truncate">
              <img :src="ann.avatar" class="w-5 h-5 rounded-full object-cover flex-shrink-0">
              <span class="font-bold truncate">{{ ann.title }}</span>
            </div>
            <span class="text-[11px] text-white/60 flex-shrink-0">{{ ann.author }}, {{ ann.date }}</span>
          </div>
        </div>
      </div>

    </footer>

  </div>
</template>

<style scoped>
.welcome-container {
  background: linear-gradient(135deg, #114B43 0%, #15384D 50%, #0F2D42 100%);
}
</style>
