<script setup lang="ts">
defineProps<{ active: 'library' | 'questions' | 'courses' }>()
const emit = defineEmits<{
  (event: 'back'): void
  (event: 'navigate', value: 'library' | 'questions' | 'courses'): void
  (event: 'refresh'): void
  (event: 'ask'): void
  (event: 'select-app', appId: string): void
}>()
</script>

<template>
  <header class="sticky top-0 z-40 bg-[#1D4ED8] text-white shadow-xs">
    <!-- TOP BLUE NAVBAR (MATCH 09-qa-list.html) -->
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
          <button
            type="button"
            class="px-3 py-4 border-none bg-transparent cursor-pointer"
            :class="active === 'questions' ? 'text-white font-bold border-b-2 border-white' : 'text-white/80 hover:text-white'"
            @click="emit('navigate', 'questions')"
          >
            Hỏi đáp Q&amp;A
          </button>
          
          <button
            type="button"
            class="px-3 py-4 border-none bg-transparent cursor-pointer"
            :class="active === 'library' ? 'text-white font-bold border-b-2 border-white' : 'text-white/80 hover:text-white'"
            @click="emit('navigate', 'library')"
          >
            Thư viện tri thức
          </button>

          <button
            type="button"
            class="px-3 py-4 border-none bg-transparent cursor-pointer"
            :class="active === 'courses' ? 'text-white font-bold border-b-2 border-white' : 'text-white/80 hover:text-white'"
            @click="emit('navigate', 'courses')"
          >
            Khóa học
          </button>
        </nav>
      </div>

      <!-- Right Actions: Refresh & Ask Question -->
      <div class="flex items-center gap-3 flex-shrink-0">
        <button
          type="button"
          class="px-2.5 py-1.5 rounded bg-blue-800/80 hover:bg-blue-700 text-white text-xs font-semibold cursor-pointer border-none transition"
          title="Làm mới dữ liệu từ backend"
          @click="emit('refresh')"
        >
          ↻ Làm mới
        </button>

        <button
          type="button"
          class="px-3.5 py-1.5 rounded bg-white hover:bg-blue-50 text-blue-800 text-xs font-bold shadow-xs cursor-pointer border-none transition"
          @click="emit('ask')"
        >
          + Đặt câu hỏi
        </button>

        <button
          type="button"
          class="flex items-center gap-1 text-xs text-white/80 hover:text-white border-none bg-transparent cursor-pointer pl-2 border-l border-blue-500/40"
          title="Về cổng Portal"
          @click="emit('back')"
        >
          <span class="material-symbols-outlined text-sm">arrow_back</span>
          <span class="hidden sm:inline">Portal</span>
        </button>
      </div>
    </div>
  </header>
</template>
