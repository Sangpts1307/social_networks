<script setup lang="ts">
import { ref } from 'vue'
import { store, type AppScreen } from '../../mock/store'

interface AppItem {
  id: AppScreen
  name: string
  desc: string
  icon: string
  category: 'Giao tiếp' | 'Tri thức' | 'AI' | 'Quản lý'
  badge?: string
}

const activeCategory = ref<'TẤT CẢ' | 'Giao tiếp' | 'Tri thức' | 'AI' | 'Quản lý'>('TẤT CẢ')

const apps: AppItem[] = [
  { id: 'social', name: 'Bảng tin doanh nghiệp', desc: 'Chia sẻ thông tin, thông báo và sự kiện nội bộ', icon: 'feed', category: 'Giao tiếp' },
  { id: 'messenger', name: 'Tin nhắn trực tiếp', desc: 'Trò chuyện thời gian thực giữa các thành viên', icon: 'chat', category: 'Giao tiếp' },
  { id: 'qa', name: 'Hỏi đáp tri thức', desc: 'Đặt câu hỏi, giải đáp chuyên môn và tích lũy điểm', icon: 'school', category: 'Tri thức' },
  { id: 'library', name: 'Thư viện tài liệu', desc: 'Kho lưu trữ tài liệu chuẩn hóa theo phòng ban', icon: 'menu_book', category: 'Tri thức' },
  { id: 'graph', name: 'Cây tri thức AI', desc: 'Khám phá liên kết thực thể và bản đồ kiến thức', icon: 'account_tree', category: 'AI', badge: 'v2.1' },
  { id: 'search', name: 'Tìm kiếm AI kết hợp', desc: 'Truy vấn ngữ nghĩa tài liệu và tìm chuyên gia', icon: 'troubleshoot', category: 'AI' },
  { id: 'leaderboard', name: 'Vinh danh & Điểm thưởng', desc: 'Bảng xếp hạng đóng góp và quy đổi xu', icon: 'military_tech', category: 'Quản lý' },
  { id: 'profile', name: 'Hồ sơ cá nhân', desc: 'Quản lý thông tin, kỹ năng và lịch sử hoạt động', icon: 'person', category: 'Quản lý' },
  { id: 'admin', name: 'Quản trị hệ thống', desc: 'Kiểm duyệt nội dung, phân quyền và giám sát', icon: 'admin_panel_settings', category: 'Quản lý', badge: 'Admin' }
]

function filteredApps() {
  if (activeCategory.value === 'TẤT CẢ') return apps
  return apps.filter(a => a.category === activeCategory.value)
}
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 py-6 space-y-6">
    
    <!-- Top Welcome Banner (Minimalist) -->
    <div class="bg-white border border-slate-200 rounded-md p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h1 class="text-xl font-bold text-slate-900">Xin chào, {{ store.currentUser.name }}</h1>
          <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
            {{ store.currentUser.roleTitle }}
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-1">
          {{ store.currentUser.department }} • {{ store.currentUser.email }}
        </p>
      </div>

      <!-- Quick Metrics -->
      <div class="flex items-center gap-3">
        <div class="bg-slate-50 border border-slate-200 rounded px-3 py-2 text-center min-w-[90px]">
          <span class="text-[10px] text-slate-500 uppercase font-semibold block">Xu tích lũy</span>
          <span class="text-base font-bold text-amber-600 leading-none">{{ store.currentUser.coins }}</span>
        </div>
        <div class="bg-slate-50 border border-slate-200 rounded px-3 py-2 text-center min-w-[90px]">
          <span class="text-[10px] text-slate-500 uppercase font-semibold block">Điểm tri thức</span>
          <span class="text-base font-bold text-blue-600 leading-none">{{ store.currentUser.points }}</span>
        </div>
        <div class="bg-slate-50 border border-slate-200 rounded px-3 py-2 text-center min-w-[90px]">
          <span class="text-[10px] text-slate-500 uppercase font-semibold block">Câu trả lời</span>
          <span class="text-base font-bold text-slate-800 leading-none">{{ store.currentUser.answersCount }}</span>
        </div>
      </div>
    </div>

    <!-- Category Filter Tabs -->
    <div class="flex items-center gap-1.5 border-b border-slate-200 pb-2 overflow-x-auto">
      <button
        v-for="cat in (['TẤT CẢ', 'Giao tiếp', 'Tri thức', 'AI', 'Quản lý'] as const)"
        :key="cat"
        type="button"
        class="px-3 py-1.5 rounded text-xs font-semibold cursor-pointer border-none transition"
        :class="activeCategory === cat ? 'bg-slate-900 text-white' : 'text-slate-600 bg-white border border-slate-200 hover:bg-slate-50'"
        @click="activeCategory = cat"
      >
        {{ cat }}
      </button>
    </div>

    <!-- Application Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="app in filteredApps()"
        :key="app.id"
        class="bg-white border border-slate-200 hover:border-slate-400 rounded-md p-4 transition cursor-pointer flex flex-col justify-between"
        @click="store.setScreen(app.id)"
      >
        <div>
          <div class="flex items-center justify-between">
            <div class="w-10 h-10 rounded bg-slate-100 text-slate-800 flex items-center justify-center">
              <span class="material-symbols-outlined text-2xl">{{ app.icon }}</span>
            </div>
            <span v-if="app.badge" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
              {{ app.badge }}
            </span>
          </div>

          <h3 class="font-bold text-slate-900 text-sm mt-3">{{ app.name }}</h3>
          <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ app.desc }}</p>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-semibold">
          <span>Truy cập</span>
          <span class="material-symbols-outlined text-sm">arrow_forward</span>
        </div>
      </div>
    </div>

    <!-- Recent Updates Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Latest Posts Preview -->
      <div class="bg-white border border-slate-200 rounded-md p-4">
        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
          <h2 class="text-xs font-bold text-slate-900 uppercase">Bảng tin mới nhất</h2>
          <button 
            type="button" 
            class="text-xs text-blue-600 hover:underline border-none bg-transparent cursor-pointer font-medium p-0"
            @click="store.setScreen('social')"
          >
            Xem tất cả
          </button>
        </div>
        <div class="space-y-3">
          <div 
            v-for="post in store.posts.slice(0, 2)" 
            :key="post.id"
            class="p-2.5 rounded bg-slate-50 hover:bg-slate-100 transition cursor-pointer"
            @click="store.setScreen('social')"
          >
            <div class="flex items-center justify-between text-[11px] text-slate-500">
              <span class="font-bold text-slate-800">{{ post.author }}</span>
              <span>{{ post.createdAt }}</span>
            </div>
            <p class="text-xs text-slate-700 mt-1 line-clamp-2">{{ post.content }}</p>
          </div>
        </div>
      </div>

      <!-- Latest Questions Preview -->
      <div class="bg-white border border-slate-200 rounded-md p-4">
        <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
          <h2 class="text-xs font-bold text-slate-900 uppercase">Hỏi đáp tri thức nổi bật</h2>
          <button 
            type="button" 
            class="text-xs text-blue-600 hover:underline border-none bg-transparent cursor-pointer font-medium p-0"
            @click="store.setScreen('qa')"
          >
            Xem tất cả
          </button>
        </div>
        <div class="space-y-3">
          <div 
            v-for="q in store.questions.slice(0, 2)" 
            :key="q.id"
            class="p-2.5 rounded bg-slate-50 hover:bg-slate-100 transition cursor-pointer"
            @click="store.selectedQuestionId = q.id; store.setScreen('qa')"
          >
            <div class="flex items-center justify-between text-[11px] text-slate-500">
              <span class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 font-semibold">{{ q.category }}</span>
              <span>{{ q.votes }} lượt bình chọn</span>
            </div>
            <p class="text-xs font-semibold text-slate-800 mt-1 line-clamp-1">{{ q.title }}</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>
