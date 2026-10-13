<script setup lang="ts">
import { ref, computed } from 'vue'
import { store } from '../../mock/store'

const query = ref('Microservices')
const activeFilter = ref<'all' | 'docs' | 'qa' | 'experts'>('all')

const sampleSuggestions = ['Microservices', 'Neo4j', 'Xác thực JWT', 'Bảo mật thông tin', 'Onboarding']

const matchedDocs = computed(() => {
  if (!query.value.trim()) return store.documents
  const q = query.value.toLowerCase()
  return store.documents.filter(d => d.title.toLowerCase().includes(q) || d.summary.toLowerCase().includes(q))
})

const matchedQuestions = computed(() => {
  if (!query.value.trim()) return store.questions
  const q = query.value.toLowerCase()
  return store.questions.filter(item => item.title.toLowerCase().includes(q) || item.content.toLowerCase().includes(q))
})

const matchedExperts = computed(() => {
  const q = query.value.toLowerCase()
  const experts = [
    { name: 'Trần Hoàng Nam', role: 'Tech Lead', department: 'Khối Kỹ thuật', skills: ['Microservices', 'Neo4j', 'Kiến trúc hệ thống'], matchScore: '98%', avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80' },
    { name: 'Võ Thành Luân', role: 'Kỹ sư Hệ thống', department: 'Khối Kỹ thuật', skills: ['API Gateway', 'Vue 3', 'Microservices'], matchScore: '92%', avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80' },
    { name: 'Lê Phương Thảo', role: 'Trưởng ban Nhân sự', department: 'Nhân sự & Văn hóa', skills: ['Quy chế', 'Bảo mật', 'Onboarding'], matchScore: '85%', avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120&auto=format&fit=crop&q=80' }
  ]
  if (!q) return experts
  return experts.filter(e => 
    e.name.toLowerCase().includes(q) || 
    e.skills.some(s => s.toLowerCase().includes(q))
  )
})

function contactExpert(name: string) {
  const conv = store.conversations.find(c => c.user.name.includes(name))
  if (conv) {
    store.activeConversationId = conv.id
    store.setScreen('messenger')
  } else {
    store.setScreen('messenger')
  }
}
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 py-6 space-y-5">
    
    <!-- Search Bar & Sugggestions -->
    <div class="bg-white border border-slate-200 rounded-md p-5 space-y-3">
      <div>
        <div class="flex items-center gap-2">
          <h1 class="text-base font-bold text-slate-900 leading-tight">AI Hybrid Search & Expert Finder</h1>
          <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700">Vector + Graph Search</span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">Tìm kiếm ngữ nghĩa đa chiều kết hợp đồ thị tri thức và tìm chuyên gia nội bộ</p>
      </div>

      <div class="relative">
        <input
          v-model="query"
          type="text"
          placeholder="Nhập từ khóa, câu hỏi kỹ thuật hoặc kỹ năng cần tìm..."
          class="w-full pl-10 pr-4 py-2.5 bg-slate-50 focus:bg-white text-xs sm:text-sm text-slate-900 rounded border border-slate-200 outline-none transition"
        >
        <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-lg">search</span>
      </div>

      <!-- Quick Suggestion Pills -->
      <div class="flex items-center gap-2 flex-wrap text-xs text-slate-500">
        <span class="font-medium">Gợi ý nhanh:</span>
        <button
          v-for="sug in sampleSuggestions"
          :key="sug"
          type="button"
          class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium cursor-pointer border-none"
          @click="query = sug"
        >
          {{ sug }}
        </button>
      </div>
    </div>

    <!-- Filter Buttons -->
    <div class="flex items-center gap-1.5 border-b border-slate-200 pb-2 overflow-x-auto">
      <button
        v-for="f in [
          { key: 'all', label: 'Tất cả kết quả' },
          { key: 'docs', label: `Tài liệu (${matchedDocs.length})` },
          { key: 'qa', label: `Hỏi đáp (${matchedQuestions.length})` },
          { key: 'experts', label: `Chuyên gia (${matchedExperts.length})` }
        ]"
        :key="f.key"
        type="button"
        class="px-3 py-1.5 rounded text-xs font-semibold cursor-pointer border-none transition"
        :class="activeFilter === f.key ? 'bg-slate-900 text-white' : 'text-slate-600 bg-white border border-slate-200 hover:bg-slate-50'"
        @click="activeFilter = f.key as any"
      >
        {{ f.label }}
      </button>
    </div>

    <!-- Results Section -->
    <div class="space-y-6">
      
      <!-- 1. EXPERTS MATCH (EXPERT FINDER) -->
      <div v-if="activeFilter === 'all' || activeFilter === 'experts'" class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-xs font-bold text-slate-900 uppercase">Chuyên gia nội bộ phù hợp (Expert Finder)</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <div
            v-for="exp in matchedExperts"
            :key="exp.name"
            class="bg-white border border-slate-200 rounded-md p-3.5 flex items-start justify-between gap-3"
          >
            <div class="flex items-start gap-2.5">
              <img :src="exp.avatar" :alt="exp.name" class="w-10 h-10 rounded-md object-cover border border-slate-200">
              <div class="text-xs">
                <span class="font-bold text-slate-900 block leading-tight">{{ exp.name }}</span>
                <span class="text-[11px] text-slate-500">{{ exp.role }} • {{ exp.department }}</span>
                <div class="flex items-center gap-1 mt-1.5 flex-wrap">
                  <span
                    v-for="sk in exp.skills"
                    :key="sk"
                    class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-600 font-semibold"
                  >
                    {{ sk }}
                  </span>
                </div>
              </div>
            </div>

            <div class="flex flex-col items-end gap-2 flex-shrink-0">
              <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">
                Khớp {{ exp.matchScore }}
              </span>
              <button
                type="button"
                class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer border-none"
                @click="contactExpert(exp.name)"
              >
                Nhắn tin
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. DOCUMENTS MATCH -->
      <div v-if="activeFilter === 'all' || activeFilter === 'docs'" class="space-y-3">
        <h2 class="text-xs font-bold text-slate-900 uppercase">Văn bản & Tài liệu liên quan</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div
            v-for="doc in matchedDocs"
            :key="doc.id"
            class="bg-white border border-slate-200 rounded-md p-3.5 space-y-2"
          >
            <div class="flex items-center justify-between text-xs">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 uppercase">
                {{ doc.fileType }}
              </span>
              <span class="text-[11px] text-slate-400">{{ doc.createdAt }}</span>
            </div>
            <h3 class="text-xs font-bold text-slate-900">{{ doc.title }}</h3>
            <p class="text-[11px] text-slate-600 leading-relaxed">{{ doc.summary }}</p>
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
              <span class="text-[11px] text-slate-400">{{ doc.department }}</span>
              <button
                type="button"
                class="text-blue-600 hover:underline font-semibold border-none bg-transparent cursor-pointer p-0"
                @click="store.setScreen('library')"
              >
                Mở trong thư viện
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Q&A MATCH -->
      <div v-if="activeFilter === 'all' || activeFilter === 'qa'" class="space-y-3">
        <h2 class="text-xs font-bold text-slate-900 uppercase">Câu hỏi tri thức liên quan</h2>

        <div class="space-y-2.5">
          <div
            v-for="q in matchedQuestions"
            :key="q.id"
            class="bg-white border border-slate-200 hover:border-slate-300 rounded-md p-3.5 flex items-center justify-between gap-4 cursor-pointer"
            @click="store.selectedQuestionId = q.id; store.setScreen('qa')"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                  {{ q.category }}
                </span>
                <span class="text-xs font-bold text-slate-900">{{ q.title }}</span>
              </div>
              <p class="text-[11px] text-slate-500 line-clamp-1">{{ q.content }}</p>
            </div>

            <div class="flex items-center gap-3 text-xs text-slate-400 flex-shrink-0">
              <span>{{ q.votes }} votes</span>
              <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>
