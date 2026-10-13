<script setup lang="ts">
import { computed, ref } from 'vue'
import { ApiError, authApi, type AuthUser } from '../api/auth'

const props = defineProps<{ token: string; user: AuthUser }>()
const emit = defineEmits<{ (event: 'completed'): void; (event: 'logout'): void }>()
const currentPassword = ref('')
const password = ref('')
const confirmation = ref('')
const showPasswords = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const requirements = computed(() => ({
  length: password.value.length >= 12,
  upper: /[A-Z]/.test(password.value),
  lower: /[a-z]/.test(password.value),
  number: /\d/.test(password.value),
  symbol: /[^A-Za-z0-9]/.test(password.value),
  match: password.value.length > 0 && password.value === confirmation.value,
}))
const isValid = computed(() => Object.values(requirements.value).every(Boolean))

async function submit(): Promise<void> {
  errorMessage.value = ''
  if (!isValid.value) {
    errorMessage.value = 'Mật khẩu mới chưa đáp ứng đầy đủ yêu cầu.'
    return
  }
  isSubmitting.value = true
  try {
    await authApi.changePassword(props.token, currentPassword.value, password.value, confirmation.value)
    emit('completed')
  } catch (error) {
    errorMessage.value = error instanceof ApiError ? error.message : 'Không thể đổi mật khẩu.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <main class="min-h-screen grid place-items-center bg-slate-50 px-5 py-10 text-slate-800">
    <section class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-7 shadow-xl shadow-slate-200/50 sm:p-10">
      <div class="mb-7 flex items-start justify-between gap-4">
        <div>
          <p class="mb-2 text-xs font-bold uppercase tracking-[0.2em] text-amber-600">Bảo mật tài khoản</p>
          <h1 class="text-2xl font-extrabold tracking-tight">Đổi mật khẩu lần đầu</h1>
          <p class="mt-2 text-sm leading-6 text-slate-500">Xin chào {{ user.display_name }}. Bạn cần đặt mật khẩu mới trước khi vào hệ thống.</p>
        </div>
        <span class="material-symbols-outlined rounded-2xl bg-amber-50 p-3 text-amber-600">key</span>
      </div>
      <form class="space-y-4" @submit.prevent="submit">
        <label class="block"><span class="mb-1.5 block text-sm font-semibold">Mật khẩu hiện tại</span>
          <input v-model="currentPassword" :type="showPasswords ? 'text' : 'password'" autocomplete="current-password" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-amber-500 focus:bg-white focus:ring-3 focus:ring-amber-500/15"></label>
        <label class="block"><span class="mb-1.5 block text-sm font-semibold">Mật khẩu mới</span>
          <input v-model="password" :type="showPasswords ? 'text' : 'password'" autocomplete="new-password" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-amber-500 focus:bg-white focus:ring-3 focus:ring-amber-500/15"></label>
        <label class="block"><span class="mb-1.5 block text-sm font-semibold">Nhập lại mật khẩu mới</span>
          <input v-model="confirmation" :type="showPasswords ? 'text' : 'password'" autocomplete="new-password" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-amber-500 focus:bg-white focus:ring-3 focus:ring-amber-500/15"></label>
        <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-600"><input v-model="showPasswords" type="checkbox" class="accent-amber-500"> Hiện mật khẩu</label>
        <ul class="grid grid-cols-1 gap-1.5 rounded-xl bg-slate-50 p-4 text-xs text-slate-500 sm:grid-cols-2">
          <li :class="requirements.length && 'text-emerald-600'">✓ Ít nhất 12 ký tự</li><li :class="requirements.upper && 'text-emerald-600'">✓ Có chữ hoa</li>
          <li :class="requirements.lower && 'text-emerald-600'">✓ Có chữ thường</li><li :class="requirements.number && 'text-emerald-600'">✓ Có chữ số</li>
          <li :class="requirements.symbol && 'text-emerald-600'">✓ Có ký hiệu</li><li :class="requirements.match && 'text-emerald-600'">✓ Hai mật khẩu trùng nhau</li>
        </ul>
        <p v-if="errorMessage" role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-medium text-rose-700">{{ errorMessage }}</p>
        <button type="submit" :disabled="isSubmitting || !isValid" class="w-full rounded-xl bg-amber-500 py-3.5 text-sm font-bold text-white shadow-lg shadow-amber-500/20 hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50">{{ isSubmitting ? 'Đang cập nhật...' : 'ĐỔI MẬT KHẨU' }}</button>
        <button type="button" class="w-full py-2 text-sm font-semibold text-slate-500 hover:text-slate-800" @click="emit('logout')">Đăng xuất và quay lại sau</button>
      </form>
    </section>
  </main>
</template>
