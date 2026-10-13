<script setup lang="ts">
import { ref, computed } from 'vue'
import type { AuthUser } from '../../api/auth'
import { MOCK_SEARCH_RESULTS } from '../../mock/mockData.js'

const props = defineProps<{
  user: AuthUser
}>()

const emit = defineEmits<{
  (e: 'back'): void
  (e: 'select-app', appId: string): void
  (e: 'logout'): void
}>()

const query = ref('Knowledge Graph và Microservices')
const activeFilter = ref('ALL')
const searchResults = ref([...MOCK_SEARCH_RESULTS])

const filteredResults = computed(() => {
  if (activeFilter.value === 'ALL') return searchResults.value
  return searchResults.value.filter((r) => r.type.includes(activeFilter.value))
})

function setQuery(text: string) {
  query.value = text
}
</script>

<template>
  <div class="min-h-screen text-slate-900 antialiased flex flex-col justify-between bg-[#F8FAFC] font-sans">
    
    <!-- TOP BLUE NAVBAR (MATCH 15-hybrid-search.html) -->
    <header class="sticky top-0 z-40 bg-[#1D4ED8] text-white shadow-xs">
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
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'square')">Trang chủ</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'square')">Câu hỏi</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'knowledge')">Tri thức</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'library')">Thư viện</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'reward')">Bảng xếp hạng</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'profile')">Cá nhân</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'admin')">Tùy chỉnh</button>
            <button type="button" class="px-3 py-4 text-white font-bold border-b-2 border-white bg-transparent cursor-pointer border-t-0 border-l-0 border-r-0">Tìm kiếm</button>
          </nav>
        </div>

        <!-- Right User -->
        <div class="flex items-center gap-3 flex-shrink-0">
          <button
            type="button"
            class="p-0.5 rounded-full hover:ring-2 hover:ring-white border-none bg-transparent cursor-pointer"
            title="Hồ sơ cá nhân"
            @click="emit('select-app', 'profile')"
          >
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80" alt="Avatar" class="w-7 h-7 rounded-full object-cover">
          </button>
          <button
            type="button"
            class="p-1 text-white/80 hover:text-white cursor-pointer border-none bg-transparent"
            title="Đăng xuất"
            @click="emit('logout')"
          >
            <span class="material-symbols-outlined text-base">logout</span>
          </button>
        </div>
      </div>
    </header>

    <!-- SEARCH HERO SECTION -->
    <section class="bg-white border-b border-slate-200 py-8 px-4 lg:px-8">
      <div class="max-w-3xl mx-auto text-center space-y-3">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
          Tìm kiếm Tri thức &amp; Chuyên gia nội bộ
        </h1>
        <p class="text-sm text-slate-500 max-w-lg mx-auto">
          Tích hợp Vector Embeddings + Full-Text Indexing + Liên kết quan hệ Neo4j
        </p>

        <!-- Search Box Input -->
        <div class="relative max-w-2xl mx-auto pt-2">
          <span class="material-symbols-outlined absolute left-3.5 top-5 text-slate-400 text-lg">search</span>
          <input
            v-model="query"
            type="text"
            class="w-full pl-10 pr-28 py-3 bg-slate-50 focus:bg-white text-[15px] font-medium rounded-lg border border-slate-200 focus:border-blue-600 outline-none transition shadow-xs"
            placeholder="Nhập nội dung cần tra cứu..."
          >
          <button
            type="button"
            class="absolute right-1.5 top-3.5 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-md shadow-xs transition cursor-pointer border-none"
          >
            Tìm kiếm
          </button>
        </div>

        <!-- Suggestions -->
        <div class="flex flex-wrap justify-center items-center gap-2 text-xs text-slate-500 pt-1">
          <span>Gợi ý:</span>
          <button
            type="button"
            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-medium cursor-pointer border-none"
            @click="setQuery('Knowledge Graph Neo4j')"
          >
            Knowledge Graph Neo4j
          </button>
          <button
            type="button"
            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-medium cursor-pointer border-none"
            @click="setQuery('Tối ưu hóa Eloquent Laravel')"
          >
            Eloquent Laravel
          </button>
          <button
            type="button"
            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-medium cursor-pointer border-none"
            @click="setQuery('Tìm chuyên gia AI')"
          >
            Chuyên gia AI
          </button>
        </div>
      </div>
    </section>

    <!-- SEARCH RESULTS SECTION -->
    <main class="max-w-4xl mx-auto px-4 lg:px-8 py-6 flex-1 w-full space-y-4">
      
      <!-- Filter Chips -->
      <div class="flex items-center gap-2 border-b border-slate-200 pb-3 text-xs font-semibold">
        <button
          type="button"
          class="px-3 py-1.5 rounded-full cursor-pointer border-none transition"
          :class="activeFilter === 'ALL' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          @click="activeFilter = 'ALL'"
        >
          Tất cả kết quả ({{ searchResults.length }})
        </button>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full cursor-pointer border-none transition"
          :class="activeFilter === 'Tài liệu' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          @click="activeFilter = 'Tài liệu'"
        >
          Tài liệu PDF
        </button>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full cursor-pointer border-none transition"
          :class="activeFilter === 'Hỏi đáp' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          @click="activeFilter = 'Hỏi đáp'"
        >
          Câu hỏi Q&amp;A
        </button>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full cursor-pointer border-none transition"
          :class="activeFilter === 'Chuyên gia' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          @click="activeFilter = 'Chuyên gia'"
        >
          Chuyên gia phụ trách
        </button>
      </div>

      <!-- Results List -->
      <div class="space-y-4">
        <article
          v-for="item in filteredResults"
          :key="item.id"
          class="bg-white rounded-lg border border-slate-200 p-5 shadow-xs space-y-2 hover:border-blue-400 transition"
        >
          <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="item.typeColor">
                {{ item.type }}
              </span>
              <span class="text-xs text-slate-400">• Tác giả / Đơn vị: {{ item.author }}</span>
            </div>
            
            <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-bold">
              {{ item.matchScore }}
            </span>
          </div>

          <h2 class="text-sm font-bold text-slate-900 leading-snug hover:text-blue-600 cursor-pointer">
            {{ item.title }}
          </h2>

          <p class="text-xs text-slate-600 leading-relaxed">
            {{ item.snippet }}
          </p>

          <div class="pt-2 flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-50">
            <span class="italic text-slate-400">{{ item.relation }}</span>
            <button
              type="button"
              class="font-semibold text-blue-600 hover:underline border-none bg-transparent cursor-pointer"
              @click="emit('select-app', item.type.includes('Chuyên gia') ? 'profile' : item.type.includes('Tài liệu') ? 'library' : 'square')"
            >
              Mở chi tiết →
            </button>
          </div>
        </article>
      </div>

    </main>

  </div>
</template>
