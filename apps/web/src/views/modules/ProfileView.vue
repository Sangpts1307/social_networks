<script setup lang="ts">
import { ref, computed } from 'vue'
import { store } from '../../mock/store'

const activeTab = ref<'posts' | 'questions' | 'saved'>('posts')

const myPosts = computed(() => {
  return store.posts.filter(p => p.author === store.currentUser.name)
})

const myQuestions = computed(() => {
  return store.questions.filter(q => q.author === store.currentUser.name)
})

const savedPosts = computed(() => {
  return store.posts.filter(p => p.isSaved)
})
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 py-6 space-y-6">
    
    <!-- User Info Card -->
    <div class="bg-white border border-slate-200 rounded-md p-6 flex flex-col md:flex-row md:items-center justify-between gap-5">
      <div class="flex items-start sm:items-center gap-4">
        <img
          :src="store.currentUser.avatar"
          :alt="store.currentUser.name"
          class="w-16 h-16 rounded-md object-cover border border-slate-200"
        >
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-lg font-bold text-slate-900 leading-tight">{{ store.currentUser.name }}</h1>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
              {{ store.currentUser.roleTitle }}
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-1">{{ store.currentUser.department }} • {{ store.currentUser.email }}</p>

          <!-- Skills List -->
          <div class="flex items-center gap-1.5 mt-2.5 flex-wrap">
            <span
              v-for="skill in store.currentUser.skills"
              :key="skill"
              class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-50 border border-slate-200 text-slate-600"
            >
              {{ skill }}
            </span>
          </div>
        </div>
      </div>

      <!-- Quick Metrics -->
      <div class="flex items-center gap-3 pt-3 md:pt-0 border-t md:border-t-0 border-slate-100">
        <div class="p-3 bg-slate-50 rounded border border-slate-200 text-center min-w-[80px]">
          <span class="text-[10px] font-semibold text-slate-400 uppercase block">Bài viết</span>
          <span class="text-base font-bold text-slate-900">{{ store.currentUser.postsCount }}</span>
        </div>
        <div class="p-3 bg-slate-50 rounded border border-slate-200 text-center min-w-[80px]">
          <span class="text-[10px] font-semibold text-slate-400 uppercase block">Câu trả lời</span>
          <span class="text-base font-bold text-slate-900">{{ store.currentUser.answersCount }}</span>
        </div>
        <div class="p-3 bg-slate-50 rounded border border-slate-200 text-center min-w-[80px]">
          <span class="text-[10px] font-semibold text-slate-400 uppercase block">Xu thưởng</span>
          <span class="text-base font-bold text-amber-600">{{ store.currentUser.coins }}</span>
        </div>
      </div>
    </div>

    <!-- Activity Tabs -->
    <div class="bg-white border border-slate-200 rounded-md p-4 space-y-4">
      <div class="flex items-center gap-1 border-b border-slate-100 pb-2">
        <button
          type="button"
          class="px-3 py-1.5 rounded text-xs font-semibold cursor-pointer border-none transition"
          :class="activeTab === 'posts' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
          @click="activeTab = 'posts'"
        >
          Bài viết của tôi ({{ myPosts.length }})
        </button>
        <button
          type="button"
          class="px-3 py-1.5 rounded text-xs font-semibold cursor-pointer border-none transition"
          :class="activeTab === 'questions' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
          @click="activeTab = 'questions'"
        >
          Câu hỏi tri thức ({{ myQuestions.length }})
        </button>
        <button
          type="button"
          class="px-3 py-1.5 rounded text-xs font-semibold cursor-pointer border-none transition"
          :class="activeTab === 'saved' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
          @click="activeTab = 'saved'"
        >
          Nội dung đã lưu ({{ savedPosts.length }})
        </button>
      </div>

      <!-- Tab Content: Posts -->
      <div v-if="activeTab === 'posts'" class="space-y-3">
        <div v-if="myPosts.length === 0" class="p-6 text-center text-xs text-slate-400">
          Bạn chưa có bài viết nào trên bảng tin.
        </div>
        <div
          v-for="post in myPosts"
          :key="post.id"
          class="p-3.5 rounded border border-slate-200 bg-slate-50 space-y-2 text-xs"
        >
          <div class="flex items-center justify-between text-[11px] text-slate-500">
            <span class="font-bold text-slate-800">Kênh: {{ post.channel }}</span>
            <span>{{ post.createdAt }}</span>
          </div>
          <p class="text-slate-800">{{ post.content }}</p>
          <div class="flex items-center gap-3 pt-1 text-[11px] text-slate-400">
            <span>{{ post.likes }} lượt thích</span>
            <span>•</span>
            <span>{{ post.comments.length }} bình luận</span>
          </div>
        </div>
      </div>

      <!-- Tab Content: Questions -->
      <div v-if="activeTab === 'questions'" class="space-y-3">
        <div v-if="myQuestions.length === 0" class="p-6 text-center text-xs text-slate-400">
          Bạn chưa đặt câu hỏi nào trên Base Square.
        </div>
        <div
          v-for="q in myQuestions"
          :key="q.id"
          class="p-3.5 rounded border border-slate-200 bg-slate-50 space-y-2 text-xs cursor-pointer hover:border-slate-300"
          @click="store.selectedQuestionId = q.id; store.setScreen('qa')"
        >
          <div class="flex items-center justify-between">
            <span class="font-bold text-slate-800">{{ q.title }}</span>
            <span class="text-[11px] text-slate-400">{{ q.createdAt }}</span>
          </div>
          <p class="text-slate-600 line-clamp-1">{{ q.content }}</p>
          <div class="flex items-center gap-3 pt-1 text-[11px] text-slate-400">
            <span>{{ q.votes }} bình chọn</span>
            <span>•</span>
            <span>{{ q.answersCount }} câu trả lời</span>
          </div>
        </div>
      </div>

      <!-- Tab Content: Saved -->
      <div v-if="activeTab === 'saved'" class="space-y-3">
        <div v-if="savedPosts.length === 0" class="p-6 text-center text-xs text-slate-400">
          Chưa có bài viết nào được lưu.
        </div>
        <div
          v-for="post in savedPosts"
          :key="post.id"
          class="p-3.5 rounded border border-slate-200 bg-slate-50 space-y-2 text-xs"
        >
          <div class="flex items-center justify-between text-[11px] text-slate-500">
            <span class="font-bold text-slate-800">{{ post.author }} ({{ post.channel }})</span>
            <span>{{ post.createdAt }}</span>
          </div>
          <p class="text-slate-800">{{ post.content }}</p>
          <div class="flex items-center justify-between pt-1">
            <span class="text-[11px] text-slate-400">{{ post.likes }} lượt thích</span>
            <button
              type="button"
              class="text-amber-600 hover:underline text-[11px] font-semibold border-none bg-transparent cursor-pointer p-0"
              @click="store.toggleSavePost(post.id)"
            >
              Bỏ lưu
            </button>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>
