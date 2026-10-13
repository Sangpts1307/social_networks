<script setup lang="ts">
import { computed, reactive } from 'vue'
import type { KnowledgeTopic } from '../../api/learning'

const props = defineProps<{
  topics: KnowledgeTopic[]
  saving: boolean
  errorMessage: string
}>()

const emit = defineEmits<{
  (event: 'back'): void
  (event: 'submit', payload: { topicId: string; categoryId: string; title: string; body: string }): void
}>()

const draft = reactive({
  topicId: props.topics[0]?.id || '',
  categoryId: '',
  title: '',
  body: ''
})

const categories = computed(() => {
  const currentTopic = props.topics.find((topic) => topic.id === draft.topicId)
  return currentTopic?.categories ?? []
})

function onSelectTopic(id: string) {
  draft.topicId = id
  draft.categoryId = ''
}

function submit(): void {
  emit('submit', { ...draft })
}
</script>

<template>
  <div class="w-full bg-[#F8FAFC] py-6 flex-1">
    <div class="max-w-[1440px] mx-auto px-4 lg:px-8">
      
      <!-- Back button -->
      <div class="max-w-4xl mx-auto mb-4">
        <button
          type="button"
          class="flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:underline border-none bg-transparent cursor-pointer p-0"
          @click="emit('back')"
        >
          <span class="material-symbols-outlined text-sm">arrow_back</span>
          <span>Quay lại danh sách câu hỏi</span>
        </button>
      </div>

      <!-- Main Heading (CHUẨN 11-ask-question.html) -->
      <div class="max-w-4xl mx-auto text-center mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Đặt câu hỏi chuyên môn</h1>
        <p class="text-xs text-slate-500 mt-1">Đồng nghiệp &amp; chuyên gia trên hệ sinh thái Base Square sẽ cùng thảo luận và hỗ trợ bạn.</p>
      </div>

      <!-- Multi-step Ask Question Workspace (CHUẨN 11-ask-question.html) -->
      <div class="max-w-4xl mx-auto bg-white rounded-lg border border-slate-200 p-6 md:p-8 shadow-xs">
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
          
          <!-- Left Illustration & Guidance (CHUẨN 11-ask-question.html) -->
          <div class="md:col-span-5 flex flex-col items-center justify-center p-6 bg-slate-50/60 rounded-lg border border-slate-100 text-center">
            <div class="w-40 h-44 relative flex items-center justify-center">
              <div class="w-28 h-36 border-2 border-blue-400 bg-blue-50/50 rounded-t-md relative shadow-xs">
                <div class="absolute -top-5 right-1 w-12 h-12 rounded-full bg-amber-500 text-white font-extrabold text-xl flex items-center justify-center shadow-md">
                  Q
                </div>
                <div class="absolute bottom-4 left-3 right-3 h-1 bg-blue-300 rounded"></div>
                <div class="absolute bottom-8 left-3 right-3 h-1 bg-blue-200 rounded"></div>
                <div class="absolute top-8 left-3 w-6 h-8 border-b-4 border-blue-400"></div>
              </div>
              <div class="w-8 h-32 border-2 border-blue-300 bg-white rounded-t -ml-2 -rotate-12 shadow-xs"></div>
            </div>
            
            <h2 class="text-xs font-bold text-slate-800 mt-3">Lưu ý khi đặt câu hỏi</h2>
            <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
              Chọn đúng chủ đề và chuyên mục để câu hỏi tiếp cận chuyên gia phù hợp nhất. Mô tả ngắn gọn nhưng đủ thông tin cần giải quyết.
            </p>
          </div>

          <!-- Right Side: Form (CHUẨN 11-ask-question.html) -->
          <div class="md:col-span-7 space-y-5 text-xs">
            
            <!-- Error Banner -->
            <p
              v-if="errorMessage"
              role="alert"
              class="border border-rose-200 bg-rose-50 p-3 text-xs font-medium text-rose-700 rounded-md"
            >
              {{ errorMessage }}
            </p>

            <!-- Step 1: Chọn chủ đề -->
            <div>
              <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider block mb-2.5">
                1. CHỌN MỘT CHỦ ĐỀ BẠN MUỐN ĐẶT CÂU HỎI
              </span>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label
                  v-for="topic in topics"
                  :key="topic.id"
                  class="flex items-center gap-2 p-2 rounded-md border cursor-pointer transition select-none"
                  :class="draft.topicId === topic.id ? 'bg-blue-50/80 border-blue-500 text-blue-700 font-bold shadow-xs' : 'border-slate-200 hover:bg-slate-50 text-slate-700 bg-white'"
                  @click="onSelectTopic(topic.id)"
                >
                  <input
                    type="radio"
                    name="topic"
                    :value="topic.id"
                    :checked="draft.topicId === topic.id"
                    class="text-blue-600 focus:ring-blue-500"
                  >
                  <span class="text-xs truncate">{{ topic.name }}</span>
                </label>
              </div>
            </div>

            <!-- Step 2: Chọn danh mục / chuyên mục -->
            <div>
              <span class="text-[11px] font-bold text-slate-700 uppercase tracking-wider block mb-2">
                2. CHUYÊN MỤC CHI TIẾT
              </span>

              <div v-if="categories.length > 0" class="flex flex-wrap gap-2">
                <button
                  v-for="cat in categories"
                  :key="cat.id"
                  type="button"
                  class="px-3 py-1.5 rounded text-xs transition cursor-pointer border font-medium"
                  :class="draft.categoryId === cat.id ? 'bg-blue-600 text-white border-blue-600 font-bold shadow-xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
                  @click="draft.categoryId = cat.id"
                >
                  {{ cat.name }}
                </button>
              </div>
              <p v-else class="text-[11px] text-slate-400 italic">
                Chủ đề này chưa có phân nhánh chuyên mục phụ. Câu hỏi sẽ được gửi trực tiếp đến toàn bộ chuyên gia chủ đề.
              </p>
            </div>

            <!-- Step 3: Tiêu đề câu hỏi -->
            <div>
              <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                3. TIÊU ĐỀ CÂU HỎI
              </label>
              <input
                v-model="draft.title"
                maxlength="500"
                type="text"
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs font-normal outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition"
                placeholder="Ví dụ: Làm thế nào để xác định lộ trình Career Path cho Kỹ thuật?"
              >
            </div>

            <!-- Step 4: Nội dung câu hỏi -->
            <div>
              <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                4. NỘI DUNG CHI TIẾT
              </label>
              <textarea
                v-model="draft.body"
                rows="6"
                class="w-full rounded-md border border-slate-300 p-3 text-xs font-normal outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition leading-relaxed"
                placeholder="Nhập chi tiết bối cảnh, câu hỏi và mong muốn của bạn..."
              ></textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
              <button
                type="button"
                class="px-4 py-2 rounded-md border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold cursor-pointer transition"
                @click="emit('back')"
              >
                Hủy bỏ
              </button>
              <button
                type="button"
                :disabled="saving || !draft.title.trim() || !draft.body.trim()"
                class="px-5 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs cursor-pointer border-none transition disabled:opacity-50"
                @click="submit"
              >
                {{ saving ? 'Đang gửi...' : 'Đăng câu hỏi' }}
              </button>
            </div>

          </div>

        </div>

      </div>

    </div>
  </div>
</template>
