<script setup lang="ts">
import { ref } from 'vue'
import { ApiError, authApi, type AuthUser } from '../api/auth'

const props = defineProps<{
  notice?: string
}>()

const emit = defineEmits<{
  (e: 'login-success', payload: { token: string; user: AuthUser; remember: boolean }): void
}>()

const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')

function fillAccount(accEmail: string, accPass: string) {
  email.value = accEmail
  password.value = accPass
  errorMessage.value = ''
}

async function handleLogin() {
  errorMessage.value = ''
  if (!email.value.trim() || !password.value.trim()) {
    errorMessage.value = 'Vui lòng nhập đầy đủ email và mật khẩu.'
    return
  }

  loading.value = true
  try {
    const res = await authApi.login(email.value.trim().toLowerCase(), password.value)
    emit('login-success', {
      token: res.access_token,
      user: res.user,
      remember: rememberMe.value
    })
  } catch (err: unknown) {
    if (err instanceof ApiError) {
      errorMessage.value = err.message
    } else {
      errorMessage.value = 'Không thể kết nối tới máy chủ xác thực.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="h-full min-h-screen text-slate-800 antialiased bg-[#F8FAFC] flex flex-col justify-between overflow-x-hidden font-sans">
    
    <!-- MAIN FULL-VIEWPORT SPLIT LAYOUT (02-login.html) -->
    <main class="flex-1 w-full flex flex-col lg:flex-row min-h-screen">
      
      <!-- LEFT COLUMN: BRAND HERO & WORKSPACE BACKGROUND -->
      <section class="w-full lg:w-[52%] xl:w-[55%] min-h-[280px] sm:min-h-[350px] lg:min-h-screen bg-workspace relative flex items-center justify-center p-8 sm:p-12 lg:p-16 select-none">
        <!-- Content Container Centered -->
        <div class="relative z-10 text-center max-w-xl mx-auto flex flex-col items-center">
          <!-- Brand Name -->
          <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-white drop-shadow-[0_4px_12px_rgba(0,0,0,0.4)]">
            OpenHello
          </h1>

          <!-- Brand Subtitle -->
          <p class="mt-3.5 sm:mt-4 text-sm sm:text-base lg:text-lg text-white/95 font-medium tracking-wide drop-shadow-[0_2px_8px_rgba(0,0,0,0.5)] max-w-md">
            Hệ thống quản trị nhân sự &amp; dự án chuyên nghiệp
          </p>

          <!-- Enterprise Tag -->
          <div class="mt-4 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-black/30 backdrop-blur-md border border-white/15 text-white/80 text-xs font-medium">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Hệ sinh thái Tri thức &amp; Mạng xã hội Doanh nghiệp</span>
          </div>
        </div>
      </section>

      <!-- RIGHT COLUMN: CLEAN FORM CARD -->
      <section class="w-full lg:w-[48%] xl:w-[45%] flex items-center justify-center p-6 sm:p-10 lg:p-14 bg-[#F8FAFC]">
        
        <!-- LOGIN CARD -->
        <div class="w-full max-w-[430px] bg-white rounded-2xl border border-slate-200/80 shadow-[0_12px_40px_-8px_rgba(0,0,0,0.07)] p-8 sm:p-10 transition-all">
          
          <!-- Title -->
          <h2 class="text-2xl font-bold text-slate-800 text-center mb-7 tracking-tight">
            Chào mừng quay lại
          </h2>

          <!-- Quick Account Switcher Bar -->
          <div class="mb-5 p-2.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between text-xs">
            <div class="flex items-center gap-1.5 text-slate-500 font-medium">
              <span class="material-symbols-outlined text-sm text-amber-500">account_circle</span>
              <span>Tài khoản mẫu:</span>
            </div>
            <div class="flex items-center gap-1.5 flex-wrap">
              <button
                type="button"
                class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 font-semibold rounded-lg border border-slate-200 text-[11px] transition cursor-pointer"
                @click="fillAccount('qa_expert@gmail.com', '12345678')"
              >
                Chuyên gia QA
              </button>
              <button
                type="button"
                class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 font-semibold rounded-lg border border-slate-200 text-[11px] transition cursor-pointer"
                @click="fillAccount('admin@gmail.com', '12345678')"
              >
                Admin
              </button>
              <button
                type="button"
                class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 font-semibold rounded-lg border border-slate-200 text-[11px] transition cursor-pointer"
                @click="fillAccount('member@gmail.com', '12345678')"
              >
                Member
              </button>
            </div>
          </div>

          <!-- Alert Notice from props -->
          <div v-if="props.notice" class="mb-4 p-3 rounded-lg bg-blue-50 border border-blue-200 text-xs text-blue-700 font-medium">
            {{ props.notice }}
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium">
            {{ errorMessage }}
          </div>

          <!-- FORM -->
          <form class="space-y-4" @submit.prevent="handleLogin">
            
            <!-- Email / Username Field -->
            <div>
              <label for="login-email" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                Tên đăng nhập hoặc Email
              </label>
              <input
                id="login-email"
                v-model="email"
                type="text"
                name="email"
                required
                class="w-full px-4 py-3 bg-[#EBF3FE] hover:bg-[#e4effd] focus:bg-white text-slate-800 text-sm font-medium rounded-xl border border-transparent focus:border-[#F5A623] focus:ring-3 focus:ring-amber-500/20 outline-none transition duration-150 placeholder:text-slate-400"
                placeholder="Nhập email hoặc tên tài khoản"
              >
            </div>

            <!-- Password Field -->
            <div>
              <label for="login-password" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                Mật khẩu
              </label>
              <div class="relative">
                <input
                  id="login-password"
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  name="password"
                  required
                  class="w-full px-4 py-3 bg-[#EBF3FE] hover:bg-[#e4effd] focus:bg-white text-slate-800 text-sm font-medium rounded-xl border border-transparent focus:border-[#F5A623] focus:ring-3 focus:ring-amber-500/20 outline-none transition duration-150 pr-11 placeholder:text-slate-400"
                  placeholder="Nhập mật khẩu"
                >
                <button
                  type="button"
                  class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition flex items-center justify-center p-1 cursor-pointer border-none bg-transparent"
                  title="Hiện/ẩn mật khẩu"
                  @click="showPassword = !showPassword"
                >
                  <span class="material-symbols-outlined text-[19px]">
                    {{ showPassword ? 'visibility' : 'visibility_off' }}
                  </span>
                </button>
              </div>
            </div>

            <!-- Remember Me -->
            <div class="pt-1 flex items-center justify-between select-none">
              <label class="flex items-center gap-2.5 cursor-pointer text-xs font-medium text-slate-600">
                <input
                  v-model="rememberMe"
                  type="checkbox"
                  class="w-4 h-4 rounded text-amber-500 focus:ring-amber-400"
                >
                <span>Ghi nhớ đăng nhập</span>
              </label>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="loading"
              class="w-full mt-2 py-3.5 px-4 bg-[#F5A623] hover:bg-[#E09612] disabled:opacity-60 text-white font-bold text-sm rounded-xl shadow-[0_4px_16px_rgba(245,166,35,0.35)] transition duration-150 cursor-pointer border-none flex items-center justify-center gap-2"
            >
              <span v-if="loading" class="material-symbols-outlined animate-spin text-lg">progress_activity</span>
              <span>{{ loading ? 'Đang xác thực...' : 'Đăng nhập' }}</span>
            </button>

          </form>

        </div>
      </section>

    </main>

  </div>
</template>

<style scoped>
.bg-workspace {
  background-image: 
    radial-gradient(circle at 10% 20%, rgba(245, 166, 35, 0.48) 0%, rgba(217, 119, 6, 0.22) 35%, transparent 70%),
    linear-gradient(to right, rgba(15, 23, 42, 0.42) 0%, rgba(15, 23, 42, 0.68) 100%),
    url('/assets/hachinet_login_bg.jpg');
  background-size: cover;
  background-position: center left;
  background-repeat: no-repeat;
}
</style>
