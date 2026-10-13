<script setup lang="ts">
import { ref, computed } from 'vue'
import { store } from '../../mock/store'

const activeCategory = ref('Tất cả')
const searchQuery = ref('')
const sortBy = ref<'newest' | 'votes'>('votes')
const showAskModal = ref(false)
const viewingDetail = ref(true)

// Form fields for new question
const askTitle = ref('')
const askTopic = ref('Kỹ thuật')
const askCategory = ref('Backend')
const askContent = ref('')

// Form fields for new answer
const newAnswerText = ref('')

const selectedQ = computed(() => {
  return store.questions.find(q => q.id === store.selectedQuestionId) || store.questions[0]
})

const filteredQuestions = computed(() => {
  let list = store.questions.filter(q => {
    if (activeCategory.value !== 'Tất cả' && q.topic !== activeCategory.value) return false
    if (searchQuery.value.trim()) {
      const query = searchQuery.value.toLowerCase()
      return q.title.toLowerCase().includes(query) || q.content.toLowerCase().includes(query)
    }
    return true
  })

  if (sortBy.value === 'votes') {
    return list.slice().sort((a, b) => b.votes - a.votes)
  }
  return list
})

function handleSelectQuestion(id: string) {
  store.selectedQuestionId = id
  viewingDetail.value = true
}

function handleAddAnswer() {
  if (!newAnswerText.value.trim() || !selectedQ.value) return
  store.addAnswer(selectedQ.value.id, newAnswerText.value)
  newAnswerText.value = ''
}

function handleCreateQuestion() {
  if (!askTitle.value.trim() || !askContent.value.trim()) return
  store.addQuestion(askTitle.value, askContent.value, askTopic.value, askCategory.value)
  askTitle.value = ''
  askContent.value = ''
  showAskModal.value = false
  viewingDetail.value = true
}
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 py-6 space-y-5">
    
    <!-- Top Bar: Filter, Search, Action -->
    <div class="bg-white border border-slate-200 rounded-md p-3.5 flex flex-col md:flex-row md:items-center justify-between gap-3">
      
      <!-- Search & Category Filters -->
      <div class="flex flex-wrap items-center gap-2">
        <div class="relative w-full sm:w-64">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Tìm kiếm câu hỏi tri thức..."
            class="w-full pl-8 pr-3 py-1.5 bg-slate-50 text-xs rounded border border-slate-200 outline-none"
          >
          <span class="material-symbols-outlined absolute left-2.5 top-2 text-slate-400 text-sm">search</span>
        </div>

        <div class="flex items-center gap-1 overflow-x-auto">
          <button
            v-for="cat in ['Tất cả', 'Kỹ thuật', 'Nhân sự', 'Sản phẩm']"
            :key="cat"
            type="button"
            class="px-2.5 py-1 rounded text-xs font-semibold cursor-pointer border-none"
            :class="activeCategory === cat ? 'bg-slate-900 text-white' : 'text-slate-600 bg-slate-100 hover:bg-slate-200'"
            @click="activeCategory = cat"
          >
            {{ cat }}
          </button>
        </div>
      </div>

      <!-- Right Action & Sort -->
      <div class="flex items-center gap-3">
        <select 
          v-model="sortBy"
          class="px-2 py-1.5 bg-slate-50 text-xs rounded border border-slate-200 text-slate-700 outline-none cursor-pointer"
        >
          <option value="votes">Nhiều bình chọn nhất</option>
          <option value="newest">Mới nhất</option>
        </select>

        <button
          type="button"
          class="px-3.5 py-1.5 rounded bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold cursor-pointer border-none flex items-center gap-1.5 transition"
          @click="showAskModal = true"
        >
          <span class="material-symbols-outlined text-sm">add</span>
          <span>Đặt câu hỏi</span>
        </button>
      </div>

    </div>

    <!-- Main 2-Column Split: Question List Left (5 cols) & Question Detail Right (7 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
      
      <!-- LEFT LIST (5 cols) -->
      <div class="lg:col-span-5 space-y-2.5">
        <div class="text-xs font-bold text-slate-700 px-1 flex items-center justify-between">
          <span>DANH SÁCH CÂU HỎI ({{ filteredQuestions.length }})</span>
        </div>

        <div
          v-for="q in filteredQuestions"
          :key="q.id"
          class="bg-white border rounded-md p-3.5 transition cursor-pointer space-y-2"
          :class="selectedQ && selectedQ.id === q.id ? 'border-slate-800 bg-slate-50/50' : 'border-slate-200 hover:border-slate-400'"
          @click="handleSelectQuestion(q.id)"
        >
          <div class="flex items-start justify-between gap-2">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
              {{ q.category }}
            </span>
            <div class="flex items-center gap-2 text-[11px] text-slate-500 font-medium">
              <span>{{ q.votes }} vote</span>
              <span>•</span>
              <span>{{ q.answersCount }} trả lời</span>
            </div>
          </div>

          <h3 class="text-xs font-bold text-slate-900 leading-snug">{{ q.title }}</h3>
          <p class="text-[11px] text-slate-500 line-clamp-2 leading-relaxed">{{ q.content }}</p>

          <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-[10px] text-slate-400">
            <span>Đăng bởi: {{ q.author }}</span>
            <span>{{ q.createdAt }}</span>
          </div>
        </div>
      </div>

      <!-- RIGHT DETAIL (7 cols) -->
      <div v-if="selectedQ" class="lg:col-span-7 bg-white border border-slate-200 rounded-md p-5 space-y-4">
        
        <!-- Question Meta & Vote -->
        <div class="flex items-start gap-4">
          <!-- Upvote/Downvote Box -->
          <div class="flex flex-col items-center bg-slate-50 border border-slate-200 rounded p-1.5 min-w-[42px]">
            <button
              type="button"
              class="p-1 text-slate-500 hover:text-blue-600 transition cursor-pointer border-none bg-transparent"
              title="Bình chọn hữu ích"
              @click="store.voteQuestion(selectedQ.id, 1)"
            >
              <span class="material-symbols-outlined text-base">expand_less</span>
            </button>
            <span class="text-xs font-bold text-slate-900 my-0.5">{{ selectedQ.votes }}</span>
            <button
              type="button"
              class="p-1 text-slate-500 hover:text-rose-600 transition cursor-pointer border-none bg-transparent"
              title="Bình chọn không hữu ích"
              @click="store.voteQuestion(selectedQ.id, -1)"
            >
              <span class="material-symbols-outlined text-base">expand_more</span>
            </button>
          </div>

          <!-- Main Question Info -->
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1.5">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                {{ selectedQ.topic }} / {{ selectedQ.category }}
              </span>
              <span class="text-[10px] text-slate-400">{{ selectedQ.createdAt }}</span>
            </div>
            <h2 class="text-sm font-bold text-slate-900 leading-snug">{{ selectedQ.title }}</h2>
            <p class="text-xs text-slate-700 mt-2 leading-relaxed whitespace-pre-line bg-slate-50/50 p-3 rounded border border-slate-100">
              {{ selectedQ.content }}
            </p>
            
            <div class="flex items-center gap-2 mt-3 text-xs text-slate-500">
              <img :src="selectedQ.avatar" :alt="selectedQ.author" class="w-5 h-5 rounded-full object-cover">
              <span>{{ selectedQ.author }} ({{ selectedQ.authorRole }})</span>
            </div>
          </div>
        </div>

        <!-- Answers Section -->
        <div class="pt-4 border-t border-slate-200 space-y-3">
          <h3 class="text-xs font-bold text-slate-900 uppercase">
            Câu trả lời ({{ selectedQ.answers.length }})
          </h3>

          <div v-if="selectedQ.answers.length === 0" class="p-4 rounded bg-slate-50 text-center text-xs text-slate-500">
            Chưa có câu trả lời nào. Hãy là người đầu tiên chia sẻ tri thức!
          </div>

          <div
            v-for="ans in selectedQ.answers"
            :key="ans.id"
            class="p-3 rounded border text-xs space-y-2"
            :class="ans.isAccepted ? 'bg-emerald-50/30 border-emerald-200' : 'bg-slate-50 border-slate-200'"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <img :src="ans.avatar" :alt="ans.author" class="w-6 h-6 rounded object-cover">
                <div>
                  <span class="font-bold text-slate-800">{{ ans.author }}</span>
                  <span class="text-[10px] text-slate-400 ml-1.5">{{ ans.authorRole }}</span>
                </div>
              </div>
              <span 
                v-if="ans.isAccepted" 
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white"
              >
                <span class="material-symbols-outlined text-xs">check</span>
                <span>Câu trả lời được chấp nhận</span>
              </span>
            </div>

            <p class="text-slate-800 leading-relaxed">{{ ans.content }}</p>

            <div class="flex items-center justify-between pt-1 text-[11px] text-slate-500">
              <span>{{ ans.createdAt }}</span>
              <span class="font-semibold text-slate-700">{{ ans.votes }} bình chọn</span>
            </div>
          </div>
        </div>

        <!-- Submit New Answer Box -->
        <div class="pt-3 border-t border-slate-200 space-y-2">
          <label class="block text-xs font-bold text-slate-800">Đóng góp câu trả lời của bạn</label>
          <textarea
            v-model="newAnswerText"
            rows="3"
            placeholder="Nhập nội dung giải đáp, giải pháp kỹ thuật hoặc tài liệu tham khảo..."
            class="w-full p-2.5 bg-slate-50 focus:bg-white text-xs text-slate-900 rounded border border-slate-200 outline-none"
          ></textarea>
          <div class="flex justify-end">
            <button
              type="button"
              class="px-4 py-1.5 rounded bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold cursor-pointer border-none transition disabled:opacity-50"
              :disabled="!newAnswerText.trim()"
              @click="handleAddAnswer"
            >
              Gửi câu trả lời (+20 điểm)
            </button>
          </div>
        </div>

      </div>

    </div>

    <!-- Modal: Đặt câu hỏi mới -->
    <div v-if="showAskModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
      <div class="bg-white border border-slate-200 rounded-md max-w-lg w-full p-5 space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <h3 class="text-sm font-bold text-slate-900">Đặt câu hỏi tri thức mới</h3>
          <button 
            type="button" 
            class="text-slate-400 hover:text-slate-700 cursor-pointer border-none bg-transparent text-sm p-0"
            @click="showAskModal = false"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <label class="block font-semibold text-slate-800 mb-1">Chủ đề & Chuyên mục</label>
            <div class="grid grid-cols-2 gap-2">
              <select v-model="askTopic" class="p-2 bg-slate-50 border border-slate-200 rounded outline-none">
                <option value="Kỹ thuật">Kỹ thuật</option>
                <option value="Nhân sự">Nhân sự</option>
                <option value="Sản phẩm">Sản phẩm</option>
                <option value="Marketing">Marketing</option>
              </select>
              <input 
                v-model="askCategory" 
                type="text" 
                placeholder="Phân nhánh (VD: Backend, Onboarding)" 
                class="p-2 bg-slate-50 border border-slate-200 rounded outline-none"
              >
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-800 mb-1">Tiêu đề câu hỏi</label>
            <input 
              v-model="askTitle" 
              type="text" 
              placeholder="Tóm tắt rõ ràng vấn đề bạn đang gặp phải..." 
              class="w-full p-2 bg-slate-50 border border-slate-200 rounded outline-none"
            >
          </div>

          <div>
            <label class="block font-semibold text-slate-800 mb-1">Nội dung chi tiết</label>
            <textarea 
              v-model="askContent" 
              rows="4" 
              placeholder="Mô tả bối cảnh, lỗi gặp phải hoặc mục tiêu cần giải quyết..." 
              class="w-full p-2 bg-slate-50 border border-slate-200 rounded outline-none"
            ></textarea>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button 
            type="button" 
            class="px-3 py-1.5 rounded bg-slate-100 text-slate-700 text-xs font-semibold cursor-pointer border-none"
            @click="showAskModal = false"
          >
            Hủy
          </button>
          <button 
            type="button" 
            class="px-4 py-1.5 rounded bg-slate-900 text-white text-xs font-semibold cursor-pointer border-none"
            @click="handleCreateQuestion"
          >
            Đăng câu hỏi
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
