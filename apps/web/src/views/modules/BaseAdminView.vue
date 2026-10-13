<script setup lang="ts">
import { ref } from 'vue'
import type { AuthUser } from '../../api/auth'

const props = defineProps<{
  user: AuthUser
}>()

const emit = defineEmits<{
  (e: 'back'): void
  (e: 'select-app', appId: string): void
  (e: 'logout'): void
}>()

const queueItems = ref([
  {
    id: 'q-1',
    type: 'Bài viết Inside',
    typeBadge: 'bg-purple-100 text-purple-700',
    title: 'Đăng tải thông báo cập nhật sơ đồ kiến trúc Neo4j tuần thứ 3 tháng 9',
    author: 'Võ Thành Luân',
    time: '20 phút trước',
    status: 'Chờ duyệt'
  },
  {
    id: 'q-2',
    type: 'Câu hỏi Q&A',
    typeBadge: 'bg-emerald-100 text-emerald-700',
    title: 'Cách thiết lập phân quyền RBAC và tích hợp LDAP trong hệ thống Gateway?',
    author: 'Phạm Phát',
    time: '45 phút trước',
    status: 'Chờ duyệt'
  },
  {
    id: 'q-3',
    type: 'Tài liệu Library',
    typeBadge: 'bg-rose-100 text-rose-700',
    title: 'Tài liệu hướng dẫn deploy microservices qua Docker Swarm nội bộ',
    author: 'Sang Phạm',
    time: '2 giờ trước',
    status: 'Chờ duyệt'
  }
])

function approveItem(item: any) {
  item.status = 'Đã phê duyệt'
}

function rejectItem(item: any) {
  item.status = 'Đã từ chối'
}

function refreshQueue() {
  if (typeof window !== 'undefined') {
    window.alert('Đã đồng bộ hàng đợi mới nhất!')
  }
}

function exportReport() {
  if (typeof window !== 'undefined') {
    window.alert('Đang trích xuất báo cáo kiểm duyệt tuần!')
  }
}
</script>

<template>
  <div class="min-h-screen text-slate-900 antialiased flex flex-col justify-between bg-[#F8FAFC] font-sans">
    
    <!-- TOP APP NAVBAR (MATCH 07-admin-social.html) -->
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200">
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
            <span class="text-xs font-bold text-slate-800 hidden sm:block">{{ props.user.display_name }} (Quản trị)</span>
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

    <!-- MAIN ADMIN WORKSPACE -->
    <main class="max-w-[1440px] mx-auto px-4 lg:px-8 py-6 flex-1 w-full space-y-6">
      
      <!-- Header Title -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
          <h1 class="text-xl font-bold text-slate-900 tracking-tight">Hàng đợi kiểm duyệt nội dung Inside &amp; Square</h1>
          <p class="text-xs text-slate-500 mt-0.5">Giám sát bài viết, câu hỏi Q&amp;A và tài liệu được chia sẻ trong hệ sinh thái</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-3.5 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-md cursor-pointer"
            @click="refreshQueue"
          >
            Làm mới
          </button>
          <button
            type="button"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-md shadow-xs cursor-pointer border-none"
            @click="exportReport"
          >
            Xuất báo cáo
          </button>
        </div>
      </div>

      <!-- 4 KPI Metrics Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-1">
          <span class="text-slate-500 font-semibold block">Chờ kiểm duyệt</span>
          <span class="text-2xl font-black text-amber-500 font-mono block">3</span>
          <span class="text-[11px] text-slate-400">Yêu cầu cần xử lý</span>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-1">
          <span class="text-slate-500 font-semibold block">Đã phê duyệt tuần</span>
          <span class="text-2xl font-black text-emerald-600 font-mono block">128</span>
          <span class="text-[11px] text-emerald-600 font-semibold">Tăng 14% so với tuần trước</span>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-1">
          <span class="text-slate-500 font-semibold block">Gắn cờ vi phạm</span>
          <span class="text-2xl font-black text-rose-500 font-mono block">0</span>
          <span class="text-[11px] text-slate-400">Không có vi phạm</span>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-1">
          <span class="text-slate-500 font-semibold block">Tổng nội dung tri thức</span>
          <span class="text-2xl font-black text-blue-600 font-mono block">450</span>
          <span class="text-[11px] text-slate-400">Bài viết, Q&amp;A &amp; PDF</span>
        </div>
      </div>

      <!-- Moderation Queue Table -->
      <section class="bg-white rounded-lg border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
          <h2 class="font-bold text-slate-900 text-sm">Danh sách nội dung đang chờ xem xét</h2>
          <span class="text-xs text-slate-500">3 mục chờ duyệt</span>
        </div>

        <div class="overflow-x-auto text-xs">
          <table class="w-full text-left text-slate-700">
            <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase border-b border-slate-200">
              <tr>
                <th class="px-4 py-3">Loại nội dung</th>
                <th class="px-4 py-3">Tiêu đề / Tóm tắt</th>
                <th class="px-4 py-3">Tác giả</th>
                <th class="px-4 py-3">Thời gian</th>
                <th class="px-4 py-3">Trạng thái</th>
                <th class="px-4 py-3 text-right">Thao tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in queueItems" :key="item.id" class="hover:bg-slate-50 transition">
                <td class="px-4 py-3">
                  <span class="px-2.5 py-0.5 rounded text-[10px] font-bold" :class="item.typeBadge">
                    {{ item.type }}
                  </span>
                </td>
                <td class="px-4 py-3 font-semibold text-slate-900 max-w-md truncate">
                  {{ item.title }}
                </td>
                <td class="px-4 py-3 text-slate-600 font-medium">
                  {{ item.author }}
                </td>
                <td class="px-4 py-3 text-slate-400 text-[11px]">
                  {{ item.time }}
                </td>
                <td class="px-4 py-3">
                  <span
                    class="px-2 py-0.5 rounded text-[10px] font-bold"
                    :class="item.status === 'Chờ duyệt' ? 'bg-amber-100 text-amber-800' : item.status === 'Đã phê duyệt' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                  >
                    {{ item.status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right space-x-1.5">
                  <button
                    v-if="item.status === 'Chờ duyệt'"
                    type="button"
                    class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] cursor-pointer border-none"
                    @click="approveItem(item)"
                  >
                    Duyệt
                  </button>
                  <button
                    v-if="item.status === 'Chờ duyệt'"
                    type="button"
                    class="px-2.5 py-1 rounded bg-rose-600 hover:bg-rose-700 text-white font-bold text-[11px] cursor-pointer border-none"
                    @click="rejectItem(item)"
                  >
                    Từ chối
                  </button>
                  <span v-else class="text-slate-400 text-[11px] italic">Đã xử lý</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

    </main>

  </div>
</template>
