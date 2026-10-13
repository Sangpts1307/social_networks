<script setup lang="ts">
import { ref } from 'vue'
import type { AuthUser } from '../../api/auth'
import { MOCK_LEADERBOARD } from '../../mock/mockData.js'

const props = defineProps<{
  user: AuthUser
}>()

const emit = defineEmits<{
  (e: 'back'): void
  (e: 'select-app', appId: string): void
  (e: 'logout'): void
}>()

const leaderboard = ref([...MOCK_LEADERBOARD])
</script>

<template>
  <div class="min-h-screen text-slate-900 antialiased flex flex-col justify-between bg-[#F8FAFC] font-sans">
    
    <!-- TOP BLUE NAVBAR (MATCH 12-leaderboard.html) -->
    <header class="sticky top-0 z-40 bg-[#1D4ED8] text-white shadow-xs">
      <div class="max-w-[1440px] mx-auto px-4 lg:px-8 h-14 flex items-center justify-between gap-4">
        
        <!-- Brand & Sub Nav Links -->
        <div class="flex items-center gap-6 overflow-x-auto">
          <button
            type="button"
            class="flex items-center gap-2 flex-shrink-0 cursor-pointer border-none bg-transparent text-white"
            title="Về cổng Portal"
            @click="emit('back')"
          >
            <div class="w-7 h-7 rounded-full bg-white text-blue-700 flex items-center justify-center font-bold text-xs">
              <span class="material-symbols-outlined text-sm">more_horiz</span>
            </div>
            <span class="text-sm font-extrabold tracking-wider">BASE.VN</span>
          </button>

          <nav class="flex items-center gap-1 text-xs font-semibold text-white/80 whitespace-nowrap">
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'square')">Trang chủ</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'square')">Câu hỏi</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'knowledge')">Tri thức</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'library')">Thư viện</button>
            <button type="button" class="px-3 py-4 text-white font-bold border-b-2 border-white bg-transparent cursor-pointer border-t-0 border-l-0 border-r-0">Bảng xếp hạng</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'profile')">Cá nhân</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'admin')">Tùy chỉnh</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'search')">Tìm kiếm</button>
          </nav>
        </div>

        <!-- Right Search & User -->
        <div class="flex items-center gap-3 flex-shrink-0">
          <button
            type="button"
            class="p-0.5 rounded-full hover:ring-2 hover:ring-white border-none bg-transparent cursor-pointer"
            title="Hồ sơ cá nhân"
            @click="emit('select-app', 'profile')"
          >
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80" alt="Avatar" class="w-7 h-7 rounded-full object-cover">
          </button>
          <button
            type="button"
            class="p-1 text-white/80 hover:text-white cursor-pointer border-none bg-transparent"
            title="Đăng xuất"
            @click="emit('logout')"
          >
            <span class="material-symbols-outlined text-base">logout</span>
          </button>
        </div>
      </div>
    </header>

    <!-- MAIN LEADERBOARD WORKSPACE (MATCH 12-leaderboard.html) -->
    <main class="max-w-[1440px] mx-auto px-4 lg:px-8 py-8 flex-1 w-full space-y-8">
      
      <!-- TOP 5 PODIUM AVATARS -->
      <div class="bg-white rounded-lg border border-slate-200 p-8 shadow-xs">
        <div class="text-center max-w-md mx-auto mb-6">
          <h1 class="text-xl font-bold text-slate-900 tracking-tight">Vinh danh Đóng góp Tri thức &amp; Base Reward</h1>
          <p class="text-xs text-slate-500 mt-1">Ghi nhận các thành viên tích cực chia sẻ tài liệu và giải đáp câu hỏi chuyên môn</p>
        </div>

        <div class="flex flex-wrap items-end justify-center gap-6 sm:gap-10 pt-4 pb-2">
          
          <!-- Rank 4 -->
          <div class="flex flex-col items-center text-center space-y-2 order-4 sm:order-1">
            <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-slate-200 shadow-xs">
              <img :src="leaderboard[3]?.avatar" class="w-full h-full object-cover">
            </div>
            <div>
              <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">Hạng 4</span>
              <h3 class="font-bold text-slate-900 text-xs mt-1">{{ leaderboard[3]?.name }}</h3>
              <span class="text-[11px] text-slate-500 font-semibold">{{ leaderboard[3]?.points }}</span>
            </div>
          </div>

          <!-- Rank 2 -->
          <div class="flex flex-col items-center text-center space-y-2 order-2">
            <div class="w-20 h-20 rounded-full overflow-hidden border-3 border-slate-300 shadow-sm relative">
              <img :src="leaderboard[1]?.avatar" class="w-full h-full object-cover">
            </div>
            <div>
              <span class="px-2.5 py-0.5 rounded bg-slate-200 text-slate-800 font-bold text-[10px]">🥈 Hạng 2</span>
              <h3 class="font-bold text-slate-900 text-xs mt-1">{{ leaderboard[1]?.name }}</h3>
              <span class="text-[11px] text-blue-600 font-bold">{{ leaderboard[1]?.points }}</span>
            </div>
          </div>

          <!-- Rank 1: CHAMPION -->
          <div class="flex flex-col items-center text-center space-y-2 order-1 sm:order-3 -mt-4">
            <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-amber-400 shadow-md ring-4 ring-amber-100 relative">
              <img :src="leaderboard[0]?.avatar" class="w-full h-full object-cover">
            </div>
            <div>
              <span class="px-3 py-1 rounded-full bg-amber-400 text-slate-950 font-black text-xs shadow-xs">
                👑 Quán quân Top 1
              </span>
              <h3 class="font-extrabold text-slate-900 text-sm mt-1.5">{{ leaderboard[0]?.name }}</h3>
              <span class="text-xs text-amber-600 font-extrabold block">{{ leaderboard[0]?.points }}</span>
            </div>
          </div>

          <!-- Rank 3 -->
          <div class="flex flex-col items-center text-center space-y-2 order-3 sm:order-4">
            <div class="w-18 h-18 rounded-full overflow-hidden border-2 border-amber-600 shadow-xs relative">
              <img :src="leaderboard[2]?.avatar" class="w-full h-full object-cover">
            </div>
            <div>
              <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 font-bold text-[10px]">🥉 Hạng 3</span>
              <h3 class="font-bold text-slate-900 text-xs mt-1">{{ leaderboard[2]?.name }}</h3>
              <span class="text-[11px] text-amber-700 font-bold">{{ leaderboard[2]?.points }}</span>
            </div>
          </div>

          <!-- Rank 5 -->
          <div class="flex flex-col items-center text-center space-y-2 order-5">
            <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-slate-200 shadow-xs">
              <img :src="leaderboard[4]?.avatar" class="w-full h-full object-cover">
            </div>
            <div>
              <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">Hạng 5</span>
              <h3 class="font-bold text-slate-900 text-xs mt-1">{{ leaderboard[4]?.name }}</h3>
              <span class="text-[11px] text-slate-500 font-semibold">{{ leaderboard[4]?.points }}</span>
            </div>
          </div>

        </div>
      </div>

      <!-- LEADERBOARD TABLE -->
      <section class="bg-white rounded-lg border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
          <h2 class="font-bold text-slate-900 text-sm">Bảng thống kê điểm thưởng toàn hệ thống</h2>
          <span class="text-xs text-slate-500">Cập nhật lúc 00:00 hàng ngày</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-700">
            <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase border-b border-slate-200">
              <tr>
                <th class="px-4 py-3 text-center w-16">Hạng</th>
                <th class="px-4 py-3">Thành viên</th>
                <th class="px-4 py-3">Chức vụ &amp; Đơn vị</th>
                <th class="px-4 py-3">Danh hiệu vinh danh</th>
                <th class="px-4 py-3 text-right">Tổng điểm thưởng</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="item in leaderboard"
                :key="item.rank"
                class="hover:bg-slate-50 transition"
              >
                <td class="px-4 py-3 text-center font-bold text-slate-900">
                  <span
                    class="w-6 h-6 rounded-full inline-flex items-center justify-center text-xs"
                    :class="item.rank === 1 ? 'bg-amber-100 text-amber-800' : item.rank === 2 ? 'bg-slate-200 text-slate-800' : item.rank === 3 ? 'bg-amber-50 text-amber-900' : 'text-slate-500'"
                  >
                    #{{ item.rank }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2.5">
                    <img :src="item.avatar" class="w-7 h-7 rounded-full object-cover">
                    <span class="font-bold text-slate-900">{{ item.name }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-slate-600">{{ item.role }}</td>
                <td class="px-4 py-3">
                  <span class="px-2.5 py-1 rounded bg-blue-50 text-blue-700 font-semibold text-[11px]">
                    {{ item.badge }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right font-extrabold text-blue-700 font-mono text-sm">
                  {{ item.points }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

    </main>

  </div>
</template>
