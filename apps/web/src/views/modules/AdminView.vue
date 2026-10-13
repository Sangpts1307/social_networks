<script setup lang="ts">
import { ref } from 'vue'
import { store } from '../../mock/store'

const activeSubTab = ref<'moderation' | 'users' | 'settings'>('moderation')

const usersList = [
  { id: '1', name: 'Trần Hoàng Nam', email: 'nam.th@openhello.vn', role: 'Quản trị viên', dept: 'Khối Kỹ thuật', status: 'Hoạt động' },
  { id: '2', name: 'Võ Thành Luân', email: 'luan.vt@openhello.vn', role: 'Quản trị viên', dept: 'Khối Kỹ thuật', status: 'Hoạt động' },
  { id: '3', name: 'Lê Phương Thảo', email: 'thao.lp@openhello.vn', role: 'Quản lý Nhân sự', dept: 'Nhân sự & Văn hóa', status: 'Hoạt động' },
  { id: '4', name: 'Nguyễn Minh Trang', email: 'trang.nm@openhello.vn', role: 'Nhân viên', dept: 'Ban Sản phẩm', status: 'Hoạt động' },
  { id: '5', name: 'Phạm Quốc Bảo', email: 'bao.pq@openhello.vn', role: 'Nhân viên', dept: 'Khối Kỹ thuật', status: 'Hoạt động' }
]

function handleModerate(id: string, action: 'approved' | 'hidden' | 'deleted') {
  store.moderateItem(id, action)
}
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 py-6 space-y-6">
    
    <!-- Top Stats Overview -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-white border border-slate-200 rounded-md p-4">
        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Tổng nhân sự</span>
        <span class="text-xl font-bold text-slate-900 mt-1 block">142</span>
        <span class="text-[11px] text-emerald-600 mt-0.5 block">100% tài khoản kích hoạt</span>
      </div>

      <div class="bg-white border border-slate-200 rounded-md p-4">
        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Nội dung tri thức</span>
        <span class="text-xl font-bold text-slate-900 mt-1 block">384</span>
        <span class="text-[11px] text-slate-400 mt-0.5 block">Bài viết & Q&A</span>
      </div>

      <div class="bg-white border border-slate-200 rounded-md p-4">
        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Tài liệu đã AI Index</span>
        <span class="text-xl font-bold text-blue-600 mt-1 block">42</span>
        <span class="text-[11px] text-slate-400 mt-0.5 block">Đã liên kết Knowledge Graph</span>
      </div>

      <div class="bg-white border border-slate-200 rounded-md p-4">
        <span class="text-[10px] text-slate-500 uppercase font-semibold block">Cần kiểm duyệt</span>
        <span class="text-xl font-bold text-rose-600 mt-1 block">
          {{ store.moderationItems.filter(m => m.status === 'pending').length }}
        </span>
        <span class="text-[11px] text-rose-600 mt-0.5 block">Cần xử lý kịp thời</span>
      </div>
    </div>

    <!-- Admin Subtabs -->
    <div class="bg-white border border-slate-200 rounded-md p-4 space-y-4">
      <div class="flex items-center gap-1 border-b border-slate-100 pb-2">
        <button
          type="button"
          class="px-3 py-1.5 rounded text-xs font-semibold cursor-pointer border-none transition"
          :class="activeSubTab === 'moderation' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
          @click="activeSubTab = 'moderation'"
        >
          Hàng đợi kiểm duyệt nội dung ({{ store.moderationItems.length }})
        </button>
        <button
          type="button"
          class="px-3 py-1.5 rounded text-xs font-semibold cursor-pointer border-none transition"
          :class="activeSubTab === 'users' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
          @click="activeSubTab = 'users'"
        >
          Quản lý tài khoản người dùng
        </button>
      </div>

      <!-- Tab: Moderation Queue -->
      <div v-if="activeSubTab === 'moderation'" class="space-y-3">
        <div class="text-xs text-slate-500">
          Danh sách bài viết hoặc bình luận bị người dùng báo cáo hoặc AI Scanner tự động phát hiện nghi vấn.
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="border-b border-slate-200 text-slate-500 font-semibold">
                <th class="py-2 px-3">Tác giả</th>
                <th class="py-2 px-3">Nội dung trích đoạn</th>
                <th class="py-2 px-3">Lý do & Nguồn gắn cờ</th>
                <th class="py-2 px-3">Thời gian</th>
                <th class="py-2 px-3">Trạng thái</th>
                <th class="py-2 px-3 text-right">Thao tác xử lý</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in store.moderationItems" :key="item.id" class="hover:bg-slate-50">
                <td class="py-3 px-3">
                  <span class="font-bold text-slate-900 block">{{ item.author }}</span>
                  <span class="text-[10px] text-slate-400">{{ item.authorDept }}</span>
                </td>
                <td class="py-3 px-3 max-w-xs">
                  <p class="text-slate-700 line-clamp-2 leading-relaxed">{{ item.content }}</p>
                </td>
                <td class="py-3 px-3">
                  <span class="text-slate-800 font-medium block leading-tight">{{ item.reason }}</span>
                  <span 
                    class="text-[10px] px-1.5 py-0.2 rounded font-semibold inline-block mt-1"
                    :class="item.flaggedBy === 'AI Scanner' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800'"
                  >
                    {{ item.flaggedBy }}
                  </span>
                </td>
                <td class="py-3 px-3 text-slate-400 text-[11px]">{{ item.flaggedAt }}</td>
                <td class="py-3 px-3">
                  <span 
                    class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                    :class="item.status === 'pending' ? 'bg-amber-100 text-amber-800' : item.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'"
                  >
                    {{ item.status === 'pending' ? 'Chờ duyệt' : item.status === 'approved' ? 'Đã duyệt' : item.status === 'hidden' ? 'Đã ẩn' : 'Đã xóa' }}
                  </span>
                </td>
                <td class="py-3 px-3 text-right space-x-1">
                  <template v-if="item.status === 'pending'">
                    <button
                      type="button"
                      class="px-2.5 py-1 rounded bg-slate-900 text-white font-semibold cursor-pointer border-none text-[11px] hover:bg-slate-800"
                      @click="handleModerate(item.id, 'approved')"
                    >
                      Duyệt
                    </button>
                    <button
                      type="button"
                      class="px-2.5 py-1 rounded bg-amber-500 text-white font-semibold cursor-pointer border-none text-[11px] hover:bg-amber-600"
                      @click="handleModerate(item.id, 'hidden')"
                    >
                      Ẩn
                    </button>
                    <button
                      type="button"
                      class="px-2.5 py-1 rounded bg-rose-600 text-white font-semibold cursor-pointer border-none text-[11px] hover:bg-rose-700"
                      @click="handleModerate(item.id, 'deleted')"
                    >
                      Xóa
                    </button>
                  </template>
                  <span v-else class="text-[11px] text-slate-400 italic">Đã hoàn tất</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Users Management -->
      <div v-if="activeSubTab === 'users'" class="space-y-3">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="border-b border-slate-200 text-slate-500 font-semibold">
                <th class="py-2 px-3">Họ và tên</th>
                <th class="py-2 px-3">Email</th>
                <th class="py-2 px-3">Phòng ban</th>
                <th class="py-2 px-3">Vai trò phân quyền</th>
                <th class="py-2 px-3">Trạng thái</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="u in usersList" :key="u.id" class="hover:bg-slate-50">
                <td class="py-2.5 px-3 font-bold text-slate-900">{{ u.name }}</td>
                <td class="py-2.5 px-3 text-slate-600">{{ u.email }}</td>
                <td class="py-2.5 px-3 text-slate-600">{{ u.dept }}</td>
                <td class="py-2.5 px-3">
                  <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                    {{ u.role }}
                  </span>
                </td>
                <td class="py-2.5 px-3">
                  <span class="text-emerald-600 font-semibold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> {{ u.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </div>
</template>
