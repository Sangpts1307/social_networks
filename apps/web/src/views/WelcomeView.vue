<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { canOpenAdmin, type AuthUser } from '../api/auth'
import PortalShell from '../components/layout/PortalShell.vue'
import { portalCategory } from '../components/layout/portalState'
import { MOCK_APPS, MOCK_ANNOUNCEMENTS } from '../mock/mockData.js'

const props = defineProps<{
  user: AuthUser
}>()

const emit = defineEmits<{
  (e: 'logout'): void
  (e: 'select-app', appId: string): void
}>()

const activeCategory = portalCategory
const searchQuery = ref('')
const currentTime = ref('19:13:00')
const currentDate = ref('')
const greeting = ref('Xin chào')

let timer: number | undefined

function updateClock() {
  const now = new Date()
  currentTime.value = new Intl.DateTimeFormat('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).format(now)
  currentDate.value = `Hà Nội, ${new Intl.DateTimeFormat('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh', weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' }).format(now)}`
  const hour = Number(new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Ho_Chi_Minh', hour: 'numeric', hour12: false }).format(now))
  greeting.value = hour < 11 ? 'Chào buổi sáng' : hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối'
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
    if (app.id === 'admin' && !canOpenAdmin(props.user)) return false
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
  <PortalShell :user="user" :active-category="activeCategory" @logout="emit('logout')" @select-app="appId => emit('select-app', appId)" @category-change="category => activeCategory = category">
      <div class="w-full space-y-6">
        
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

      </div>
    <template #footer>
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
        </div>
      </div>

      <!-- Greeting & Global Company Notifications Stream -->
      <div class="md:col-span-7 space-y-2">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-bold text-white">{{ greeting }}, {{ props.user.display_name }}</h2>
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
    </template>
  </PortalShell>
</template>
