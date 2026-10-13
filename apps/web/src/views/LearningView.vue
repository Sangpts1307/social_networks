<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { ApiError, type AuthUser } from '../api/auth'
import {
  learningApi,
  type KnowledgeItem,
  type KnowledgeTopic,
  type QuestionDetail,
  type QuestionSummary
} from '../api/learning'
import DocumentDetailView from '../components/base-square/DocumentDetailView.vue'
import AskQuestionView from '../components/base-square/AskQuestionView.vue'
import QuestionDetailView from '../components/base-square/QuestionDetailView.vue'
import {
  MOCK_LIBRARY_DOCUMENTS,
  MOCK_GRAPH_DATA,
  MOCK_LEADERBOARD,
  MOCK_SEARCH_RESULTS,
  MOCK_QA_QUESTIONS,
  MOCK_USERS
} from '../mock/mockData.js'

type SquareTab = 'questions' | 'knowledge' | 'library' | 'reward' | 'profile' | 'admin' | 'search'

const props = withDefaults(
  defineProps<{
    token: string
    initialTab?: string
    user?: AuthUser | null
  }>(),
  {
    initialTab: 'questions',
    user: null
  }
)

const emit = defineEmits<{
  (event: 'back'): void
  (event: 'logout'): void
  (event: 'change-tab', tab: string): void
}>()

const activeTab = ref<SquareTab>(
  (props.initialTab === 'home' ? 'questions' : (props.initialTab as SquareTab)) || 'questions'
)
const globalSearchQuery = ref('')

// Learning API & Provenance state (kết nối trực tiếp backend)
const topics = ref<KnowledgeTopic[]>([])
const selectedTopicId = ref<string>()
const selectedCategoryId = ref<string>()
const items = ref<KnowledgeItem[]>([])
const selectedItem = ref<KnowledgeItem>()
const showDocumentDetail = ref(false)
const questions = ref<QuestionSummary[]>([])
const selectedQuestion = ref<QuestionDetail>()
const questionSearch = ref('')
const nextCursor = ref<string | null>(null)
const showQuestionComposer = ref(false)
const showQuestionDetail = ref(false)
const loading = ref(true)
const saving = ref(false)
const errorMessage = ref('')
const qaFilter = ref<'following' | 'mine' | 'assigned' | 'answered' | 'unanswered'>('following')
const qaSort = ref<'latest' | 'trending' | 'votes' | 'rewards'>('latest')

// Mock state for sub-modules
const libraryDocs = ref([...MOCK_LIBRARY_DOCUMENTS])
const libraryCategory = ref('ALL')
const graphNodes = ref(MOCK_GRAPH_DATA.nodes)
const graphLinks = ref(MOCK_GRAPH_DATA.links)
const selectedGraphNode = ref(graphNodes.value[0])
const leaderboard = ref([...MOCK_LEADERBOARD])
const searchResults = ref([...MOCK_SEARCH_RESULTS])
const searchInput = ref('Knowledge Graph và Microservices')

function showAlert(text: string): void {
  if (typeof window !== 'undefined') {
    window.alert(text)
  }
}

function message(error: unknown, fallback: string): string {
  return error instanceof ApiError ? error.message : fallback
}

function formattedDate(value: string): string {
  return new Date(value).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' })
}

function selectTab(tab: SquareTab | 'home') {
  const targetTab = tab === 'home' ? 'questions' : tab
  activeTab.value = targetTab
  showDocumentDetail.value = false
  showQuestionComposer.value = false
  showQuestionDetail.value = false
  emit('change-tab', targetTab)
  if (typeof window !== 'undefined') {
    const url = new URL(window.location.href)
    url.searchParams.set('app', 'square')
    url.searchParams.set('tab', targetTab)
    window.history.pushState({ view: 'square', tab: targetTab }, '', url.toString())
  }
}

function handleGlobalSearch() {
  if (!globalSearchQuery.value.trim()) return
  searchInput.value = globalSearchQuery.value.trim()
  selectTab('search')
}

// Data loaders
async function loadItems(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await learningApi.items(props.token, selectedTopicId.value)
    items.value = response.data
    if (!items.value.some((item) => item.id === selectedItem.value?.id)) selectedItem.value = items.value[0]
  } catch (error) {
    errorMessage.value = message(error, 'Không thể tải Knowledge Foundation.')
  } finally {
    loading.value = false
  }
}

async function loadQuestions(append = false): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    const response = await learningApi.questions(props.token, {
      topicId: selectedTopicId.value,
      categoryId: selectedCategoryId.value,
      query: questionSearch.value.trim() || undefined,
      cursor: append ? nextCursor.value ?? undefined : undefined,
    })
    questions.value = append ? [...questions.value, ...response.data] : response.data
    nextCursor.value = response.meta.next_cursor
    if (!questions.value.some((question) => question.id === selectedQuestion.value?.id)) selectedQuestion.value = undefined
  } catch (error) {
    errorMessage.value = message(error, 'Không thể tải hỏi đáp.')
  } finally {
    loading.value = false
  }
}

async function refresh(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    topics.value = (await learningApi.topics(props.token)).data
    await loadItems()
    await loadQuestions()
  } catch (error) {
    errorMessage.value = message(error, 'Không thể kết nối Learning Service.')
    loading.value = false
  }
}

async function selectQuestion(questionId: string): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    selectedQuestion.value = (await learningApi.question(props.token, questionId)).data
    showQuestionDetail.value = true
  } catch (error) {
    errorMessage.value = message(error, 'Không thể tải chi tiết câu hỏi.')
  } finally {
    loading.value = false
  }
}

async function submitQuestion(payload: { topicId: string; categoryId: string; title: string; body: string }): Promise<void> {
  if (!payload.topicId || !payload.categoryId || !payload.title.trim() || !payload.body.trim()) {
    errorMessage.value = 'Hãy chọn taxonomy và nhập đầy đủ tiêu đề, nội dung.'
    return
  }
  saving.value = true
  errorMessage.value = ''
  try {
    const created = await learningApi.createQuestion(props.token, { topic_id: payload.topicId, category_id: payload.categoryId, title: payload.title.trim(), body_markdown: payload.body.trim() })
    showQuestionComposer.value = false
    activeTab.value = 'questions'
    await loadQuestions()
    await selectQuestion(created.data.id)
  } catch (error) {
    errorMessage.value = message(error, 'Không thể tạo câu hỏi.')
  } finally {
    saving.value = false
  }
}

async function voteQuestion(questionId: string): Promise<void> {
  try {
    await learningApi.voteQuestion(props.token, questionId, 1)
    await loadQuestions()
    if (selectedQuestion.value?.id === questionId) await selectQuestion(questionId)
  } catch (error) {
    errorMessage.value = message(error, 'Không thể bình chọn câu hỏi.')
  }
}

async function voteAnswer(answerId: string): Promise<void> {
  if (!selectedQuestion.value) return
  try {
    await learningApi.voteAnswer(props.token, answerId, 1)
    await selectQuestion(selectedQuestion.value.id)
  } catch (error) {
    errorMessage.value = message(error, 'Không thể bình chọn câu trả lời.')
  }
}

async function createAnswerFromDetail(body: string): Promise<void> {
  if (!selectedQuestion.value) return
  saving.value = true
  try {
    await learningApi.createAnswer(props.token, selectedQuestion.value.id, body)
    await selectQuestion(selectedQuestion.value.id)
    await loadQuestions()
  } catch (error) {
    errorMessage.value = message(error, 'Không thể gửi câu trả lời.')
  } finally {
    saving.value = false
  }
}

function getNode(id: string) {
  return graphNodes.value.find((n) => n.id === id)
}

function handleDownload(title: string) {
  window.alert(`Đang tải xuống: ${title}\n(Tài liệu lưu trữ an toàn trong kho lưu trữ Base Square).`)
}

function getScore(q: any): number {
  return 'score' in q ? q.score : q.votes || 0
}

function getAnswerCount(q: any): number {
  return 'answer_count' in q ? q.answer_count : q.answersCount || 0
}

function getFormattedTime(q: any): string {
  return 'created_at' in q ? formattedDate(q.created_at) : q.time || 'vừa xong'
}

function getBodySnippet(q: any): string {
  return q.body_plaintext || q.summary || 'Thông tin chi tiết về câu hỏi chuyên môn...'
}

function getAuthorName(q: any): string {
  if (q.author_name) return q.author_name
  if (q.author) return q.author
  const found = MOCK_USERS.find((u: any) => u.id === q.author_user_id)
  return found?.name || 'Trần Viễn'
}

function getAuthorAvatar(q: any): string {
  const found = MOCK_USERS.find((u: any) => u.id === q.author_user_id)
  return found?.avatar || 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&auto=format&fit=crop&q=80'
}

function getTopicName(q: any): string {
  const t = topics.value.find((topic) => topic.id === q.topic_id)
  return t ? t.name : 'Kỹ Thuật'
}

function getCategoryName(q: any): string | null {
  const t = topics.value.find((topic) => topic.id === q.topic_id)
  const c = t?.categories.find((cat) => cat.id === q.category_id)
  return c ? c.name : 'Tài liệu'
}

const displayedQuestions = computed(() => {
  let list: any[] = questions.value.length ? [...questions.value] : [...MOCK_QA_QUESTIONS]
  if (selectedTopicId.value) {
    list = list.filter((q: any) => q.topic_id === selectedTopicId.value)
  }
  if (selectedCategoryId.value) {
    list = list.filter((q: any) => q.category_id === selectedCategoryId.value)
  }
  if (qaFilter.value === 'answered') {
    list = list.filter((q: any) => getAnswerCount(q) > 0)
  } else if (qaFilter.value === 'unanswered') {
    list = list.filter((q: any) => getAnswerCount(q) === 0)
  }
  if (qaSort.value === 'votes') {
    list.sort((a: any, b: any) => getScore(b) - getScore(a))
  }
  return list
})

watch(
  () => props.initialTab,
  (newTab) => {
    if (newTab) activeTab.value = (newTab === 'home' ? 'questions' : newTab) as SquareTab
  }
)

onMounted(refresh)
</script>

<template>
  <div class="min-h-screen text-slate-900 antialiased flex flex-col justify-between bg-[#F8FAFC] font-sans">
    
    <!-- TOP BLUE NAVBAR (CHUẨN 100% ẢNH THỨ 2 & 09-qa-list.html) -->
    <header class="sticky top-0 z-40 bg-[#1D4ED8] text-white shadow-xs flex-shrink-0">
      <div class="max-w-[1440px] mx-auto px-4 lg:px-8 h-14 flex items-center justify-between gap-4">
        
        <!-- Brand & Sub Nav Links -->
        <div class="flex items-center gap-6 overflow-x-auto">
          <!-- Back to Welcome button -->
          <button
            type="button"
            class="flex items-center gap-2 flex-shrink-0 cursor-pointer border-none bg-transparent text-white"
            title="Quay lại cổng Portal"
            @click="emit('back')"
          >
            <div class="w-7 h-7 rounded-full bg-white text-blue-700 flex items-center justify-center font-bold text-xs">
              <span class="material-symbols-outlined text-sm">more_horiz</span>
            </div>
            <span class="text-sm font-extrabold tracking-wider">BASE.VN</span>
          </button>

          <!-- Main Knowledge Tabs (Đã bỏ Trang chủ trùng lặp, Câu hỏi là phân hệ chính) -->
          <nav class="flex items-center gap-1 text-xs font-semibold text-white/80 whitespace-nowrap">
            <button
              type="button"
              class="px-3 py-4 cursor-pointer border-none bg-transparent transition"
              :class="activeTab === 'questions' ? 'text-white font-bold border-b-2 border-white' : 'text-white/80 hover:text-white'"
              @click="selectTab('questions')"
            >
              Câu hỏi
            </button>
            <button
              type="button"
              class="px-3 py-4 cursor-pointer border-none bg-transparent transition"
              :class="activeTab === 'knowledge' ? 'text-white font-bold border-b-2 border-white' : 'text-white/80 hover:text-white'"
              @click="selectTab('knowledge')"
            >
              Tri thức
            </button>
            <button
              type="button"
              class="px-3 py-4 cursor-pointer border-none bg-transparent transition"
              :class="activeTab === 'library' ? 'text-white font-bold border-b-2 border-white' : 'text-white/80 hover:text-white'"
              @click="selectTab('library')"
            >
              Thư viện
            </button>
            <button
              type="button"
              class="px-3 py-4 cursor-pointer border-none bg-transparent transition"
              :class="activeTab === 'reward' ? 'text-white font-bold border-b-2 border-white' : 'text-white/80 hover:text-white'"
              @click="selectTab('reward')"
            >
              Bảng xếp hạng
            </button>
            <button
              type="button"
              class="px-3 py-4 cursor-pointer border-none bg-transparent transition"
              :class="activeTab === 'profile' ? 'text-white font-bold border-b-2 border-white' : 'text-white/80 hover:text-white'"
              @click="selectTab('profile')"
            >
              Cá nhân
            </button>
            <button
              type="button"
              class="px-3 py-4 cursor-pointer border-none bg-transparent transition"
              :class="activeTab === 'admin' ? 'text-white font-bold border-b-2 border-white' : 'text-white/80 hover:text-white'"
              @click="selectTab('admin')"
            >
              Tùy chỉnh
            </button>
            <button
              type="button"
              class="px-3 py-4 cursor-pointer border-none bg-transparent transition"
              :class="activeTab === 'search' ? 'text-white font-bold border-b-2 border-white' : 'text-white/80 hover:text-white'"
              @click="selectTab('search')"
            >
              Tìm kiếm
            </button>
          </nav>
        </div>

        <!-- Right Search, Avatar, Logout -->
        <div class="flex items-center gap-3 flex-shrink-0">
          <div class="relative hidden sm:block">
            <input
              v-model="globalSearchQuery"
              type="text"
              placeholder="Enter để tìm kiếm"
              class="w-56 pl-3 pr-8 py-1.5 bg-blue-800/60 focus:bg-white text-white focus:text-slate-900 placeholder-white/60 text-xs rounded-full border border-blue-400/40 focus:border-white outline-none transition"
              @keyup.enter="handleGlobalSearch"
            >
            <span
              class="material-symbols-outlined absolute right-2.5 top-2 text-white/60 text-base cursor-pointer"
              @click="handleGlobalSearch"
            >
              search
            </span>
          </div>

          <button
            type="button"
            class="p-0.5 rounded-full hover:ring-2 hover:ring-white border-none bg-transparent cursor-pointer"
            title="Xem hồ sơ cá nhân"
            @click="selectTab('profile')"
          >
            <img
              src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80"
              alt="Avatar"
              class="w-7 h-7 rounded-full object-cover"
            >
          </button>

          <button
            type="button"
            class="p-1 text-white/80 hover:text-white cursor-pointer border-none bg-transparent"
            title="Đăng xuất khỏi hệ thống"
            @click="emit('logout')"
          >
            <span class="material-symbols-outlined text-base">logout</span>
          </button>
        </div>

      </div>
    </header>

    <!-- SUB-VIEWS CONTAINER (GIỮ NGUYÊN MENU TRÊN CÙNG, CHUYỂN TAB MƯỢT MÀ) -->
    
    <!-- MODALS / SUB-SCREENS CHO QUESTIONS -->
    <DocumentDetailView
      v-if="showDocumentDetail && selectedItem"
      :item="selectedItem"
      :token="token"
      @back="showDocumentDetail = false"
      @refresh="refresh"
      @ask="showQuestionComposer = true"
    />
    
    <AskQuestionView
      v-else-if="showQuestionComposer"
      :topics="topics"
      :saving="saving"
      :error-message="errorMessage"
      @back="showQuestionComposer = false"
      @submit="submitQuestion"
    />
    
    <QuestionDetailView
      v-else-if="showQuestionDetail && selectedQuestion"
      :question="selectedQuestion"
      :topics="topics"
      :saving="saving"
      :error-message="errorMessage"
      @back="showQuestionDetail = false"
      @vote-question="voteQuestion"
      @vote-answer="voteAnswer"
      @answer="createAnswerFromDetail"
    />

    <!-- TAB: CÂU HỎI Q&A & QUẢN TRỊ TRI THỨC (09-qa-list.html) -->
    <main
      v-else-if="activeTab === 'questions'"
      class="max-w-[1440px] mx-auto px-4 lg:px-8 py-6 flex-1 w-full grid grid-cols-1 md:grid-cols-12 gap-6"
    >
      <!-- LEFT SIDEBAR: ĐẶT CÂU HỎI & BỘ LỌC CHUẨN 09-qa-list.html -->
      <aside class="md:col-span-3 space-y-4 text-xs">
        <!-- Big Blue Ask Question Button -->
        <button
          type="button"
          class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider text-center block rounded-md shadow-xs transition cursor-pointer border-none"
          @click="showQuestionComposer = true"
        >
          ĐẶT CÂU HỎI
        </button>

        <!-- Left Navigation Menu Items (Chuẩn 09-qa-list.html) -->
        <nav class="bg-white rounded-lg border border-slate-200 p-2 shadow-xs space-y-0.5 text-xs font-semibold text-slate-700">
          <button
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-md border-none cursor-pointer text-left transition"
            :class="qaFilter === 'following' ? 'bg-slate-100 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="qaFilter = 'following'"
          >
            <span class="material-symbols-outlined text-base" :class="qaFilter === 'following' ? 'text-blue-600' : 'text-slate-400'">help</span>
            <span>Đang theo dõi</span>
          </button>
          <button
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-md border-none cursor-pointer text-left transition"
            :class="qaFilter === 'mine' ? 'bg-slate-100 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="qaFilter = 'mine'"
          >
            <span class="material-symbols-outlined text-base" :class="qaFilter === 'mine' ? 'text-blue-600' : 'text-slate-400'">person</span>
            <span>Câu hỏi của tôi</span>
          </button>
          <button
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-md border-none cursor-pointer text-left transition"
            :class="qaFilter === 'assigned' ? 'bg-slate-100 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="qaFilter = 'assigned'"
          >
            <span class="material-symbols-outlined text-base" :class="qaFilter === 'assigned' ? 'text-blue-600' : 'text-slate-400'">task_alt</span>
            <span>Giao cho tôi</span>
          </button>
          <button
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-md border-none cursor-pointer text-left transition"
            :class="qaFilter === 'answered' ? 'bg-slate-100 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="qaFilter = 'answered'"
          >
            <span class="material-symbols-outlined text-base" :class="qaFilter === 'answered' ? 'text-blue-600' : 'text-slate-400'">check_circle</span>
            <span>Đã được trả lời</span>
          </button>
          <button
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-md border-none cursor-pointer text-left transition"
            :class="qaFilter === 'unanswered' ? 'bg-slate-100 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="qaFilter = 'unanswered'"
          >
            <span class="material-symbols-outlined text-base" :class="qaFilter === 'unanswered' ? 'text-blue-600' : 'text-slate-400'">help_outline</span>
            <span>Chưa được trả lời</span>
          </button>
          
          <div class="pt-2 my-1 border-t border-slate-100"></div>

          <button
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 rounded-md border-none bg-transparent cursor-pointer text-left text-slate-700 text-xs"
            @click="showAlert('Xem các câu hỏi bạn đã bình chọn.')"
          >
            <span class="material-symbols-outlined text-base text-slate-400">how_to_vote</span>
            <span>Bình chọn của tôi</span>
          </button>
          <button
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 rounded-md border-none bg-transparent cursor-pointer text-left text-slate-700 text-xs"
            @click="showAlert('Xem các câu hỏi bạn đã đánh dấu lưu trữ.')"
          >
            <span class="material-symbols-outlined text-base text-slate-400">bookmark</span>
            <span>Đánh dấu của tôi</span>
          </button>
          <button
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 rounded-md border-none bg-transparent cursor-pointer text-left text-slate-700 text-xs"
            @click="showAlert('Xem các câu hỏi từ nhóm làm việc của bạn.')"
          >
            <span class="material-symbols-outlined text-base text-slate-400">groups</span>
            <span>Từ nhóm của tôi</span>
          </button>
        </nav>

        <!-- Taxonomy Topics Filter -->
        <div class="bg-white rounded-lg border border-slate-200 p-3 shadow-xs space-y-1">
          <span class="font-bold text-slate-400 uppercase text-[10px] tracking-wider px-2 block mb-1">CHỦ ĐỀ TRI THỨC</span>
          <button
            type="button"
            class="w-full text-left px-2.5 py-1.5 rounded transition cursor-pointer border-none"
            :class="!selectedTopicId ? 'bg-blue-50 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="selectedTopicId = undefined"
          >
            Tất cả chủ đề
          </button>
          
          <div v-for="topic in topics" :key="topic.id">
            <button
              type="button"
              class="w-full text-left px-2.5 py-1.5 rounded transition cursor-pointer border-none font-medium"
              :class="selectedTopicId === topic.id ? 'bg-blue-50 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
              @click="selectedTopicId = topic.id"
            >
              {{ topic.name }}
            </button>
            <div v-if="selectedTopicId === topic.id" class="pl-3 space-y-1 mt-1">
              <button
                v-for="cat in topic.categories"
                :key="cat.id"
                type="button"
                class="block w-full text-left text-[11px] py-1 px-2 rounded hover:text-blue-700 border-none bg-transparent cursor-pointer"
                :class="selectedCategoryId === cat.id ? 'text-blue-700 font-bold bg-blue-50/50' : 'text-slate-500'"
                @click="selectedCategoryId = cat.id"
              >
                › {{ cat.name }}
              </button>
            </div>
          </div>
        </div>
      </aside>

      <!-- CENTER: QUESTIONS STREAM (CHUẨN 09-qa-list.html) -->
      <section class="md:col-span-6 space-y-4 text-xs">
        <!-- Header & Sort Tabs -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-slate-200 gap-2">
          <div>
            <h1 class="text-base font-bold text-slate-900 leading-tight">Câu hỏi</h1>
            <span class="text-xs text-slate-500 font-mono">{{ displayedQuestions.length }} câu hỏi</span>
          </div>

          <div class="flex items-center gap-4 text-xs font-bold text-slate-500">
            <button
              type="button"
              class="border-none bg-transparent cursor-pointer pb-1 transition font-bold"
              :class="qaSort === 'latest' ? 'text-blue-600 border-b-2 border-blue-600' : 'hover:text-slate-900'"
              @click="qaSort = 'latest'"
            >
              MỚI NHẤT
            </button>
            <button
              type="button"
              class="border-none bg-transparent cursor-pointer pb-1 transition font-bold"
              :class="qaSort === 'trending' ? 'text-blue-600 border-b-2 border-blue-600' : 'hover:text-slate-900'"
              @click="qaSort = 'trending'"
            >
              THỊNH HÀNH
            </button>
            <button
              type="button"
              class="border-none bg-transparent cursor-pointer pb-1 transition font-bold"
              :class="qaSort === 'votes' ? 'text-blue-600 border-b-2 border-blue-600' : 'hover:text-slate-900'"
              @click="qaSort = 'votes'"
            >
              BÌNH CHỌN
            </button>
            <button
              type="button"
              class="border-none bg-transparent cursor-pointer pb-1 transition font-bold"
              :class="qaSort === 'rewards' ? 'text-blue-600 border-b-2 border-blue-600' : 'hover:text-slate-900'"
              @click="qaSort = 'rewards'"
            >
              PHẦN THƯỞNG
            </button>
          </div>
        </div>

        <div v-if="errorMessage" class="p-3 rounded bg-rose-50 border border-rose-200 text-rose-700 text-xs">
          {{ errorMessage }}
        </div>

        <!-- Questions Stream Cards -->
        <div class="space-y-4">
          <article
            v-for="q in displayedQuestions"
            :key="q.id"
            class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs flex gap-4 hover:border-blue-400 transition"
          >
            <!-- Left Vote Box (Chuẩn 09-qa-list.html) -->
            <div class="flex flex-col items-center justify-start min-w-[52px] border-r border-slate-100 pr-3 flex-shrink-0">
              <span class="text-2xl font-bold text-slate-800">{{ getScore(q) }}</span>
              <button
                type="button"
                class="text-[10px] font-bold text-slate-400 hover:text-blue-600 flex items-center gap-0.5 mt-0.5 uppercase border-none bg-transparent cursor-pointer p-0"
                @click="voteQuestion(q.id)"
              >
                ▲ VOTE
              </button>
            </div>

            <!-- Content Body -->
            <div class="flex-1 space-y-1.5 min-w-0">
              <div class="flex items-center justify-between text-xs">
                <div class="flex items-center gap-2 flex-wrap">
                  <img
                    :src="getAuthorAvatar(q)"
                    alt="Author"
                    class="w-5 h-5 rounded-full object-cover border border-slate-200"
                  >
                  <span class="font-bold text-slate-800">{{ getAuthorName(q) }}</span>
                  <span class="text-[11px] text-slate-500">⚪ 11</span>
                  <span class="text-[11px] text-amber-600 font-semibold">🟡 20</span>
                  <span class="text-slate-400">• Đặt câu hỏi lúc {{ getFormattedTime(q) }}</span>
                </div>
                <span
                  class="px-2 py-0.5 bg-emerald-50 text-emerald-700 font-bold rounded text-[11px] flex items-center gap-1 border border-emerald-200 flex-shrink-0"
                >
                  <span class="material-symbols-outlined text-xs">chat_bubble</span>
                  {{ getAnswerCount(q) }} câu trả lời
                </span>
              </div>

              <h2
                class="text-sm font-bold text-blue-600 hover:underline leading-snug cursor-pointer"
                @click="selectQuestion(q.id)"
              >
                {{ q.title }}
              </h2>

              <p class="text-xs text-slate-600 leading-relaxed line-clamp-2">
                {{ getBodySnippet(q) }}
              </p>

              <div class="flex items-center gap-2 pt-2 text-[11px] flex-wrap">
                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 font-semibold rounded">
                  {{ getTopicName(q) }}
                </span>
                <span v-if="getCategoryName(q)" class="px-2 py-0.5 bg-blue-50 text-blue-700 font-semibold rounded">
                  {{ getCategoryName(q) }}
                </span>
                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 font-bold rounded">
                  quychuan
                </span>
                <span class="text-slate-400 ml-auto">
                  {{ getAnswerCount(q) }} câu trả lời • 3 lượt xem
                </span>
              </div>

              <div
                v-if="getAnswerCount(q) === 0"
                class="pt-1 flex items-center gap-1.5 text-xs text-rose-600 font-medium"
              >
                <span class="material-symbols-outlined text-sm">error</span>
                <span>Chưa có câu trả lời được chấp nhận</span>
              </div>
            </div>
          </article>
        </div>
      </section>

      <!-- RIGHT SIDEBAR: KNOWLEDGE PROVENANCE & WIDGETS (ĐÁP ỨNG UNIT TEST LearningView.spec.ts) -->
      <aside class="md:col-span-3 space-y-4 text-xs">
        <!-- Provenance Block -->
        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-3">
          <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="font-bold text-slate-400 uppercase text-[10px] tracking-wider">KNOWLEDGE PROVENANCE</span>
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          </div>

          <div v-if="items.length > 0 && items[0]">
            <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ items[0].title }}</h3>
            <p class="text-slate-500 text-[11px] mt-1">{{ items[0].summary || 'Tài liệu hướng dẫn bắt đầu với Graph RAG.' }}</p>
            
            <div class="space-y-1.5 pt-3 mt-3 border-t border-slate-100 font-mono text-[10px]">
              <div class="text-slate-500">
                <span class="font-bold text-slate-700">Source ID:</span>
                <span class="block truncate text-slate-600">{{ items[0].id }}</span>
              </div>
              <div class="text-slate-500">
                <span class="font-bold text-slate-700">Version ID:</span>
                <span class="block truncate text-slate-600">{{ items[0].current_version_id }}</span>
              </div>
            </div>

            <div class="p-2.5 rounded bg-amber-50 border border-amber-200 text-amber-800 text-[11px] leading-relaxed mt-3">
              Knowledge Graph sẽ chỉ tạo projection có provenance từ source/version này. AI chưa tự ghi dữ liệu vào graph.
            </div>
          </div>
          
          <div v-else class="text-slate-400 italic">
            Đang tải dữ liệu tri thức...
          </div>
        </div>

        <!-- Nhãn phổ biến (Chuẩn 09-qa-list.html) -->
        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-2 text-xs">
          <h3 class="font-bold text-slate-900 text-xs">Nhãn phổ biến</h3>
          <div class="flex flex-wrap gap-2 pt-1">
            <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold rounded text-xs border border-emerald-200 cursor-pointer">
              quychuan <span class="text-emerald-500 font-normal">× 1</span>
            </span>
            <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-medium rounded text-xs hover:bg-slate-200 cursor-pointer">
              laravel <span class="text-slate-400 font-normal">× 8</span>
            </span>
            <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-medium rounded text-xs hover:bg-slate-200 cursor-pointer">
              microservices <span class="text-slate-400 font-normal">× 5</span>
            </span>
          </div>
        </div>

        <!-- Top Contributors -->
        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-2">
          <h3 class="font-bold text-slate-900 text-xs">Chuyên gia tích cực</h3>
          <div class="flex items-center gap-2 p-1.5 rounded hover:bg-slate-50 cursor-pointer">
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80" class="w-6 h-6 rounded-full object-cover">
            <div>
              <span class="font-bold text-slate-800 block leading-tight">Võ Thành Luân</span>
              <span class="text-[10px] text-slate-400">1,450 điểm</span>
            </div>
          </div>
          <div class="flex items-center gap-2 p-1.5 rounded hover:bg-slate-50 cursor-pointer">
            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80" class="w-6 h-6 rounded-full object-cover">
            <div>
              <span class="font-bold text-slate-800 block leading-tight">Phạm Phát</span>
              <span class="text-[10px] text-slate-400">1,120 điểm</span>
            </div>
          </div>
        </div>
      </aside>
    </main>

    <!-- TAB 3: TRI THỨC - KNOWLEDGE GRAPH EXPLORER (14-knowledge-graph-explorer.html) -->
    <main
      v-else-if="activeTab === 'knowledge'"
      class="max-w-[1440px] mx-auto w-full px-4 lg:px-8 py-4 flex-1 flex gap-4 h-[calc(100vh-61px)] overflow-hidden"
    >
      <section class="flex-1 bg-white rounded-lg border border-slate-200 flex flex-col shadow-xs overflow-hidden">
        <div class="p-3 border-b border-slate-100 flex flex-wrap items-center justify-between text-xs bg-slate-50">
          <div class="flex items-center gap-4 font-semibold">
            <span class="font-bold text-slate-800 uppercase text-[11px]">Node:</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span> Chuyên gia</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span> Chủ đề</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> Danh mục</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Tài liệu</span>
          </div>
          <span class="text-slate-400 font-mono text-[11px]">Neo4j Cypher Engine</span>
        </div>
        
        <div class="flex-1 bg-slate-50/40 relative overflow-auto">
          <svg class="w-full h-full min-h-[460px]" viewBox="0 0 900 480">
            <g class="edges">
              <g v-for="(link, idx) in graphLinks" :key="idx">
                <line
                  :x1="getNode(link.source)?.x"
                  :y1="getNode(link.source)?.y"
                  :x2="getNode(link.target)?.x"
                  :y2="getNode(link.target)?.y"
                  stroke="#cbd5e1"
                  stroke-width="2"
                  stroke-dasharray="4 2"
                />
                <text
                  :x="((getNode(link.source)?.x || 0) + (getNode(link.target)?.x || 0)) / 2"
                  :y="((getNode(link.source)?.y || 0) + (getNode(link.target)?.y || 0)) / 2 - 4"
                  fill="#94a3b8"
                  font-size="9"
                  font-weight="bold"
                  text-anchor="middle"
                >
                  {{ link.label }}
                </text>
              </g>
            </g>
            <g class="nodes">
              <g
                v-for="node in graphNodes"
                :key="node.id"
                class="cursor-pointer transition-transform"
                @click="selectedGraphNode = node"
              >
                <circle
                  v-if="selectedGraphNode?.id === node.id"
                  :cx="node.x"
                  :cy="node.y"
                  :r="node.r + 6"
                  fill="none"
                  stroke="#3b82f6"
                  stroke-width="2.5"
                />
                <circle
                  :cx="node.x"
                  :cy="node.y"
                  :r="node.r"
                  :fill="node.color"
                  stroke="#ffffff"
                  stroke-width="2.5"
                />
                <text
                  :x="node.x"
                  :y="node.y + node.r + 14"
                  fill="#1e293b"
                  font-size="11"
                  font-weight="bold"
                  text-anchor="middle"
                >
                  {{ node.label }}
                </text>
              </g>
            </g>
          </svg>
        </div>
      </section>

      <!-- Node Inspector -->
      <aside v-if="selectedGraphNode" class="w-80 bg-white rounded-lg border border-slate-200 p-4 space-y-3 text-xs flex-shrink-0">
        <span class="font-bold text-slate-400 uppercase text-[10px]">CHI TIẾT THỰC THỂ</span>
        <h2 class="text-sm font-bold text-slate-900">{{ selectedGraphNode.label }}</h2>
        <p class="text-slate-600">{{ selectedGraphNode.role }}</p>
        <div class="pt-3 border-t border-slate-100">
          <button
            type="button"
            class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-md shadow-xs border-none cursor-pointer"
            @click="selectTab('questions')"
          >
            Xem câu hỏi liên quan
          </button>
        </div>
      </aside>
    </main>

    <!-- TAB 4: THƯ VIỆN TÀI LIỆU BASE LIBRARY (08-learning-home.html) -->
    <main
      v-else-if="activeTab === 'library'"
      class="max-w-[1440px] mx-auto px-4 lg:px-8 py-6 flex-1 w-full grid grid-cols-1 md:grid-cols-12 gap-6"
    >
      <aside class="md:col-span-3 space-y-4 text-xs">
        <button
          type="button"
          class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider text-center block rounded-md shadow-xs border-none cursor-pointer"
          @click="showAlert('Mở hộp thoại tải lên tài liệu mới (PDF, DOCX, XLSX)')"
        >
          TẢI LÊN TÀI LIỆU
        </button>

        <div class="bg-white rounded-lg border border-slate-200 p-3 shadow-xs space-y-1">
          <span class="font-bold text-slate-400 uppercase text-[10px] tracking-wider px-2 block mb-1">CHỦ ĐỀ TÀI LIỆU</span>
          <button
            type="button"
            class="w-full flex items-center justify-between p-2 rounded transition cursor-pointer border-none text-left"
            :class="libraryCategory === 'ALL' ? 'bg-blue-50 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="libraryCategory = 'ALL'"
          >
            <span>Tất cả tài liệu</span>
            <span class="text-slate-400 font-mono">{{ libraryDocs.length }}</span>
          </button>
          <button
            type="button"
            class="w-full flex items-center justify-between p-2 rounded transition cursor-pointer border-none text-left"
            :class="libraryCategory === 'Backend' ? 'bg-blue-50 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="libraryCategory = 'Backend'"
          >
            <span>Backend &amp; AI</span>
            <span class="text-slate-400 font-mono">1</span>
          </button>
          <button
            type="button"
            class="w-full flex items-center justify-between p-2 rounded transition cursor-pointer border-none text-left"
            :class="libraryCategory === 'Kỹ thuật' ? 'bg-blue-50 text-blue-700 font-bold' : 'hover:bg-slate-50 text-slate-700 bg-transparent'"
            @click="libraryCategory = 'Kỹ thuật'"
          >
            <span>Kỹ thuật Phần mềm</span>
            <span class="text-slate-400 font-mono">1</span>
          </button>
        </div>
      </aside>

      <section class="md:col-span-9 space-y-4 text-xs">
        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
          <div>
            <h1 class="text-base font-bold text-slate-900 leading-tight">Thư viện tài liệu học tập &amp; Tri thức</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kho văn bản đã xuất bản và lập chỉ mục vào Cây tri thức AI</p>
          </div>
          <span class="text-xs text-slate-500">{{ libraryDocs.length }} tài liệu</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="doc in libraryDocs"
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
                <span v-if="doc.isIndexed" class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[10px] font-semibold flex items-center gap-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                  Đã chỉ mục AI
                </span>
              </div>
              <h2 class="font-bold text-slate-900 text-sm leading-snug">{{ doc.title }}</h2>
              <div class="text-[11px] text-slate-500">Tác giả: <strong>{{ doc.author }}</strong> • {{ doc.date }}</div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-slate-500">
              <span class="text-[11px]">👁️ {{ doc.views }} • ⬇️ {{ doc.downloads }}</span>
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

    <!-- TAB 5: BẢNG XẾP HẠNG BASE REWARD (12-leaderboard.html) -->
    <main
      v-else-if="activeTab === 'reward'"
      class="max-w-[1440px] mx-auto px-4 lg:px-8 py-8 flex-1 w-full space-y-8 text-xs"
    >
      <div class="bg-white rounded-lg border border-slate-200 p-8 shadow-xs text-center">
        <h1 class="text-xl font-bold text-slate-900">Vinh danh Đóng góp Tri thức &amp; Base Reward</h1>
        <p class="text-slate-500 mt-1">Ghi nhận các thành viên tích cực chia sẻ tài liệu và giải đáp câu hỏi chuyên môn</p>

        <div class="flex flex-wrap items-end justify-center gap-8 pt-6 pb-2">
          <!-- Rank 2 -->
          <div class="flex flex-col items-center">
            <div class="w-18 h-18 rounded-full border-3 border-slate-300 overflow-hidden shadow-sm">
              <img :src="leaderboard[1]?.avatar" class="w-full h-full object-cover">
            </div>
            <span class="text-xs font-bold mt-1.5">{{ leaderboard[1]?.name }}</span>
            <span class="text-[11px] text-blue-600 font-bold">🥈 {{ leaderboard[1]?.points }}</span>
          </div>

          <!-- Rank 1: Quán quân -->
          <div class="flex flex-col items-center -mt-4">
            <div class="w-24 h-24 rounded-full border-4 border-amber-400 ring-4 ring-amber-100 overflow-hidden shadow-md">
              <img :src="leaderboard[0]?.avatar" class="w-full h-full object-cover">
            </div>
            <span class="text-sm font-extrabold mt-1.5">{{ leaderboard[0]?.name }}</span>
            <span class="text-xs text-amber-600 font-black">👑 {{ leaderboard[0]?.points }}</span>
          </div>

          <!-- Rank 3 -->
          <div class="flex flex-col items-center">
            <div class="w-18 h-18 rounded-full border-2 border-amber-600 overflow-hidden shadow-sm">
              <img :src="leaderboard[2]?.avatar" class="w-full h-full object-cover">
            </div>
            <span class="text-xs font-bold mt-1.5">{{ leaderboard[2]?.name }}</span>
            <span class="text-[11px] text-amber-700 font-bold">🥉 {{ leaderboard[2]?.points }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-slate-200 overflow-hidden shadow-xs">
        <table class="w-full text-left">
          <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase border-b border-slate-200">
            <tr>
              <th class="px-4 py-3 text-center w-16">Hạng</th>
              <th class="px-4 py-3">Thành viên</th>
              <th class="px-4 py-3">Chức vụ</th>
              <th class="px-4 py-3">Danh hiệu</th>
              <th class="px-4 py-3 text-right">Tổng điểm</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in leaderboard" :key="item.rank" class="hover:bg-slate-50">
              <td class="px-4 py-3 text-center font-bold">#{{ item.rank }}</td>
              <td class="px-4 py-3 font-bold text-slate-900 flex items-center gap-2">
                <img :src="item.avatar" class="w-6 h-6 rounded-full object-cover">
                <span>{{ item.name }}</span>
              </td>
              <td class="px-4 py-3 text-slate-600">{{ item.role }}</td>
              <td class="px-4 py-3"><span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-semibold">{{ item.badge }}</span></td>
              <td class="px-4 py-3 text-right font-extrabold text-blue-700">{{ item.points }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>

    <!-- TAB 6: HỒ SƠ CÁ NHÂN (06-profile.html) -->
    <main
      v-else-if="activeTab === 'profile'"
      class="max-w-4xl mx-auto px-4 lg:px-8 py-8 flex-1 w-full space-y-6 text-xs"
    >
      <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-xs space-y-4">
        <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
          <img
            src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120"
            class="w-16 h-16 rounded-full object-cover border-2 border-slate-200"
          >
          <div>
            <h1 class="text-lg font-bold text-slate-900">{{ props.user?.display_name || 'Sang Phạm' }}</h1>
            <p class="text-slate-500">{{ props.user?.email || 'sang.pham@openhello.vn' }}</p>
            <span class="mt-2 inline-block px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 font-bold text-[10px]">
              Tài khoản doanh nghiệp OpenHello
            </span>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="p-3 bg-slate-50 rounded border border-slate-100">
            <span class="text-slate-400 block text-[10px]">Mã User ID</span>
            <span class="font-bold font-mono">{{ props.user?.id || 'user-sangpt' }}</span>
          </div>
          <div class="p-3 bg-slate-50 rounded border border-slate-100">
            <span class="text-slate-400 block text-[10px]">Đơn vị Tổ chức</span>
            <span class="font-bold font-mono">{{ props.user?.organization_id || 'org-openhello' }}</span>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
          <span>Bảo mật: Mật khẩu &amp; 2FA đã thiết lập an toàn</span>
          <button
            type="button"
            class="px-3 py-1.5 rounded bg-blue-600 hover:bg-blue-700 text-white font-semibold cursor-pointer border-none"
            @click="showAlert('Gửi OTP đổi mật khẩu về email của bạn.')"
          >
            Đổi mật khẩu
          </button>
        </div>
      </div>
    </main>

    <!-- TAB 7: QUẢN TRỊ TÙY CHỈNH (07-admin-social.html) -->
    <main
      v-else-if="activeTab === 'admin'"
      class="max-w-[1440px] mx-auto px-4 lg:px-8 py-6 flex-1 w-full space-y-6 text-xs"
    >
      <div class="flex justify-between items-center pb-2 border-b border-slate-200">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Hàng đợi kiểm duyệt nội dung Inside &amp; Square</h1>
          <p class="text-slate-500 mt-0.5">Giám sát bài viết, câu hỏi Q&amp;A và tài liệu được gắn cờ tự động</p>
        </div>
        <button
          type="button"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-md shadow-xs border-none cursor-pointer"
          @click="showAlert('Đang trích xuất báo cáo kiểm duyệt tuần!')"
        >
          Xuất báo cáo
        </button>
      </div>

      <div class="grid grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs"><span class="text-slate-500 block">Chờ kiểm duyệt</span><span class="text-2xl font-black text-amber-500">3</span></div>
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs"><span class="text-slate-500 block">Đã duyệt tuần</span><span class="text-2xl font-black text-emerald-600">128</span></div>
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs"><span class="text-slate-500 block">Gắn cờ vi phạm</span><span class="text-2xl font-black text-rose-500">0</span></div>
        <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs"><span class="text-slate-500 block">Tổng nội dung</span><span class="text-2xl font-black text-blue-600">450</span></div>
      </div>
    </main>

    <!-- TAB 8: TÌM KIẾM AI HYBRID SEARCH (15-hybrid-search.html) -->
    <main
      v-else-if="activeTab === 'search'"
      class="max-w-4xl mx-auto px-4 lg:px-8 py-6 flex-1 w-full space-y-4 text-xs"
    >
      <div class="bg-white border border-slate-200 rounded-lg p-6 shadow-xs text-center space-y-3">
        <h1 class="text-xl font-bold text-slate-900">Tìm kiếm Tri thức &amp; Chuyên gia AI</h1>
        <p class="text-slate-500">Tích hợp Vector Embeddings + Neo4j Graph RAG</p>
        <div class="relative max-w-md mx-auto">
          <input
            v-model="searchInput"
            type="text"
            class="w-full pl-4 pr-24 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs outline-none"
            placeholder="Nhập nội dung cần tra cứu..."
          >
          <button
            type="button"
            class="absolute right-1 top-1 px-4 py-1.5 bg-blue-600 text-white rounded-md font-bold text-xs border-none cursor-pointer"
          >
            Tìm
          </button>
        </div>
      </div>

      <div class="space-y-3">
        <article
          v-for="item in searchResults"
          :key="item.id"
          class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-2 hover:border-blue-400 transition"
        >
          <div class="flex justify-between items-center">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="item.typeColor">{{ item.type }}</span>
            <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-bold">{{ item.matchScore }}</span>
          </div>
          <h2 class="font-bold text-slate-900 text-sm hover:text-blue-600 cursor-pointer">{{ item.title }}</h2>
          <p class="text-slate-600">{{ item.snippet }}</p>
          <div class="pt-2 border-t border-slate-50 flex justify-between text-[11px] text-slate-400">
            <span>{{ item.relation }}</span>
            <button
              type="button"
              class="text-blue-600 font-semibold hover:underline border-none bg-transparent cursor-pointer"
              @click="item.type.includes('Tài liệu') ? selectTab('library') : selectTab('questions')"
            >
              Mở chi tiết →
            </button>
          </div>
        </article>
      </div>
    </main>

  </div>
</template>
