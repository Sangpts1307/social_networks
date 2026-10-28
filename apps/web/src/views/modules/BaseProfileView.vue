<script setup lang="ts">
import { onMounted, ref } from 'vue'
import type { AuthUser } from '../../api/auth'
import { authApi } from '../../api/auth'
import { profileApi, type AccountProfile } from '../../api/profile'
import PortalShell from '../../components/layout/PortalShell.vue'

const props = defineProps<{ user: AuthUser; token: string }>()
const emit = defineEmits<{ (e: 'back'): void; (e: 'select-app', appId: string): void; (e: 'logout'): void }>()
const profile = ref<AccountProfile | null>(null)
const loading = ref(true)
const saving = ref(false)
const activeTab = ref<'personal' | 'password'>('personal')
const error = ref('')
const notice = ref('')
const bio = ref('')
const phone = ref('')
const location = ref('')
const skills = ref('')
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=240&auto=format&fit=crop&q=85'

function copyFields(data: AccountProfile) {
  bio.value = data.bio ?? ''
  phone.value = data.phone ?? ''
  location.value = data.location ?? ''
  skills.value = (data.skills ?? []).join(', ')
}
async function load() {
  loading.value = true
  error.value = ''
  try {
    profile.value = await profileApi.me(props.token)
    copyFields(profile.value)
  } catch (cause) {
    error.value = cause instanceof Error ? cause.message : 'Không tải được hồ sơ.'
  } finally { loading.value = false }
}
async function save() {
  saving.value = true
  error.value = ''
  notice.value = ''
  try {
    profile.value = await profileApi.update(props.token, {
      bio: bio.value.trim() || null,
      phone: phone.value.trim() || null,
      location: location.value.trim() || null,
      skills: skills.value.split(',').map(value => value.trim()).filter(Boolean),
    })
    copyFields(profile.value)
    notice.value = 'Đã lưu thông tin cá nhân.'
  } catch (cause) {
    error.value = cause instanceof Error ? cause.message : 'Không lưu được hồ sơ.'
  } finally { saving.value = false }
}
async function changePassword() {
  if (newPassword.value !== confirmPassword.value) { error.value = 'Mật khẩu nhập lại không khớp.'; return }
  saving.value = true
  error.value = ''
  notice.value = ''
  try {
    await authApi.changePassword(props.token, currentPassword.value, newPassword.value, confirmPassword.value)
    currentPassword.value = newPassword.value = confirmPassword.value = ''
    notice.value = 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại.'
    emit('logout')
  } catch (cause) {
    error.value = cause instanceof Error ? cause.message : 'Không đổi được mật khẩu.'
  } finally { saving.value = false }
}
onMounted(load)
</script>

<template>
  <PortalShell :user="user" profile-page @logout="emit('logout')" @select-app="appId => emit('select-app', appId)">
    <nav class="mb-5 text-sm text-white/70" aria-label="Đường dẫn"><button class="hover:text-white" @click="emit('back')">Trang chủ</button><span class="mx-2">/</span><span class="font-semibold text-emerald-300">Thông tin cá nhân</span></nav>
        <section class="overflow-hidden rounded-2xl border border-white/20 bg-white text-slate-900">
          <div class="relative h-36 overflow-hidden bg-gradient-to-r from-[#155953] via-[#187772] to-[#279992] sm:h-40">
            <div class="absolute inset-0 opacity-10" style="background-image:linear-gradient(#ffffff 1px, transparent 1px),linear-gradient(90deg,#ffffff 1px,transparent 1px);background-size:42px 42px"></div>
            <div class="relative p-6 text-white sm:p-8"><h1 class="text-2xl font-extrabold sm:text-3xl">Thông tin tài khoản</h1><p class="mt-2 text-sm font-medium text-white/90">Bạn có thể chỉnh sửa thông tin cá nhân tại đây</p></div>
            <div class="absolute right-8 top-1/2 hidden -translate-y-1/2 items-center gap-4 text-white/80 md:flex" aria-label="Biểu trưng BASE.VN">
              <svg class="h-20 w-20" viewBox="0 0 88 88" fill="none" aria-hidden="true"><rect x="2" y="2" width="84" height="84" rx="22" stroke="currentColor" stroke-width="2" opacity=".55"/><rect x="17" y="17" width="18" height="18" rx="4" fill="currentColor"/><rect x="43" y="17" width="18" height="18" rx="4" fill="currentColor" opacity=".7"/><rect x="17" y="43" width="18" height="18" rx="4" fill="currentColor" opacity=".7"/><rect x="43" y="43" width="18" height="18" rx="4" fill="currentColor"/><path d="M70 28v32M54 70h16" stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity=".8"/></svg>
              <span class="text-lg font-extrabold tracking-[.18em]">BASE.VN</span>
            </div>
            <div class="absolute -bottom-16 right-0 h-48 w-64 rounded-full bg-white/15 blur-2xl"></div>
          </div>
          <div class="relative px-5 pb-9 sm:px-8">
            <img :src="avatar" alt="Ảnh đại diện" class="-mt-14 h-28 w-28 rounded-full border-[5px] border-white bg-white object-cover shadow-lg sm:h-32 sm:w-32">
            <div class="mt-3"><h2 class="text-xl font-bold text-slate-900">{{ profile?.display_name || user.display_name }}</h2><p class="mt-1 text-sm text-slate-500">{{ profile?.job_title || 'Chưa cập nhật chức vụ' }}</p></div>
            <div class="mt-7 flex gap-7 border-b border-slate-200 text-sm font-semibold">
              <button class="pb-4" :class="activeTab === 'personal' ? 'border-b-[3px] border-teal-500 text-teal-700' : 'text-slate-400 hover:text-slate-700'" @click="activeTab = 'personal'; error = ''; notice = ''">Thông tin cá nhân</button>
              <button class="pb-4" :class="activeTab === 'password' ? 'border-b-[3px] border-teal-500 text-teal-700' : 'text-slate-400 hover:text-slate-700'" @click="activeTab = 'password'; error = ''; notice = ''">Thay đổi mật khẩu</button>
            </div>
            <p v-if="error" role="alert" class="mt-5 rounded-xl bg-rose-50 p-3 text-sm text-rose-700">{{ error }} <button v-if="!profile" class="underline" @click="load">Thử lại</button></p>
            <p v-if="notice" role="status" class="mt-5 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-700">{{ notice }}</p>
            <p v-if="loading" class="mt-6 text-sm text-slate-500">Đang tải hồ sơ...</p>
            <form v-if="activeTab === 'personal' && profile" class="mt-7" @submit.prevent="save">
              <div class="grid gap-5 md:grid-cols-2">
                <label class="block text-sm font-semibold">Tên nhân viên<input :value="profile.display_name" readonly class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-normal text-slate-600" title="Tên do quản trị viên quản lý"></label>
                <label class="block text-sm font-semibold">Email<input :value="profile.email || user.email" readonly class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-normal text-slate-600" title="Email do quản trị viên quản lý"></label>
                <label class="block text-sm font-semibold">Chức vụ<input :value="profile.job_title || 'Chưa cập nhật'" readonly class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-normal text-slate-600" title="Chức vụ do quản trị viên quản lý"></label>
                <label class="block text-sm font-semibold">Mã nhân viên<input :value="profile.employee_code || 'Chưa cập nhật'" readonly class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-normal text-slate-600" title="Mã nhân viên do quản trị viên quản lý"></label>
                <label class="block text-sm font-semibold">Số điện thoại<input v-model="phone" maxlength="32" type="tel" placeholder="Nhập số điện thoại" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></label>
                <label class="block text-sm font-semibold">Địa chỉ<input v-model="location" maxlength="255" placeholder="Nhập địa chỉ hoặc địa điểm" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></label>
                <label class="block text-sm font-semibold md:col-span-2">Giới thiệu<textarea v-model="bio" maxlength="5000" rows="3" placeholder="Viết vài dòng về bản thân" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></textarea></label>
                <label class="block text-sm font-semibold md:col-span-2">Kỹ năng <span class="font-normal text-slate-400">(cách nhau bằng dấu phẩy)</span><input v-model="skills" placeholder="Ví dụ: Vue, Laravel, Thiết kế" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 font-normal outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></label>
              </div>
              <button :disabled="saving" class="mt-7 rounded-xl bg-teal-500 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-teal-600 disabled:opacity-50">{{ saving ? 'Đang lưu...' : 'Lưu thông tin' }}</button>
            </form>
            <form v-else-if="activeTab === 'password'" class="mt-7 max-w-lg space-y-5" @submit.prevent="changePassword">
              <label class="block text-sm font-semibold">Mật khẩu hiện tại<input v-model="currentPassword" required type="password" autocomplete="current-password" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-teal-500"></label>
              <label class="block text-sm font-semibold">Mật khẩu mới<input v-model="newPassword" required type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-teal-500"></label>
              <label class="block text-sm font-semibold">Nhập lại mật khẩu mới<input v-model="confirmPassword" required type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-teal-500"></label>
              <button :disabled="saving" class="rounded-xl bg-teal-500 px-6 py-3 text-sm font-bold text-white hover:bg-teal-600 disabled:opacity-50">{{ saving ? 'Đang lưu...' : 'Đổi mật khẩu' }}</button>
            </form>
          </div>
        </section>
  </PortalShell>
</template>
