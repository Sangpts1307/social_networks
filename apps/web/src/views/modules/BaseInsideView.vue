<script setup lang="ts">
import { ref } from 'vue'
import type { AuthUser } from '../../api/auth'
import { MOCK_SOCIAL_POSTS } from '../../mock/mockData.js'

const props = defineProps<{
  user: AuthUser
}>()

const emit = defineEmits<{
  (e: 'back'): void
  (e: 'select-app', appId: string): void
  (e: 'logout'): void
}>()

const posts = ref([...MOCK_SOCIAL_POSTS])
const isComposerExpanded = ref(false)
const newContent = ref('')
const newChannel = ref('Tech & Architecture')

function toggleLike(post: any) {
  post.isLiked = !post.isLiked
  post.likes += post.isLiked ? 1 : -1
}

function handleAddPost() {
  if (!newContent.value.trim()) return
  posts.value.unshift({
    id: `post-${Date.now()}`,
    author: props.user.display_name,
    role: 'Thành viên đóng góp',
    channel: newChannel.value,
    time: 'Vừa xong',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80',
    content: newContent.value.trim(),
    likes: 0,
    commentsCount: 0,
    isLiked: false,
    image: '',
    comments: []
  })
  newContent.value = ''
  isComposerExpanded.value = false
}
</script>

<template>
  <div class="min-h-screen text-slate-900 antialiased flex flex-col justify-between bg-[#F8FAFC] font-sans">
    
    <!-- TOP APP NAVBAR (03-social-feed.html) -->
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200">
      <div class="max-w-[1440px] mx-auto px-4 lg:px-8 h-14 flex items-center justify-between gap-4">
        
        <!-- Brand Logo -->
        <button
          type="button"
          class="flex items-center gap-2.5 cursor-pointer border-none bg-transparent"
          title="Về cổng Portal"
          @click="emit('back')"
        >
          <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
            <span class="material-symbols-outlined text-base">change_history</span>
          </div>
          <span class="text-base font-extrabold tracking-wider text-slate-900">BASE.VN</span>
        </button>

        <!-- Center-Right Tools -->
        <div class="flex items-center gap-3">
          <div class="relative w-64 sm:w-80 hidden sm:block">
            <input
              type="text"
              placeholder="Tìm kiếm bài viết, người, kênh..."
              class="w-full pl-4 pr-9 py-1.5 bg-slate-50 hover:bg-slate-100 focus:bg-white text-xs rounded-md border border-slate-200 focus:border-blue-600 outline-none transition"
            >
            <span class="material-symbols-outlined absolute right-2.5 top-2 text-slate-400 text-base">search</span>
          </div>

          <button
            type="button"
            class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md cursor-pointer border-none"
            title="Tạo nhanh"
            @click="isComposerExpanded = true"
          >
            <span class="material-symbols-outlined text-base">add</span>
          </button>

          <div class="relative">
            <button
              type="button"
              class="p-1.5 hover:bg-slate-100 text-slate-600 rounded-md cursor-pointer border-none"
              title="Thông báo"
            >
              <span class="material-symbols-outlined text-base">notifications</span>
            </button>
            <span class="absolute top-0.5 right-0.5 w-4 h-4 rounded-full bg-rose-600 text-white text-[9px] font-bold flex items-center justify-center">2</span>
          </div>

          <button
            type="button"
            class="flex items-center gap-2 p-1 rounded-md hover:bg-slate-100 cursor-pointer border-none bg-transparent"
            title="Xem trang cá nhân"
            @click="emit('select-app', 'profile')"
          >
            <img
              src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80"
              alt="Avatar"
              class="w-7 h-7 rounded-full object-cover border border-slate-200"
            >
            <span class="text-xs font-bold text-slate-800 hidden sm:block">{{ props.user.display_name }}</span>
          </button>

          <button
            type="button"
            class="px-2.5 py-1 text-xs font-semibold text-slate-500 hover:text-rose-600 rounded cursor-pointer border-none bg-transparent"
            title="Đăng xuất"
            @click="emit('logout')"
          >
            <span class="material-symbols-outlined text-base align-middle">logout</span>
          </button>
        </div>
      </div>
    </header>

    <!-- 3-COLUMN BASE INSIDE LAYOUT (1440PX) -->
    <main class="max-w-[1440px] mx-auto px-4 lg:px-8 py-4 flex-1 w-full grid grid-cols-1 md:grid-cols-12 gap-5">
      
      <!-- LEFT SIDEBAR: NAVIGATION & CHANNELS -->
      <aside class="md:col-span-3 space-y-4">
        
        <!-- Main Nav Items -->
        <nav class="bg-white rounded-lg border border-slate-200 p-2 shadow-xs space-y-0.5 text-xs font-semibold text-slate-700">
          <button
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 bg-blue-600 text-white rounded-md font-bold shadow-xs cursor-pointer border-none text-left"
          >
            <span class="material-symbols-outlined text-base">home</span>
            <span>Bảng tin Home</span>
          </button>
          <button
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-slate-100 rounded-md cursor-pointer border-none bg-transparent text-left text-slate-700"
            @click="emit('select-app', 'profile')"
          >
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80" class="w-5 h-5 rounded-full object-cover">
            <span>{{ props.user.display_name }}</span>
          </button>
          <button
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-slate-100 rounded-md cursor-pointer border-none bg-transparent text-left text-slate-700"
            @click="emit('select-app', 'message')"
          >
            <span class="material-symbols-outlined text-base text-slate-500">chat</span>
            <span>Base Message</span>
          </button>
          <button
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-slate-100 rounded-md cursor-pointer border-none bg-transparent text-left text-slate-700"
            @click="emit('select-app', 'square')"
          >
            <span class="material-symbols-outlined text-base text-slate-500">school</span>
            <span>Base Square (Tri thức)</span>
          </button>
          <button
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-slate-100 rounded-md cursor-pointer border-none bg-transparent text-left text-slate-700"
            @click="emit('select-app', 'admin')"
          >
            <span class="material-symbols-outlined text-base text-slate-500">bar_chart</span>
            <span>Báo cáo & Quản trị</span>
          </button>
        </nav>

        <!-- Section: CÔNG TY -->
        <div class="bg-white rounded-lg border border-slate-200 p-3 shadow-xs space-y-2 text-xs">
          <div class="flex items-center justify-between text-slate-400 font-bold uppercase text-[11px] tracking-wider">
            <span>CÔNG TY</span>
            <span class="material-symbols-outlined text-sm">expand_more</span>
          </div>
          <div class="space-y-1 font-medium text-slate-700">
            <div class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded cursor-pointer">
              <span class="material-symbols-outlined text-sm text-slate-400">mail</span> Thư từ Ban Giám đốc
            </div>
            <div class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded cursor-pointer">
              <span class="material-symbols-outlined text-sm text-slate-400">auto_stories</span> Giá trị cốt lõi
            </div>
            <div class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded cursor-pointer">
              <span class="material-symbols-outlined text-sm text-slate-400">flag</span> Nguyên tắc làm việc
            </div>
          </div>
        </div>

        <!-- Section: KÊNH PHÒNG BAN -->
        <div class="bg-white rounded-lg border border-slate-200 p-3 shadow-xs space-y-2 text-xs">
          <div class="flex items-center justify-between text-slate-400 font-bold uppercase text-[11px] tracking-wider">
            <span>KÊNH NỘI BỘ</span>
            <span class="material-symbols-outlined text-sm">expand_more</span>
          </div>
          <div class="space-y-1 font-medium text-slate-700">
            <div class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded cursor-pointer">
              <span class="w-4 h-4 rounded bg-emerald-600 text-white text-[10px] font-bold flex items-center justify-center flex-shrink-0">T</span>
              <span class="truncate">Tech & Architecture</span>
            </div>
            <div class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded cursor-pointer">
              <span class="w-4 h-4 rounded bg-amber-500 text-white text-[10px] font-bold flex items-center justify-center flex-shrink-0">A</span>
              <span class="truncate">AI & Knowledge Graph</span>
            </div>
            <div class="flex items-center gap-2 px-2 py-1.5 hover:bg-slate-50 rounded cursor-pointer">
              <span class="w-4 h-4 rounded bg-teal-600 text-white text-[10px] font-bold flex items-center justify-center flex-shrink-0">H</span>
              <span class="truncate">HR & Văn hóa Doanh nghiệp</span>
            </div>
          </div>
        </div>

      </aside>

      <!-- CENTER FEED: POST CREATOR & FEED POSTS -->
      <section class="md:col-span-6 space-y-4">
        
        <!-- Post Creator Box -->
        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-3">
          <div class="flex items-center gap-3">
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80" class="w-9 h-9 rounded-full object-cover">
            <input
              type="text"
              placeholder="Chia sẻ với đồng nghiệp..."
              class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-100/80 focus:bg-white text-xs rounded-full border border-transparent focus:border-blue-600 outline-none transition cursor-pointer"
              @focus="isComposerExpanded = true"
            >
          </div>

          <div v-if="isComposerExpanded" class="space-y-3 pt-2 border-t border-slate-100">
            <textarea
              v-model="newContent"
              placeholder="Nhập nội dung thông báo hoặc cập nhật của bạn..."
              rows="3"
              class="w-full p-3 bg-slate-50 text-xs rounded-lg border border-slate-200 outline-none focus:border-blue-600"
            ></textarea>
            
            <div class="flex items-center justify-between">
              <select
                v-model="newChannel"
                class="px-2.5 py-1 text-xs rounded border border-slate-200 bg-white text-slate-700 outline-none"
              >
                <option value="Tech & Architecture">Tech & Architecture</option>
                <option value="Toàn công ty">Toàn công ty</option>
                <option value="AI & Knowledge Graph">AI & Knowledge Graph</option>
              </select>

              <div class="flex justify-end gap-2">
                <button
                  type="button"
                  class="px-3 py-1.5 bg-slate-100 text-slate-600 text-xs rounded-md cursor-pointer border-none"
                  @click="isComposerExpanded = false"
                >
                  Hủy
                </button>
                <button
                  type="button"
                  class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-md shadow-xs cursor-pointer border-none"
                  @click="handleAddPost"
                >
                  Đăng bài
                </button>
              </div>
            </div>
          </div>

          <!-- Action Items below Creator Input -->
          <div class="flex flex-wrap items-center justify-between text-xs font-semibold text-slate-600 pt-2 border-t border-slate-100">
            <button type="button" class="flex items-center gap-1.5 hover:text-blue-600 py-1 border-none bg-transparent cursor-pointer" @click="isComposerExpanded = true">
              <span class="material-symbols-outlined text-sm text-blue-500">chat</span>
              <span>Cập nhật nhanh</span>
            </button>
            <span class="text-slate-200">|</span>
            <button type="button" class="flex items-center gap-1.5 hover:text-blue-600 py-1 border-none bg-transparent cursor-pointer" @click="isComposerExpanded = true">
              <span class="material-symbols-outlined text-sm text-amber-500">campaign</span>
              <span>Thông báo</span>
            </button>
            <span class="text-slate-200">|</span>
            <button type="button" class="flex items-center gap-1.5 hover:text-blue-600 py-1 border-none bg-transparent cursor-pointer" @click="isComposerExpanded = true">
              <span class="material-symbols-outlined text-sm text-emerald-500">poll</span>
              <span>Tạo bình chọn</span>
            </button>
            <span class="text-slate-200">|</span>
            <button type="button" class="flex items-center gap-1.5 hover:text-blue-600 py-1 border-none bg-transparent cursor-pointer" @click="isComposerExpanded = true">
              <span class="material-symbols-outlined text-sm text-teal-500">event</span>
              <span>Tạo sự kiện</span>
            </button>
          </div>
        </div>

        <!-- Section: Bài viết trên Feed -->
        <div class="flex items-center justify-between text-xs font-bold text-slate-800 pt-1">
          <div class="flex items-center gap-1.5 text-blue-700">
            <span class="material-symbols-outlined text-base">dynamic_feed</span>
            <span>Bảng tin nội bộ gần đây</span>
          </div>
          <span class="text-slate-400 font-normal">Hiển thị {{ posts.length }} bài viết</span>
        </div>

        <!-- Posts Loop -->
        <article
          v-for="post in posts"
          :key="post.id"
          class="bg-white rounded-lg border border-slate-200 shadow-xs overflow-hidden space-y-3 p-4"
        >
          <!-- Author Info -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <img :src="post.avatar" class="w-10 h-10 rounded-full object-cover">
              <div>
                <div class="flex items-center gap-1.5 flex-wrap text-xs">
                  <span class="font-bold text-slate-900">{{ post.author }}</span>
                  <span class="text-slate-400">•</span>
                  <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-semibold text-[10px]">{{ post.channel }}</span>
                </div>
                <div class="text-[11px] text-slate-400">{{ post.role }} • {{ post.time }}</div>
              </div>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-600 border-none bg-transparent cursor-pointer">
              <span class="material-symbols-outlined text-base">more_horiz</span>
            </button>
          </div>

          <!-- Content Body -->
          <p class="text-xs text-slate-800 leading-relaxed whitespace-pre-line">
            {{ post.content }}
          </p>

          <!-- Attached Image if any -->
          <div v-if="post.image" class="rounded-lg overflow-hidden border border-slate-100 max-h-80">
            <img :src="post.image" class="w-full h-full object-cover">
          </div>

          <!-- Like / Comment Stats & Buttons -->
          <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <div class="flex items-center gap-4">
              <button
                type="button"
                class="flex items-center gap-1.5 font-semibold transition border-none bg-transparent cursor-pointer"
                :class="post.isLiked ? 'text-blue-600' : 'text-slate-600 hover:text-blue-600'"
                @click="toggleLike(post)"
              >
                <span class="material-symbols-outlined text-base">thumb_up</span>
                <span>{{ post.likes }} Thích</span>
              </button>
              
              <div class="flex items-center gap-1.5 font-semibold text-slate-600">
                <span class="material-symbols-outlined text-base">chat_bubble</span>
                <span>{{ post.commentsCount }} Bình luận</span>
              </div>
            </div>

            <button type="button" class="text-slate-400 hover:text-slate-600 border-none bg-transparent cursor-pointer">
              <span class="material-symbols-outlined text-base">share</span>
            </button>
          </div>

          <!-- Comments List if available -->
          <div v-if="post.comments && post.comments.length > 0" class="pt-2 space-y-2 border-t border-slate-50">
            <div
              v-for="cmt in post.comments"
              :key="cmt.id"
              class="flex items-start gap-2.5 p-2 bg-slate-50 rounded-lg text-xs"
            >
              <img :src="cmt.avatar" class="w-6 h-6 rounded-full object-cover mt-0.5">
              <div class="flex-1">
                <div class="flex items-center justify-between">
                  <span class="font-bold text-slate-900">{{ cmt.author }}</span>
                  <span class="text-[10px] text-slate-400">{{ cmt.time }}</span>
                </div>
                <p class="text-slate-700 mt-0.5">{{ cmt.content }}</p>
              </div>
            </div>
          </div>
        </article>

      </section>

      <!-- RIGHT SIDEBAR: TRENDING & ANNOUNCEMENTS -->
      <aside class="md:col-span-3 space-y-4">
        
        <!-- Trending Widget -->
        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-3 text-xs">
          <div class="flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
              <span class="material-symbols-outlined text-amber-500 text-base">bolt</span>
              <span>Xu hướng thảo luận</span>
            </h3>
          </div>
          <div class="space-y-2 text-slate-700">
            <div class="p-2 rounded bg-slate-50 hover:bg-slate-100 transition cursor-pointer">
              <span class="font-bold text-blue-600 block">#knowledge-graph</span>
              <span class="text-[11px] text-slate-400">28 bài viết &amp; câu hỏi</span>
            </div>
            <div class="p-2 rounded bg-slate-50 hover:bg-slate-100 transition cursor-pointer">
              <span class="font-bold text-blue-600 block">#laravel-microservices</span>
              <span class="text-[11px] text-slate-400">19 bài viết</span>
            </div>
            <div class="p-2 rounded bg-slate-50 hover:bg-slate-100 transition cursor-pointer">
              <span class="font-bold text-blue-600 block">#base-reward-q3</span>
              <span class="text-[11px] text-slate-400">14 bài viết</span>
            </div>
          </div>
        </div>

        <!-- Events Widget -->
        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-xs space-y-3 text-xs">
          <h3 class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
            <span class="material-symbols-outlined text-teal-600 text-base">event</span>
            <span>Sự kiện sắp tới</span>
          </h3>
          <div class="space-y-2">
            <div class="border-l-2 border-teal-500 pl-2.5 py-1 text-slate-700">
              <span class="font-bold block text-slate-900">Chia sẻ Tri thức Graph RAG</span>
              <span class="text-[11px] text-slate-400">Thứ 6, 14:00 • Phòng họp 3</span>
            </div>
            <div class="border-l-2 border-blue-500 pl-2.5 py-1 text-slate-700">
              <span class="font-bold block text-slate-900">Demo Base Square v2</span>
              <span class="text-[11px] text-slate-400">Thứ 2 tới • Online Google Meet</span>
            </div>
          </div>
        </div>

      </aside>

    </main>

  </div>
</template>
