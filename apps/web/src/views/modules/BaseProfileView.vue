<script setup lang="ts">
import type { AuthUser } from '../../api/auth'

const props = defineProps<{
  user: AuthUser
}>()

const emit = defineEmits<{
  (e: 'back'): void
  (e: 'select-app', appId: string): void
  (e: 'logout'): void
}>()

function showSecurityNotice() {
  window.alert('Tính năng cập nhật mật khẩu an toàn sẽ gửi mã xác thực OTP về email của bạn.')
}
</script>

<template>
  <div class="min-h-screen text-slate-900 antialiased flex flex-col justify-between bg-[#F8FAFC] font-sans">
    
    <!-- TOP HEADER (MATCH 06-profile.html) -->
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-xs">
      <div class="max-w-[1440px] mx-auto px-4 lg:px-8 h-14 flex items-center justify-between gap-4">
        
        <div class="flex items-center gap-3">
          <button
            type="button"
            class="flex items-center gap-2 cursor-pointer border-none bg-transparent"
            title="Về cổng Portal"
            @click="emit('back')"
          >
            <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
              <span class="material-symbols-outlined text-base">change_history</span>
            </div>
            <div class="text-left">
              <span class="text-sm font-extrabold tracking-wider text-slate-900 block leading-tight">BASE.VN</span>
              <span class="text-[11px] text-slate-500">Base Account &amp; Security</span>
            </div>
          </button>
        </div>

        <div class="flex items-center gap-4 text-sm font-semibold">
          <button
            type="button"
            class="text-slate-600 hover:text-slate-900 border-none bg-transparent cursor-pointer text-xs"
            @click="emit('select-app', 'inside')"
          >
            Mạng xã hội (Inside)
          </button>
          <button
            type="button"
            class="text-slate-600 hover:text-slate-900 border-none bg-transparent cursor-pointer text-xs"
            @click="emit('select-app', 'square')"
          >
            Học tập &amp; Q&amp;A (Square)
          </button>

          <div class="flex items-center gap-2 p-1 pl-2 pr-3 rounded-lg bg-slate-100 text-slate-900 border border-slate-200">
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80" alt="Avatar" class="w-6 h-6 rounded-full object-cover">
            <span class="font-bold text-xs">{{ props.user.display_name }}</span>
          </div>

          <button
            type="button"
            class="p-1.5 text-slate-400 hover:text-slate-900 rounded cursor-pointer border-none bg-transparent"
            title="Đăng xuất"
            @click="emit('logout')"
          >
            <span class="material-symbols-outlined text-lg">logout</span>
          </button>
        </div>

      </div>
    </header>

    <!-- MAIN BASE ACCOUNT VIEW (MATCH 06-profile.html) -->
    <main class="max-w-[1440px] mx-auto px-4 lg:px-8 py-6 flex-1 w-full grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      
      <!-- LEFT SIDEBAR: TẤT CẢ ỨNG DỤNG & TRẠNG THÁI HỒ SƠ -->
      <aside class="lg:col-span-3 space-y-4">
        <div class="bg-white rounded-lg border border-slate-200 p-3 shadow-xs space-y-2">
          <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider px-2">TẤT CẢ ỨNG DỤNG</h3>
          <nav class="space-y-0.5 text-sm font-medium">
            <button
              type="button"
              class="w-full flex items-center justify-between p-2 rounded hover:bg-slate-50 text-slate-800 transition border-none bg-transparent cursor-pointer text-left"
              @click="emit('back')"
            >
              <span>🏠 Trang chủ Portal</span>
              <span class="material-symbols-outlined text-slate-400 text-base">chevron_right</span>
            </button>
            <button
              type="button"
              class="w-full flex items-center justify-between p-2 rounded hover:bg-slate-50 text-slate-800 transition border-none bg-transparent cursor-pointer text-left"
              @click="emit('select-app', 'inside')"
            >
              <span>🌐 Hệ thống MXH (Inside)</span>
              <span class="material-symbols-outlined text-slate-400 text-base">chevron_right</span>
            </button>
            <button
              type="button"
              class="w-full flex items-center justify-between p-2 rounded hover:bg-slate-50 text-slate-800 transition border-none bg-transparent cursor-pointer text-left"
              @click="emit('select-app', 'library')"
            >
              <span>🎓 Hệ thống học tập (EDU)</span>
              <span class="material-symbols-outlined text-slate-400 text-base">chevron_right</span>
            </button>
          </nav>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-2.5 text-xs">
          <span class="font-bold text-slate-400 uppercase tracking-wider block text-[11px]">Trạng thái hồ sơ</span>
          <div class="flex items-center justify-between font-medium text-slate-700 text-sm">
            <span>Vai trò:</span>
            <span class="text-slate-900 font-bold">Thành viên hệ sinh thái</span>
          </div>
          <div class="flex items-center justify-between font-medium text-slate-700 text-sm">
            <span>Trạng thái:</span>
            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-xs">Đang hoạt động</span>
          </div>
        </div>
      </aside>

      <!-- RIGHT MAIN CONTENT: PROFILE DETAILS -->
      <section class="lg:col-span-9 space-y-5">
        
        <!-- User Info Card -->
        <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-4">
              <img
                src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80"
                class="w-16 h-16 rounded-full object-cover border-2 border-slate-200 shadow-xs"
              >
              <div>
                <h1 class="text-lg font-bold text-slate-900">{{ props.user.display_name }}</h1>
                <p class="text-slate-500 text-xs mt-0.5">{{ props.user.email }}</p>
                <div class="mt-2 flex items-center gap-2">
                  <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold">
                    Tài khoản doanh nghiệp OpenHello
                  </span>
                </div>
              </div>
            </div>

            <button
              type="button"
              class="px-3.5 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer border-none"
              @click="showSecurityNotice"
            >
              Cập nhật thông tin
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100 space-y-1">
              <span class="text-slate-400 font-medium block">Mã định danh User ID</span>
              <span class="font-mono font-bold text-slate-800 text-sm block truncate">{{ props.user.id }}</span>
            </div>
            
            <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-100 space-y-1">
              <span class="text-slate-400 font-medium block">Đơn vị Tổ chức (Org ID)</span>
              <span class="font-mono font-bold text-slate-800 text-sm block truncate">{{ props.user.organization_id }}</span>
            </div>
          </div>
        </div>

        <!-- Security & Password -->
        <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-xs space-y-4">
          <h2 class="text-sm font-bold text-slate-900">Bảo mật tài khoản &amp; Phiên đăng nhập</h2>
          
          <div class="space-y-3 text-xs">
            <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-100">
              <div>
                <span class="font-bold text-slate-800 block">Mật khẩu đăng nhập</span>
                <span class="text-slate-500 text-[11px]">Đã thiết lập bảo mật an toàn theo tiêu chuẩn doanh nghiệp</span>
              </div>
              <button
                type="button"
                class="px-3 py-1.5 rounded bg-blue-600 hover:bg-blue-700 text-white font-semibold cursor-pointer border-none"
                @click="showSecurityNotice"
              >
                Đổi mật khẩu
              </button>
            </div>

            <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-100">
              <div>
                <span class="font-bold text-slate-800 block">Xác thực hai yếu tố (2FA)</span>
                <span class="text-slate-500 text-[11px]">Bảo vệ phiên làm việc khi đăng nhập từ thiết bị mới</span>
              </div>
              <span class="text-emerald-600 font-bold">Đã kích hoạt</span>
            </div>
          </div>
        </div>

      </section>

    </main>

  </div>
</template>
