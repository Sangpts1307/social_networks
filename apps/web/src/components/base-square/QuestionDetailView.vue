<script setup lang="ts">
import { computed, ref } from 'vue'
import type { KnowledgeTopic, QuestionDetail } from '../../api/learning'
import { MOCK_USERS } from '../../mock/mockData.js'

const props = withDefaults(
  defineProps<{
    question: QuestionDetail
    saving: boolean
    errorMessage: string
    topics?: KnowledgeTopic[]
  }>(),
  {
    topics: () => []
  }
)

const emit = defineEmits<{
  (event: 'back'): void
  (event: 'vote-question', id: string): void
  (event: 'vote-answer', id: string): void
  (event: 'answer', body: string): void
}>()

const answer = ref('')
const quickComment = ref('')
const activeAnswerTab = ref<'relevant' | 'recent'>('relevant')
const hasVotedQuestion = ref(false)
const commentNotice = ref('')

const topicName = computed(() => {
  const t = props.topics.find((topic) => topic.id === props.question.topic_id)
  return t ? t.name : 'Bán hàng'
})

const categoryName = computed(() => {
  const t = props.topics.find((topic) => topic.id === props.question.topic_id)
  const c = t?.categories.find((cat) => cat.id === props.question.category_id)
  return c ? c.name : 'Quy trình & Phương pháp'
})

const authorName = computed(() => {
  const user = MOCK_USERS.find((u) => u.id === props.question.author_user_id)
  if (user) return user.name
  if (props.question.author_user_id.length > 20) {
    return 'Trần Viễn'
  }
  return props.question.author_user_id || 'Trần Viễn'
})

const authorAvatar = computed(() => {
  const user = MOCK_USERS.find((u) => u.id === props.question.author_user_id)
  return user?.avatar || 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&auto=format&fit=crop&q=80'
})

const recommendedExperts = ref([
  {
    name: 'Trần Viễn',
    role: 'CEO',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&auto=format&fit=crop&q=80'
  },
  {
    name: 'Võ Thành Luân',
    role: 'Senior Backend & AI Specialist',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80'
  }
])

const followers = ref([
  {
    name: 'Phạm Phát',
    role: 'Solutions Architect',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&auto=format&fit=crop&q=80'
  }
])

const similarQuestions = ref([
  {
    id: 'sim-1',
    title: 'Xử lý thế nào khi khách hàng nói "Chúng tôi đang làm việc với một đối thủ khác"?',
    time: '981 ngày trước',
    votes: 3
  },
  {
    id: 'sim-2',
    title: 'Quy trình đàm phán giải pháp doanh nghiệp B2B hiệu quả',
    time: '450 ngày trước',
    votes: 5
  }
])

function formattedDate(value: string): string {
  if (!value) return 'Vừa xong'
  return new Date(value).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' })
}

function sendAnswer(): void {
  if (answer.value.trim()) {
    emit('answer', answer.value.trim())
    answer.value = ''
  }
}

function handleVoteQuestion(): void {
  emit('vote-question', props.question.id)
  hasVotedQuestion.value = true
}

function sendComment(): void {
  if (!quickComment.value.trim()) return
  commentNotice.value = 'Đã gửi bình luận nhanh của bạn!'
  quickComment.value = ''
  setTimeout(() => {
    commentNotice.value = ''
  }, 3000)
}

function addExpert(): void {
  window.alert('Mở hộp thoại đề xuất thêm chuyên gia phụ trách câu hỏi này.')
}

function addFollower(): void {
  window.alert('Đã thêm bạn vào danh sách theo dõi câu hỏi này.')
}

function addTag(): void {
  window.alert('Nhập nhãn mới để phân loại câu hỏi.')
}
</script>

<template>
  <div class="w-full bg-[#F8FAFC] py-6 flex-1">
    <div class="max-w-[1440px] mx-auto px-4 lg:px-8 grid grid-cols-1 md:grid-cols-12 gap-6">
      
      <!-- LEFT SIDEBAR: AUTHOR & EXPERTS (CHUẨN 10-qa-detail.html) -->
      <aside class="md:col-span-3 space-y-4">
        
        <!-- Category Back Link -->
        <div class="p-3 bg-white rounded-lg border border-slate-200 shadow-xs space-y-3 text-xs">
          <button
            type="button"
            class="flex items-center gap-2 font-bold text-blue-600 hover:underline border-none bg-transparent cursor-pointer p-0 text-left w-full"
            @click="emit('back')"
          >
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            <span class="material-symbols-outlined text-base">menu_book</span>
            <span>{{ topicName }}</span>
          </button>

          <div class="pt-2 border-t border-slate-100">
            <span class="text-[11px] text-slate-400 block font-medium">Chuyên mục:</span>
            <span class="font-bold text-slate-800 text-xs">{{ categoryName }}</span>
          </div>
        </div>

        <!-- Question Author -->
        <div class="p-3 bg-white rounded-lg border border-slate-200 shadow-xs space-y-2 text-xs">
          <span class="text-[11px] text-slate-400 font-bold uppercase tracking-wider block">Người đặt câu hỏi</span>
          <div class="flex items-center gap-2.5">
            <img
              :src="authorAvatar"
              alt="Avatar"
              class="w-9 h-9 rounded-full object-cover border border-slate-200"
            >
            <div>
              <span class="font-bold text-slate-900 block leading-tight">{{ authorName }}</span>
              <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-0.5">
                <span>⚪ 155</span>
                <span class="text-amber-600 font-semibold">🟡 63</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recommended Experts -->
        <div class="p-3 bg-white rounded-lg border border-slate-200 shadow-xs space-y-2 text-xs">
          <span class="text-[11px] text-slate-400 font-bold uppercase tracking-wider block">Các chuyên gia được đề xuất</span>
          <div
            v-for="expert in recommendedExperts"
            :key="expert.name"
            class="flex items-center gap-2.5"
          >
            <img
              :src="expert.avatar"
              alt="Expert"
              class="w-8 h-8 rounded-full object-cover border border-slate-200"
            >
            <div>
              <span class="font-bold text-slate-900 block leading-tight">{{ expert.name }}</span>
              <span class="text-[10px] text-slate-400">{{ expert.role }}</span>
            </div>
          </div>
          <button
            type="button"
            class="text-[11px] font-bold text-blue-600 hover:underline pt-1 block border-none bg-transparent cursor-pointer text-left w-full"
            @click="addExpert"
          >
            + THÊM CHUYÊN GIA KHÁC
          </button>
        </div>

        <!-- Followers -->
        <div class="p-3 bg-white rounded-lg border border-slate-200 shadow-xs space-y-2 text-xs">
          <span class="text-[11px] text-slate-400 font-bold uppercase tracking-wider block">Người theo dõi</span>
          <div
            v-for="follower in followers"
            :key="follower.name"
            class="flex items-center gap-2.5"
          >
            <img
              :src="follower.avatar"
              alt="Follower"
              class="w-8 h-8 rounded-full object-cover border border-slate-200"
            >
            <div>
              <span class="font-bold text-slate-900 block leading-tight">{{ follower.name }}</span>
              <span class="text-[10px] text-slate-400">{{ follower.role }}</span>
            </div>
          </div>
          <button
            type="button"
            class="text-[11px] font-bold text-blue-600 hover:underline pt-1 block border-none bg-transparent cursor-pointer text-left w-full"
            @click="addFollower"
          >
            + THÊM NGƯỜI THEO DÕI
          </button>
        </div>

      </aside>

      <!-- CENTER: QUESTION BODY & ANSWERS (CHUẨN 10-qa-detail.html) -->
      <section class="md:col-span-6 space-y-5 text-xs">
        
        <p
          v-if="errorMessage"
          role="alert"
          class="border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700 rounded-lg"
        >
          {{ errorMessage }}
        </p>

        <!-- MAIN QUESTION CARD -->
        <article class="bg-white rounded-lg border border-slate-200 p-5 shadow-xs flex gap-4">
          
          <!-- Left Vote & Star Box -->
          <div class="flex flex-col items-center justify-start min-w-[50px] border-r border-slate-100 pr-3 flex-shrink-0">
            <span class="text-3xl font-bold text-slate-900">{{ question.score }}</span>
            <button
              type="button"
              class="text-[10px] font-bold hover:text-blue-600 uppercase mt-0.5 border-none bg-transparent cursor-pointer p-0"
              :class="hasVotedQuestion ? 'text-blue-600 font-extrabold' : 'text-slate-400'"
              title="Bình chọn câu hỏi này"
              @click="handleVoteQuestion"
            >
              ▲ VOTE
            </button>
            <div class="mt-3 flex flex-col items-center text-amber-500">
              <span class="text-base leading-none">★</span>
              <span class="text-[11px] font-bold text-slate-700">1</span>
            </div>
          </div>

          <!-- Question Content -->
          <div class="flex-1 space-y-3">
            
            <!-- Status Pill -->
            <div>
              <span
                v-if="question.answers.length > 0 || question.question_status === 'closed'"
                class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 font-bold rounded text-[10px] uppercase tracking-wider"
              >
                ĐÃ ĐƯỢC TRẢ LỜI
              </span>
              <span
                v-else
                class="px-2.5 py-0.5 bg-amber-100 text-amber-800 font-bold rounded text-[10px] uppercase tracking-wider"
              >
                CHỜ GIẢI ĐÁP
              </span>
            </div>

            <!-- Question Title -->
            <h1 class="text-lg font-bold text-slate-900 leading-snug">
              {{ question.title }}
            </h1>

            <!-- Meta Actions Subline -->
            <p class="text-[11px] text-slate-400 leading-relaxed">
              Đặt câu hỏi lúc {{ formattedDate(question.created_at) }} bởi
              <strong class="text-slate-700">{{ authorName }}</strong> • 
              <span class="hover:underline cursor-pointer">Chỉnh sửa</span> • 
              <span class="hover:underline cursor-pointer">Bỏ đánh dấu tri thức</span> • 
              <span class="hover:underline cursor-pointer">Thay đổi chuyên mục</span> • 
              <span class="hover:underline cursor-pointer">Ẩn</span>
            </p>

            <!-- Question Body Text -->
            <div class="text-slate-700 leading-relaxed pt-1 whitespace-pre-wrap">
              {{ question.body_plaintext }}
            </div>

            <!-- Stats & Add Tag Button -->
            <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500">
              <div class="flex items-center gap-1.5 flex-wrap">
                <span>≡ {{ question.answer_count || 1 }} câu trả lời</span>
                <span>•</span>
                <span>{{ question.score }} bình chọn</span>
                <span>•</span>
                <span>{{ question.score * 10 }} điểm</span>
                <span>•</span>
                <span>0 phần thưởng</span>
              </div>
              <button
                type="button"
                class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold text-[11px] flex items-center gap-1 border-none cursor-pointer"
                @click="addTag"
              >
                <span>+ THÊM NHÃN</span>
              </button>
            </div>

            <!-- Light Green Knowledge Callout Box -->
            <div class="p-3.5 bg-emerald-50/80 rounded-lg border border-emerald-200 flex items-start gap-3">
              <span class="material-symbols-outlined text-emerald-600 text-2xl mt-0.5">description</span>
              <div>
                <h3 class="font-bold text-emerald-900 text-xs">Đề xuất tri thức</h3>
                <p class="text-[11px] text-emerald-800 leading-relaxed mt-0.5">
                  Câu hỏi này được đề xuất là một bài viết tri thức trong cơ sở tri thức của công ty.
                </p>
              </div>
            </div>

            <!-- Comment Input Box -->
            <div class="pt-2">
              <div class="flex items-center bg-slate-50 rounded-md border border-slate-200 px-3 py-2">
                <input
                  v-model="quickComment"
                  type="text"
                  placeholder="Bình luận và nhấn Enter để gửi"
                  class="flex-1 bg-transparent text-xs outline-none border-none text-slate-800"
                  @keyup.enter="sendComment"
                >
                <div class="flex items-center gap-2 text-slate-400">
                  <button type="button" class="hover:text-slate-700 border-none bg-transparent cursor-pointer p-0" title="Đính kèm tệp">
                    <span class="material-symbols-outlined text-base">attach_file</span>
                  </button>
                  <button type="button" class="hover:text-slate-700 border-none bg-transparent cursor-pointer p-0" title="Gợi ý tri thức">
                    <span class="material-symbols-outlined text-base">lightbulb</span>
                  </button>
                  <button type="button" class="hover:text-slate-700 border-none bg-transparent cursor-pointer p-0" title="Biểu cảm">
                    <span class="material-symbols-outlined text-base">mood</span>
                  </button>
                </div>
              </div>
              <p v-if="commentNotice" class="text-[11px] text-emerald-600 font-semibold mt-1">
                {{ commentNotice }}
              </p>
            </div>

          </div>
        </article>

        <!-- ANSWERS SECTION -->
        <div class="space-y-4">
          
          <!-- Answers Header Tabs -->
          <div class="flex items-center justify-between pb-2 border-b border-slate-200">
            <h2 class="text-sm font-bold text-slate-900">
              {{ question.answers.length }} câu trả lời
            </h2>
            <div class="flex bg-white rounded-md border border-slate-200 overflow-hidden font-semibold">
              <button
                type="button"
                class="px-3 py-1 cursor-pointer border-none text-xs"
                :class="activeAnswerTab === 'relevant' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900 bg-transparent'"
                @click="activeAnswerTab = 'relevant'"
              >
                Liên quan
              </button>
              <button
                type="button"
                class="px-3 py-1 cursor-pointer border-none text-xs"
                :class="activeAnswerTab === 'recent' ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-500 hover:text-slate-900 bg-transparent'"
                @click="activeAnswerTab = 'recent'"
              >
                Gần đây
              </button>
            </div>
          </div>

          <!-- Answer Cards -->
          <article
            v-for="ans in question.answers"
            :key="ans.id"
            class="bg-white rounded-lg border p-5 shadow-xs flex gap-4 transition"
            :class="ans.id === question.best_answer_id ? 'border-emerald-300 bg-emerald-50/20' : 'border-slate-200'"
          >
            <!-- Left Vote Box -->
            <div class="flex flex-col items-center justify-start min-w-[50px] border-r border-slate-100 pr-3 flex-shrink-0">
              <span class="text-2xl font-bold text-slate-800">{{ ans.score }}</span>
              <button
                type="button"
                class="text-[10px] font-bold text-slate-400 hover:text-blue-600 uppercase mt-0.5 border-none bg-transparent cursor-pointer p-0"
                title="Bình chọn câu trả lời hữu ích"
                @click="emit('vote-answer', ans.id)"
              >
                ▲ VOTE
              </button>
            </div>

            <!-- Answer Content -->
            <div class="flex-1 space-y-3 leading-relaxed">
              <p
                v-if="ans.id === question.best_answer_id"
                class="text-xs font-bold text-emerald-700 flex items-center gap-1"
              >
                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-bold text-[10px]">
                  ✔ BEST ANSWER HIỆN TẠI
                </span>
              </p>

              <div class="whitespace-pre-wrap text-slate-800">
                {{ ans.body_plaintext }}
              </div>

              <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-[11px] text-slate-500">
                <div class="flex items-center gap-2">
                  <img
                    src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=60&auto=format&fit=crop&q=80"
                    alt="Answer Author"
                    class="w-5 h-5 rounded-full object-cover"
                  >
                  <span class="font-bold text-slate-800">Võ Thành Luân (Tech Lead)</span>
                  <span>• Trả lời {{ formattedDate(ans.created_at) }}</span>
                </div>
                <button
                  type="button"
                  class="text-blue-600 font-bold hover:underline border-none bg-transparent cursor-pointer p-0"
                  @click="emit('vote-answer', ans.id)"
                >
                  ▲ Hữu ích ({{ ans.score }})
                </button>
              </div>
            </div>
          </article>

          <!-- Composer: Gửi câu trả lời mới -->
          <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-xs space-y-3">
            <h3 class="text-sm font-bold text-slate-900">Trả lời câu hỏi này</h3>
            <form @submit.prevent="sendAnswer">
              <textarea
                v-model="answer"
                rows="4"
                class="w-full rounded-md border border-slate-300 p-3 text-xs font-normal outline-none focus:border-blue-600 transition"
                placeholder="Chia sẻ câu trả lời chuyên môn, giải pháp hoặc tài liệu liên quan..."
              ></textarea>
              <div class="flex justify-end gap-2 mt-3">
                <button
                  type="submit"
                  :disabled="saving || !answer.trim()"
                  class="rounded-md bg-blue-600 hover:bg-blue-700 px-5 py-2 font-bold text-white transition disabled:opacity-50 cursor-pointer border-none"
                >
                  {{ saving ? 'Đang gửi...' : 'Gửi câu trả lời' }}
                </button>
              </div>
            </form>
          </div>

        </div>

      </section>

      <!-- RIGHT SIDEBAR: RELATED QUESTIONS (CHUẨN 10-qa-detail.html) -->
      <aside class="md:col-span-3 space-y-4 text-xs">
        
        <!-- Card: Đề xuất tri thức AI -->
        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-2">
          <h3 class="font-bold text-slate-900">Đề xuất tri thức</h3>
          <p class="text-slate-500 text-[11px] leading-relaxed">
            AI chỉ đề xuất nguồn đã publish và luôn giữ provenance. Related questions sẽ được mở khi AI Knowledge projection sẵn sàng.
          </p>
        </div>

        <!-- Card: Câu hỏi tương tự -->
        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-3">
          <h3 class="font-bold text-slate-900">Câu hỏi tương tự</h3>
          <div class="space-y-2 text-[11px]">
            <div
              v-for="q in similarQuestions"
              :key="q.id"
              class="flex items-start gap-2 hover:text-blue-600 cursor-pointer"
            >
              <span class="material-symbols-outlined text-base text-slate-400 mt-0.5">menu_book</span>
              <div>
                <span class="font-medium text-blue-600 leading-snug block">
                  {{ q.title }}
                </span>
                <span class="text-[10px] text-slate-400">{{ q.time }} • {{ q.votes }} Bình chọn</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Card: Câu hỏi khác của tác giả -->
        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-3">
          <h3 class="font-bold text-slate-900">Câu hỏi khác của {{ authorName }}</h3>
          <div class="space-y-2 text-[11px]">
            <div class="flex items-start gap-2 hover:text-blue-600 cursor-pointer">
              <span class="material-symbols-outlined text-base text-slate-400 mt-0.5">menu_book</span>
              <div>
                <span class="font-medium text-blue-600 leading-snug block">
                  Cách triển khai microservices với cơ sở dữ liệu đồ thị Neo4j
                </span>
                <span class="text-[10px] text-slate-400">120 ngày trước • 7 Bình chọn</span>
              </div>
            </div>
          </div>
        </div>

      </aside>

    </div>
  </div>
</template>
