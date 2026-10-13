<script setup lang="ts">
import { ref } from 'vue'
import { store } from '../../mock/store'

const activePeriod = ref<'thang' | 'quy' | 'nam'>('thang')

const rewardVouchers = [
  { id: 'v-1', title: 'Voucher Sách Chuyên Ngành (Tiki/Fahasa)', cost: 500, desc: 'Trị giá 200.000 VNĐ áp dụng sách công nghệ & quản trị' },
  { id: 'v-2', title: 'Tài trợ Khóa học Trực tuyến (Udemy/Coursera)', cost: 1000, desc: 'Hỗ trợ chi phí khóa học cấp chứng chỉ chuyên môn' },
  { id: 'v-3', title: 'Thẻ Quà Tặng Đồ Uống & Ăn Uống Doanh Nghiệp', cost: 300, desc: 'Trị giá 100.000 VNĐ dùng tại căn tin hoặc chuỗi đối tác' }
]

function handleRedeem(v: typeof rewardVouchers[0]) {
  if (store.currentUser.coins < v.cost) {
    alert(`Bạn cần ${v.cost} xu để đổi phần quà này (Hiện có: ${store.currentUser.coins} xu).`)
    return
  }
  store.currentUser.coins -= v.cost
  alert(`Đổi thành công: ${v.title}!\nMã voucher đã được gửi qua hòm thư ${store.currentUser.email}.`)
}
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 py-6 space-y-6">
    
    <!-- Top Summary & Personal Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white border border-slate-200 rounded-md p-4">
        <span class="text-[10px] text-slate-500 font-semibold uppercase">Điểm tri thức của bạn</span>
        <div class="flex items-baseline gap-2 mt-1">
          <span class="text-2xl font-bold text-blue-600">{{ store.currentUser.points }}</span>
          <span class="text-xs text-slate-500">pts</span>
        </div>
        <p class="text-[11px] text-slate-400 mt-1">Xếp hạng #2 toàn công ty</p>
      </div>

      <div class="bg-white border border-slate-200 rounded-md p-4">
        <span class="text-[10px] text-slate-500 font-semibold uppercase">Số xu thưởng khả dụng</span>
        <div class="flex items-baseline gap-2 mt-1">
          <span class="text-2xl font-bold text-amber-600">{{ store.currentUser.coins }}</span>
          <span class="text-xs text-slate-500">xu</span>
        </div>
        <p class="text-[11px] text-slate-400 mt-1">Có thể dùng để đổi quà và voucher</p>
      </div>

      <div class="bg-white border border-slate-200 rounded-md p-4">
        <span class="text-[10px] text-slate-500 font-semibold uppercase">Đóng góp được ghi nhận</span>
        <div class="flex items-baseline gap-2 mt-1">
          <span class="text-2xl font-bold text-emerald-600">{{ store.currentUser.answersCount }}</span>
          <span class="text-xs text-slate-500">câu trả lời hữu ích</span>
        </div>
        <p class="text-[11px] text-slate-400 mt-1">Danh hiệu: Chuyên gia Vàng</p>
      </div>
    </div>

    <!-- Leaderboard Table -->
    <div class="bg-white border border-slate-200 rounded-md p-4 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-100">
        <div>
          <h2 class="text-sm font-bold text-slate-900 leading-tight">Bảng xếp hạng đóng góp tri thức</h2>
          <p class="text-xs text-slate-500">Tôn vinh các thành viên tích cực chia sẻ và giải đáp chuyên môn</p>
        </div>

        <div class="flex items-center gap-1">
          <button
            v-for="p in [{ key: 'thang', label: 'Tháng này' }, { key: 'quy', label: 'Quý này' }, { key: 'nam', label: 'Cả năm' }]"
            :key="p.key"
            type="button"
            class="px-2.5 py-1 rounded text-xs font-semibold cursor-pointer border-none"
            :class="activePeriod === p.key ? 'bg-slate-900 text-white' : 'text-slate-600 bg-slate-100 hover:bg-slate-200'"
            @click="activePeriod = p.key as any"
          >
            {{ p.label }}
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-200 text-slate-500 font-semibold">
              <th class="py-2 px-3 w-12 text-center">Hạng</th>
              <th class="py-2 px-3">Thành viên</th>
              <th class="py-2 px-3">Phòng ban</th>
              <th class="py-2 px-3">Danh hiệu</th>
              <th class="py-2 px-3 text-right">Câu trả lời đúng</th>
              <th class="py-2 px-3 text-right">Điểm tri thức</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="item in store.leaderboard"
              :key="item.rank"
              class="hover:bg-slate-50 transition"
              :class="item.name === store.currentUser.name ? 'bg-blue-50/40 font-semibold' : ''"
            >
              <td class="py-2.5 px-3 text-center">
                <span
                  class="w-6 h-6 inline-flex items-center justify-center rounded font-bold text-xs"
                  :class="item.rank === 1 ? 'bg-amber-100 text-amber-800' : item.rank === 2 ? 'bg-slate-200 text-slate-800' : item.rank === 3 ? 'bg-orange-100 text-orange-800' : 'text-slate-500'"
                >
                  {{ item.rank }}
                </span>
              </td>
              <td class="py-2.5 px-3">
                <div class="flex items-center gap-2.5">
                  <img :src="item.avatar" :alt="item.name" class="w-7 h-7 rounded object-cover">
                  <div>
                    <span class="font-bold text-slate-900 block leading-tight">{{ item.name }}</span>
                    <span class="text-[10px] text-slate-400">{{ item.role }}</span>
                  </div>
                </div>
              </td>
              <td class="py-2.5 px-3 text-slate-600">{{ item.department }}</td>
              <td class="py-2.5 px-3">
                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                  {{ item.badge }}
                </span>
              </td>
              <td class="py-2.5 px-3 text-right font-medium text-slate-700">{{ item.acceptedAnswers }}</td>
              <td class="py-2.5 px-3 text-right font-bold text-blue-600">{{ item.points }} pts</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Reward Voucher Exchange Catalog -->
    <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
      <div class="flex items-center justify-between pb-2 border-b border-slate-100">
        <div>
          <h2 class="text-sm font-bold text-slate-900 leading-tight">Quy đổi xu thưởng (Base Reward)</h2>
          <p class="text-xs text-slate-500">Đổi xu tích lũy lấy các phần thưởng học tập và quyền lợi nội bộ</p>
        </div>
        <span class="text-xs font-semibold text-slate-700">Số dư hiện tại: {{ store.currentUser.coins }} xu</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div
          v-for="v in rewardVouchers"
          :key="v.id"
          class="p-3.5 rounded border border-slate-200 bg-slate-50 flex flex-col justify-between space-y-3"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                {{ v.cost }} xu
              </span>
            </div>
            <h3 class="font-bold text-slate-900 text-xs mt-2">{{ v.title }}</h3>
            <p class="text-[11px] text-slate-500 mt-1">{{ v.desc }}</p>
          </div>

          <button
            type="button"
            class="w-full py-1.5 rounded bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold cursor-pointer border-none transition disabled:opacity-50"
            :disabled="store.currentUser.coins < v.cost"
            @click="handleRedeem(v)"
          >
            Đổi quà ngay
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
