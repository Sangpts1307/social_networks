<script setup lang="ts">
import { ref, computed } from 'vue'
import { store } from '../../mock/store'

const activeChannel = ref('Tất cả')
const showSavedOnly = ref(false)
const newPostContent = ref('')
const newPostChannel = ref('Toàn công ty')
const newPostImage = ref('')
const activeCommentPostId = ref<string | null>(null)
const commentInputs = ref<Record<string, string>>({})

const channels = ['Tất cả', 'Toàn công ty', 'Kỹ thuật', 'Marketing', 'Nhân sự & Văn hóa', 'Sản phẩm']

const filteredPosts = computed(() => {
  return store.posts.filter(p => {
    if (showSavedOnly.value && !p.isSaved) return false
    if (activeChannel.value !== 'Tất cả' && p.channel !== activeChannel.value) return false
    return true
  })
})

function submitNewPost() {
  if (!newPostContent.value.trim()) return
  store.createPost(newPostContent.value, newPostChannel.value, newPostImage.value)
  newPostContent.value = ''
  newPostImage.value = ''
}

function submitComment(postId: string) {
  const text = commentInputs.value[postId] || ''
  if (!text.trim()) return
  store.addComment(postId, text)
  commentInputs.value[postId] = ''
}
</script>

<template>
  <div class="max-w-[1440px] mx-auto px-4 py-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
      
      <!-- LEFT SIDEBAR: CHANNELS & SHORTCUTS -->
      <aside class="lg:col-span-3 space-y-4">
        <!-- Channels Box -->
        <div class="bg-white border border-slate-200 rounded-md p-3">
          <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2.5 px-2">Kênh thảo luận</h2>
          <nav class="space-y-0.5 text-xs font-medium">
            <button
              v-for="ch in channels"
              :key="ch"
              type="button"
              class="w-full text-left px-2.5 py-2 rounded flex items-center justify-between cursor-pointer border-none transition"
              :class="activeChannel === ch && !showSavedOnly ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50'"
              @click="activeChannel = ch; showSavedOnly = false"
            >
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">tag</span>
                <span>{{ ch }}</span>
              </div>
            </button>
          </nav>

          <div class="pt-3 mt-2 border-t border-slate-100 space-y-0.5 text-xs font-medium">
            <button
              type="button"
              class="w-full text-left px-2.5 py-2 rounded flex items-center gap-2 cursor-pointer border-none transition"
              :class="showSavedOnly ? 'bg-slate-100 text-slate-900 font-bold' : 'text-slate-600 hover:bg-slate-50'"
              @click="showSavedOnly = true; activeChannel = 'Tất cả'"
            >
              <span class="material-symbols-outlined text-sm">bookmark</span>
              <span>Bài viết đã lưu</span>
            </button>
          </div>
        </div>

        <!-- Quick Info Box -->
        <div class="bg-white border border-slate-200 rounded-md p-3 text-xs text-slate-500">
          <span class="font-bold text-slate-700 block mb-1">Quy định bảng tin</span>
          <p class="leading-relaxed">Đăng bài đúng kênh chuyên môn. Mọi bài viết được hỗ trợ rà soát bởi AI Scanner và kiểm duyệt nội bộ.</p>
        </div>
      </aside>

      <!-- CENTER: POST COMPOSER & FEED -->
      <main class="lg:col-span-6 space-y-4">
        
        <!-- Post Composer -->
        <div class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
          <div class="flex items-center gap-2.5">
            <img :src="store.currentUser.avatar" alt="Avatar" class="w-8 h-8 rounded-md object-cover border border-slate-200">
            <div class="text-xs">
              <span class="font-bold text-slate-900 block leading-tight">{{ store.currentUser.name }}</span>
              <span class="text-slate-500">{{ store.currentUser.roleTitle }}</span>
            </div>
          </div>

          <textarea
            v-model="newPostContent"
            rows="3"
            placeholder="Chia sẻ thông tin, cập nhật tiến độ công việc hoặc sự kiện mới..."
            class="w-full p-2.5 bg-slate-50 hover:bg-white focus:bg-white text-xs text-slate-900 rounded border border-slate-200 outline-none transition resize-none placeholder:text-slate-400"
          ></textarea>

          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-2 border-t border-slate-100">
            <!-- Channel Picker & Image Input Toggle -->
            <div class="flex items-center gap-2 text-xs">
              <select 
                v-model="newPostChannel"
                class="px-2 py-1.5 bg-slate-50 rounded border border-slate-200 text-slate-700 outline-none cursor-pointer"
              >
                <option v-for="ch in channels.filter(c => c !== 'Tất cả')" :key="ch" :value="ch">Kênh: {{ ch }}</option>
              </select>

              <input 
                v-model="newPostImage" 
                type="text" 
                placeholder="URL hình ảnh (tùy chọn)" 
                class="px-2 py-1.5 bg-slate-50 rounded border border-slate-200 text-slate-700 outline-none text-xs w-44"
              >
            </div>

            <button
              type="button"
              class="px-4 py-1.5 rounded bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold cursor-pointer border-none transition disabled:opacity-50"
              :disabled="!newPostContent.trim()"
              @click="submitNewPost"
            >
              Đăng bài
            </button>
          </div>
        </div>

        <!-- Post List -->
        <div v-if="filteredPosts.length === 0" class="bg-white border border-slate-200 rounded-md p-8 text-center text-xs text-slate-500">
          Chưa có bài viết nào trong kênh này.
        </div>

        <div v-for="post in filteredPosts" :key="post.id" class="bg-white border border-slate-200 rounded-md p-4 space-y-3">
          
          <!-- Post Header -->
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-2.5">
              <img :src="post.avatar" :alt="post.author" class="w-8 h-8 rounded-md object-cover border border-slate-200">
              <div class="text-xs">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-slate-900">{{ post.author }}</span>
                  <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">
                    {{ post.channel }}
                  </span>
                </div>
                <span class="text-[11px] text-slate-400">{{ post.authorRole }} • {{ post.createdAt }}</span>
              </div>
            </div>

            <!-- Save Bookmark Button -->
            <button
              type="button"
              class="p-1 rounded text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer border-none bg-transparent"
              :title="post.isSaved ? 'Bỏ lưu' : 'Lưu bài viết'"
              @click="store.toggleSavePost(post.id)"
            >
              <span class="material-symbols-outlined text-base" :class="post.isSaved ? 'text-amber-500' : ''">
                {{ post.isSaved ? 'bookmark' : 'bookmark_border' }}
              </span>
            </button>
          </div>

          <!-- Post Content -->
          <p class="text-xs text-slate-800 leading-relaxed whitespace-pre-line">{{ post.content }}</p>

          <!-- Post Image (if any) -->
          <div v-if="post.imageUrl" class="rounded overflow-hidden border border-slate-100 max-h-72">
            <img :src="post.imageUrl" alt="Ảnh bài viết" class="w-full h-full object-cover">
          </div>

          <!-- Actions Bar -->
          <div class="flex items-center gap-4 pt-2 border-t border-slate-100 text-xs text-slate-500">
            <button
              type="button"
              class="flex items-center gap-1.5 hover:text-slate-900 transition cursor-pointer border-none bg-transparent p-0"
              :class="post.isLiked ? 'text-blue-600 font-bold' : ''"
              @click="store.toggleLikePost(post.id)"
            >
              <span class="material-symbols-outlined text-sm">{{ post.isLiked ? 'thumb_up' : 'thumb_up_off_alt' }}</span>
              <span>{{ post.likes }} Thích</span>
            </button>

            <button
              type="button"
              class="flex items-center gap-1.5 hover:text-slate-900 transition cursor-pointer border-none bg-transparent p-0"
              @click="activeCommentPostId = activeCommentPostId === post.id ? null : post.id"
            >
              <span class="material-symbols-outlined text-sm">chat_bubble_outline</span>
              <span>{{ post.comments.length }} Bình luận</span>
            </button>
          </div>

          <!-- Comments Section -->
          <div v-if="activeCommentPostId === post.id" class="pt-2 border-t border-slate-100 space-y-2">
            <!-- Comments List -->
            <div v-for="c in post.comments" :key="c.id" class="flex items-start gap-2 bg-slate-50 rounded p-2 text-xs">
              <img :src="c.avatar" alt="Avatar" class="w-6 h-6 rounded object-cover">
              <div class="flex-1">
                <div class="flex items-center justify-between">
                  <span class="font-bold text-slate-800">{{ c.author }}</span>
                  <span class="text-[10px] text-slate-400">{{ c.createdAt }}</span>
                </div>
                <p class="text-slate-700 mt-0.5">{{ c.content }}</p>
              </div>
            </div>

            <!-- Comment Composer Input -->
            <div class="flex items-center gap-2 pt-1">
              <input
                v-model="commentInputs[post.id]"
                type="text"
                placeholder="Viết bình luận..."
                class="flex-1 px-3 py-1.5 bg-slate-50 focus:bg-white text-xs text-slate-900 rounded border border-slate-200 outline-none"
                @keyup.enter="submitComment(post.id)"
              >
              <button
                type="button"
                class="px-3 py-1.5 rounded bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold cursor-pointer border-none"
                @click="submitComment(post.id)"
              >
                Gửi
              </button>
            </div>
          </div>

        </div>

      </main>

      <!-- RIGHT SIDEBAR: UPCOMING EVENTS & TRENDS -->
      <aside class="lg:col-span-3 space-y-4">
        <!-- Events Widget -->
        <div class="bg-white border border-slate-200 rounded-md p-3 text-xs">
          <h2 class="font-bold text-slate-900 uppercase tracking-wider mb-2 pb-1.5 border-b border-slate-100">
            Sự kiện sắp tới
          </h2>
          <div class="space-y-2.5">
            <div class="p-2 rounded bg-slate-50 border border-slate-100">
              <span class="text-[10px] font-bold text-blue-600 block uppercase">Thứ Sáu • 15:30</span>
              <p class="font-bold text-slate-800 mt-0.5">Tech Sharing: AI Knowledge Graph</p>
              <span class="text-[11px] text-slate-500">Hội trường tầng 3</span>
            </div>
            <div class="p-2 rounded bg-slate-50 border border-slate-100">
              <span class="text-[10px] font-bold text-amber-600 block uppercase">Thứ Hai tuần tới</span>
              <p class="font-bold text-slate-800 mt-0.5">Hạn nộp báo cáo KPI quý 3</p>
              <span class="text-[11px] text-slate-500">Toàn công ty</span>
            </div>
          </div>
        </div>

        <!-- System Activity -->
        <div class="bg-white border border-slate-200 rounded-md p-3 text-xs text-slate-600 space-y-2">
          <h2 class="font-bold text-slate-900 uppercase tracking-wider mb-2 pb-1.5 border-b border-slate-100">
            Thông tin kết nối
          </h2>
          <div class="flex items-center justify-between">
            <span>Hệ thống:</span>
            <span class="font-semibold text-emerald-600 flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Trực tuyến
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span>Thành viên hoạt động:</span>
            <span class="font-semibold text-slate-800">42 người</span>
          </div>
        </div>
      </aside>

    </div>
  </div>
</template>
