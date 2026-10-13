<script setup lang="ts">
import { ref, computed } from 'vue'
import type { AuthUser } from '../../api/auth'
import { MOCK_LIBRARY_DOCUMENTS } from '../../mock/mockData.js'

const props = defineProps<{
  user: AuthUser
}>()

const emit = defineEmits<{
  (e: 'back'): void
  (e: 'select-app', appId: string): void
  (e: 'logout'): void
}>()

const documents = ref([...MOCK_LIBRARY_DOCUMENTS])
const selectedCategory = ref('ALL')
const searchQuery = ref('')

const filteredDocuments = computed(() => {
  return documents.value.filter((doc: any) => {
    const matchCat = selectedCategory.value === 'ALL' || doc.category.includes(selectedCategory.value)
    const matchSearch = !searchQuery.value || doc.title.toLowerCase().includes(searchQuery.value.toLowerCase())
    return matchCat && matchSearch
  })
})

function handleDownload(title: string) {
  window.alert(`Đang tải xuống: ${title}\n(Tài liệu lưu trữ an toàn trong kho lưu trữ Base Square).`)
}

function handleUpload() {
  window.alert('Mở hộp thoại tải lên tài liệu mới (PDF, DOCX, XLSX) vào kho lưu trữ tri thức!')
}
</script>

<template>
  <div class="min-h-screen text-slate-900 antialiased flex flex-col justify-between bg-[#F8FAFC] font-sans">
    
    <!-- TOP BLUE NAVBAR (MATCH 08-learning-home.html) -->
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
            <button type="button" class="px-3 py-4 text-white font-bold border-b-2 border-white bg-transparent cursor-pointer border-t-0 border-l-0 border-r-0">Thư viện</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'reward')">Bảng xếp hạng</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'profile')">Cá nhân</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'admin')">Tùy chỉnh</button>
            <button type="button" class="px-3 py-4 hover:text-white border-none bg-transparent cursor-pointer text-white/80" @click="emit('select-app', 'search')">Tìm kiếm</button>
          </nav>
        </div>

        <!-- Right Search & User Profile -->
        <div class="flex items-center gap-3 flex-shrink-0">
          <div class="relative hidden sm:block">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Enter để tìm kiếm"
              class="w-56 pl-3 pr-8 py-1.5 bg-blue-800/60 focus:bg-white text-white focus:text-slate-900 placeholder-white/60 text-xs rounded-full border border-blue-400/40 focus:border-white outline-none transition"
            >
            <span class="material-symbols-outlined absolute right-2.5 top-2 text-white/60 text-base">search</span>
          </div>

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

    <!-- MAIN LIBRARY 2-COLUMN WORKSPACE -->
    <main class="max-w-[1440px] mx-auto px-4 lg:px-8 py-6 flex-1 w-full grid grid-cols-1 md:grid-cols-12 gap-6">
      
      <!-- LEFT SIDEBAR: UPLOAD & CATEGORY TREE -->
      <aside class="md:col-span-3 space-y-4">
        <!-- Upload Button -->
        <button
          type="button"
          class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider text-center block rounded-md shadow-xs transition cursor-pointer border-none"
          @click="handleUpload"
        >
          TẢI LÊN TÀI LIỆU
        </button>

        <!-- Categories Tree -->
        <div class="bg-white rounded-lg border border-slate-200 p-3 shadow-xs space-y-1 text-xs">
          <span class="font-bold text-slate-400 uppercase text-[11px] tracking-wider px-2 block mb-2">CHỦ ĐỀ TÀI LIỆU</span>
          
          <button
            type="button"
            class="w-full flex items-center justify-between p-2 rounded transition cursor-pointer border-none text-left"
            :class="selectedCategory === 'ALL' ? 'bg-blue-50 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="selectedCategory = 'ALL'"
          >
            <span>Tất cả tài liệu</span>
            <span class="text-slate-400 font-mono">{{ documents.length }}</span>
          </button>

          <button
            type="button"
            class="w-full flex items-center justify-between p-2 rounded transition cursor-pointer border-none text-left"
            :class="selectedCategory === 'Backend' ? 'bg-blue-50 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="selectedCategory = 'Backend'"
          >
            <span>Backend &amp; AI</span>
            <span class="text-slate-400 font-mono">1</span>
          </button>

          <button
            type="button"
            class="w-full flex items-center justify-between p-2 rounded transition cursor-pointer border-none text-left"
            :class="selectedCategory === 'Kỹ thuật' ? 'bg-blue-50 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="selectedCategory = 'Kỹ thuật'"
          >
            <span>Kỹ thuật Phần mềm</span>
            <span class="text-slate-400 font-mono">1</span>
          </button>

          <button
            type="button"
            class="w-full flex items-center justify-between p-2 rounded transition cursor-pointer border-none text-left"
            :class="selectedCategory === 'HR' ? 'bg-blue-50 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="selectedCategory = 'HR'"
          >
            <span>HR &amp; Văn hóa</span>
            <span class="text-slate-400 font-mono">1</span>
          </button>

          <button
            type="button"
            class="w-full flex items-center justify-between p-2 rounded transition cursor-pointer border-none text-left"
            :class="selectedCategory === 'DevOps' ? 'bg-blue-50 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="selectedCategory = 'DevOps'"
          >
            <span>DevOps &amp; Hạ tầng</span>
            <span class="text-slate-400 font-mono">1</span>
          </button>
        </div>
      </aside>

      <!-- RIGHT: DOCUMENTS GRID -->
      <section class="md:col-span-9 space-y-4">
        
        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
          <div>
            <h1 class="text-base font-bold text-slate-900 leading-tight">Thư viện tài liệu học tập</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kho văn bản đã xuất bản và lập chỉ mục vào Cây tri thức AI</p>
          </div>
          <span class="text-xs text-slate-500">{{ filteredDocuments.length }} tài liệu</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          <div
            v-for="doc in filteredDocuments"
            :key="doc.id"
            class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-3 flex flex-col justify-between hover:border-blue-400 transition"
          >
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <span
                  class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                  :class="doc.type === 'PDF' ? 'bg-rose-100 text-rose-700' : doc.type === 'DOCX' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'"
                >
                  {{ doc.type }} • {{ doc.size }}
                </span>
                
                <span
                  v-if="doc.isIndexed"
                  class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[10px] font-semibold flex items-center gap-1"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                  Đã chỉ mục AI
                </span>
              </div>

              <h2 class="font-bold text-slate-900 text-sm leading-snug">
                {{ doc.title }}
              </h2>
              
              <div class="text-[11px] text-slate-500">
                Tác giả: <span class="font-semibold text-slate-700">{{ doc.author }}</span> • Ngày: {{ doc.date }}
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-slate-500">
              <div class="flex items-center gap-3 text-[11px]">
                <span>👁️ {{ doc.views }}</span>
                <span>⬇️ {{ doc.downloads }}</span>
              </div>
              <button
                type="button"
                class="px-3 py-1.5 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold cursor-pointer border-none"
                @click="handleDownload(doc.title)"
              >
                Tải xuống
              </button>
            </div>
          </div>
        </div>

      </section>

    </main>

  </div>
</template>
