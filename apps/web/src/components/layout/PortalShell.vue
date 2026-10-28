<script setup lang="ts">
import { canOpenAdmin, type AuthUser } from '../../api/auth'
import { portalCategory } from './portalState'

withDefaults(defineProps<{ user: AuthUser; activeCategory?: string; profilePage?: boolean }>(), { activeCategory: 'ALL', profilePage: false })
const emit = defineEmits<{ (e: 'logout'): void; (e: 'select-app', appId: string): void; (e: 'category-change', category: string): void }>()
const categories = [
  { id: 'ALL', label: 'TẤT CẢ ỨNG DỤNG' },
  { id: 'WORK+', label: 'WORK+' },
  { id: 'HRM+', label: 'HRM+' },
  { id: 'INFO+', label: 'INFO+' },
  { id: 'AI PLATFORM', label: 'AI PLATFORM' },
]
function chooseCategory(category: string, profilePage: boolean) {
  portalCategory.value = category
  emit('category-change', category)
  if (profilePage) emit('select-app', 'welcome')
}
</script>

<template>
  <div class="portal-shell min-h-screen flex flex-col p-4 text-white antialiased selection:bg-teal-500 selection:text-white sm:p-8">
    <header class="mx-auto flex w-full max-w-[1440px] items-center justify-between py-2">
      <button type="button" class="flex items-center gap-3" title="Về cổng ứng dụng" @click="emit('select-app', 'welcome')"><span class="flex h-8 w-8 items-center justify-center rounded-lg border border-white/20 bg-white/10"><span class="material-symbols-outlined text-lg">grid_view</span></span><span class="text-base font-extrabold uppercase tracking-wider">BASE.VN</span></button>
      <div class="flex items-center gap-2 sm:gap-4"><button type="button" class="flex items-center gap-2 rounded-lg px-1 py-1 text-left text-white hover:text-emerald-200" title="Xem hồ sơ" @click="emit('select-app', 'profile')"><img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="Avatar" class="h-8 w-8 rounded-full object-cover"><span class="hidden max-w-32 truncate text-sm font-semibold sm:block">{{ user.display_name }}</span></button><span class="hidden h-5 w-px bg-white/20 sm:block"></span><button type="button" class="flex items-center gap-1.5 px-1 py-1 text-xs font-semibold text-white/75 hover:text-white" title="Đăng xuất khỏi hệ thống" @click="emit('logout')"><span class="material-symbols-outlined text-lg">logout</span><span class="hidden sm:inline">Đăng xuất</span></button></div>
    </header>
    <main class="mx-auto grid w-full max-w-[1440px] flex-1 grid-cols-1 items-start gap-8 py-8 lg:grid-cols-12">
      <nav class="space-y-2 text-xs font-bold uppercase tracking-wider text-white/60 lg:col-span-2 lg:pt-[clamp(96px,20vh,220px)]" aria-label="Ứng dụng trên cổng"><button v-for="category in categories" :key="category.id" type="button" class="flex w-full items-center rounded-r py-1.5 pl-2 text-left transition" :class="activeCategory === category.id ? 'border-l-2 border-emerald-400 bg-white/5 font-extrabold text-emerald-400' : 'hover:text-white'" @click="chooseCategory(category.id, profilePage)">{{ category.label }}</button><div v-if="canOpenAdmin(user)" class="border-t border-white/10 pt-4"><button type="button" class="flex w-full items-center gap-2 py-1 pl-2 text-left text-[11px] font-bold text-amber-300 hover:text-amber-200" @click="emit('select-app', 'admin')"><span class="material-symbols-outlined text-sm">admin_panel_settings</span> Phân hệ Quản trị</button></div></nav>
      <section class="min-w-0 self-stretch lg:col-span-10" :class="profilePage ? '' : 'flex items-center'"><slot /></section>
    </main>
    <slot name="footer" />
  </div>
</template>

<style scoped>
.portal-shell { background: linear-gradient(135deg, #114B43 0%, #15384D 50%, #0F2D42 100%); }
</style>
