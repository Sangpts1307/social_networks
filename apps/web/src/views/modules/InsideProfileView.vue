<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import type { AuthUser } from '../../api/auth'
import { profileApi, type AccountProfile } from '../../api/profile'
import { socialApi, type SocialPostDto } from '../../api/social'
import { insidePosts, seedPersonalPosts } from './insidePosts'

const props = defineProps<{ user: AuthUser; token: string; channelMap?: Record<string, string> }>()
const emit = defineEmits<{ (e: 'back'): void; (e: 'settings'): void; (e: 'comment', postId: string): void; (e: 'share', post: any): void }>()
const profile = ref<AccountProfile | null>(null)
const followers = ref<number | null>(null)
const following = ref<number | null>(null)
const apiPosts = ref<any[] | null>(null)
const postToDelete = ref<any | null>(null)
const deletingPost = ref(false)
const deleteError = ref('')
const avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=240&auto=format&fit=crop&q=85'

const myPosts = computed(() => {
  if (apiPosts.value !== null) return apiPosts.value
  return insidePosts.value.filter(post => post.author === props.user.display_name || post.authorId === props.user.id)
})

function mapDtoToPost(dto: SocialPostDto) {
  const body = dto.body_markdown || dto.body_plaintext || ''
  const imagePattern = /!\[[^\]]*\]\((https?:\/\/[^\s)]+|data:image\/(?:png|jpeg|jpg|gif|webp);base64,[A-Za-z0-9+/=]+)\)/g
  const image = dto.image_url || imagePattern.exec(body)?.[1] || ''
  return {
    id: dto.id,
    authorId: dto.author_user_id,
    author: profile.value?.display_name || props.user.display_name,
    role: profile.value?.job_title || 'Thành viên',
    channelId: dto.channel_id,
    channel: props.channelMap?.[dto.channel_id] || 'Nội bộ',
    time: dto.published_at ? new Date(dto.published_at).toLocaleDateString('vi-VN') : 'Vừa xong',
    avatar,
    title: dto.title,
    content: body.replace(imagePattern, '').trim(),
    likes: Number(dto.reaction_count ?? 0),
    commentsCount: Number(dto.comment_count ?? 0),
    isLiked: Boolean(dto.my_reaction),
    isSaved: Boolean(dto.is_saved),
    image,
  }
}

async function confirmDeletePost() {
  const post = postToDelete.value
  if (!post || deletingPost.value || post.authorId !== props.user.id) return
  deletingPost.value = true
  deleteError.value = ''
  try {
    if (apiPosts.value !== null) {
      await socialApi.deletePost(props.token, post.id)
      apiPosts.value = apiPosts.value.filter(item => item.id !== post.id)
    } else {
      insidePosts.value = insidePosts.value.filter(item => item.id !== post.id)
    }
    postToDelete.value = null
  } catch (cause) {
    deleteError.value = cause instanceof Error ? cause.message : 'Không xóa được bài viết.'
  } finally {
    deletingPost.value = false
  }
}

async function toggleLike(post: any) {
  const nextLiked = !post.isLiked
  post.isLiked = nextLiked
  post.likes = Math.max(0, (post.likes || 0) + (nextLiked ? 1 : -1))
  try {
    await socialApi.react(props.token, post.id, nextLiked ? 'like' : null)
  } catch {
    // Keep local state in fallback/demo mode
  }
}

onMounted(async () => {
  seedPersonalPosts(props.user.display_name)
  try { profile.value = await profileApi.me(props.token) } catch { /* Show signed-in user details. */ }
  try {
    const body = await socialApi.followStats(props.token)
    followers.value = body.data.followers_count
    following.value = body.data.following_count
  } catch { /* Follow counts are optional. */ }
  try {
    const page = await socialApi.posts(props.token, { author_user_id: props.user.id })
    if (Array.isArray(page?.data)) {
      apiPosts.value = page.data.map(mapDtoToPost)
    }
  } catch { /* Fallback to seeded personal posts when API is offline. */ }
})
</script>

<template>
  <section class="min-w-0 space-y-4" aria-label="Trang cá nhân">
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div class="relative h-36 bg-gradient-to-r from-[#17376d] via-[#2369a8] to-[#60c6c7] sm:h-44">
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 28px 28px"></div>
      </div>
      <div class="px-5 pb-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
          <div class="flex min-w-0 items-end gap-4">
            <img :src="avatar" alt="Ảnh đại diện" class="relative -mt-10 h-20 w-20 shrink-0 rounded-full border-4 border-white object-cover sm:h-24 sm:w-24">
            <div class="min-w-0 pb-1">
              <h1 class="truncate text-xl font-bold text-slate-900">{{ profile?.display_name || user.display_name }}</h1>
              <p class="text-sm text-slate-500">{{ profile?.job_title || 'Thành viên' }}</p>
            </div>
          </div>
          <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700" @click="emit('settings')">
            <span class="material-symbols-outlined text-lg">edit</span> Chỉnh sửa thông tin
          </button>
        </div>
        <p class="mt-4 text-sm text-slate-500">{{ followers ?? '—' }} người theo dõi · {{ following ?? '—' }} đang theo dõi</p>
        <div class="mt-4 border-t border-slate-100 pt-3 text-sm font-semibold text-blue-700">Bài viết</div>
      </div>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-5" aria-label="Giới thiệu">
      <h2 class="font-bold text-slate-900">Giới thiệu</h2>
      <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ profile?.bio || 'Chưa có lời giới thiệu.' }}</p>
      <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 border-t border-slate-100 pt-4 text-sm text-slate-600">
        <span class="inline-flex items-center gap-2"><span class="material-symbols-outlined text-lg text-blue-600">work</span>{{ profile?.job_title || 'Thành viên công ty' }}</span>
        <span v-if="profile?.location" class="inline-flex items-center gap-2"><span class="material-symbols-outlined text-lg text-blue-600">location_on</span>{{ profile.location }}</span>
        <span v-if="profile?.skills?.length" class="inline-flex items-center gap-2"><span class="material-symbols-outlined text-lg text-blue-600">psychology</span>{{ profile.skills.join(', ') }}</span>
      </div>
    </section>

    <section class="space-y-4" aria-label="Bài viết cá nhân">
      <p v-if="deleteError" role="alert" class="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ deleteError }}</p>
      <article v-for="post in myPosts" :key="post.id" class="overflow-hidden rounded-xl border border-slate-200 bg-white p-5">
        <div class="flex items-center justify-between gap-3"><div class="flex items-center gap-3"><img :src="post.avatar || avatar" alt="" class="h-10 w-10 rounded-full object-cover"><div><h3 class="text-sm font-bold text-slate-900">{{ post.author }}</h3><p class="text-xs text-slate-500">{{ post.time }} · {{ post.channel }}</p></div></div><button v-if="post.authorId === user.id" type="button" class="rounded-lg p-2 text-slate-500 hover:bg-rose-50 hover:text-rose-600" :aria-label="`Xóa bài viết của ${post.author}`" title="Xóa bài viết" @click="postToDelete = post; deleteError = ''"><span class="material-symbols-outlined text-xl">delete</span></button></div>
        <h4 v-if="post.title" class="mt-3 text-base font-bold text-slate-900">{{ post.title }}</h4>
        <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{{ post.content }}</p>
        <img v-if="post.image" :src="post.image" alt="Ảnh bài đăng" class="mt-4 max-h-96 w-full rounded-lg object-cover">
        <div class="mt-4 flex items-center gap-6 border-t border-slate-100 pt-3 text-sm text-slate-500">
          <button type="button" class="inline-flex items-center gap-1.5 hover:text-blue-600" :class="post.isLiked ? 'text-blue-600' : ''" :aria-label="`Thích bài viết của ${post.author}`" @click="toggleLike(post)"><span class="material-symbols-outlined text-xl">thumb_up</span>{{ post.likes }}</button>
          <button type="button" class="inline-flex items-center gap-1.5 hover:text-blue-600" :aria-label="`Xem bình luận bài viết của ${post.author}`" @click="emit('comment', post.id)"><span class="material-symbols-outlined text-xl">chat_bubble</span>{{ post.commentsCount }}</button>
          <button type="button" class="inline-flex items-center gap-1.5 hover:text-blue-600" :aria-label="`Chia sẻ bài viết của ${post.author}`" @click="emit('share', post)"><span class="material-symbols-outlined text-xl">share</span></button>
        </div>
      </article>
    </section>
    <div v-if="postToDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-label="Xác nhận xóa bài viết">
      <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl"><h2 class="font-bold text-slate-900">Xóa bài viết?</h2><p class="mt-2 text-sm text-slate-600">Bài viết sẽ bị xóa khỏi trang cá nhân.</p><div class="mt-5 flex justify-end gap-2"><button type="button" class="rounded-lg px-4 py-2 text-sm text-slate-600" :disabled="deletingPost" @click="postToDelete = null">Hủy</button><button type="button" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" :disabled="deletingPost" @click="confirmDeletePost">{{ deletingPost ? 'Đang xóa...' : 'Xóa bài viết' }}</button></div></div>
    </div>
  </section>
</template>
