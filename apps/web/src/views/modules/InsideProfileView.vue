<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import type { AuthUser } from '../../api/auth'
import { profileApi, type AccountProfile } from '../../api/profile'
import { insidePosts, seedPersonalPosts } from './insidePosts'

const props = defineProps<{ user: AuthUser; token: string }>()
const emit = defineEmits<{ (e: 'back'): void; (e: 'settings'): void; (e: 'comment', postId: string): void; (e: 'share', post: any): void }>()
const profile = ref<AccountProfile | null>(null)
const followers = ref<number | null>(null)
const following = ref<number | null>(null)
const avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=240&auto=format&fit=crop&q=85'
const myPosts = computed(() => insidePosts.value.filter(post => post.author === props.user.display_name))

function toggleLike(post: any) {
  post.isLiked = !post.isLiked
  post.likes += post.isLiked ? 1 : -1
}

onMounted(async () => {
  seedPersonalPosts(props.user.display_name)
  try { profile.value = await profileApi.me(props.token) } catch { /* Show signed-in user details. */ }
  try {
    const response = await fetch('/api/v1/social/follow-stats', { headers: { Authorization: `Bearer ${props.token}`, Accept: 'application/json' } })
    if (response.ok) {
      const body = await response.json()
      followers.value = body.data.followers_count
      following.value = body.data.following_count
    }
  } catch { /* Follow counts are optional. */ }
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
      <h2 class="rounded-xl border border-slate-200 bg-white px-5 py-4 font-bold text-slate-900">Bài viết của {{ profile?.display_name || user.display_name }} <span class="text-sm font-normal text-slate-400">({{ myPosts.length }})</span></h2>
      <article v-for="post in myPosts" :key="post.id" class="overflow-hidden rounded-xl border border-slate-200 bg-white p-5">
        <div class="flex items-center gap-3"><img :src="post.avatar || avatar" alt="" class="h-10 w-10 rounded-full object-cover"><div><h3 class="text-sm font-bold text-slate-900">{{ post.author }}</h3><p class="text-xs text-slate-500">{{ post.time }} · {{ post.channel }}</p></div></div>
        <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{{ post.content }}</p>
        <img v-if="post.image" :src="post.image" alt="Ảnh bài đăng" class="mt-4 max-h-96 w-full rounded-lg object-cover">
        <div class="mt-4 flex items-center gap-6 border-t border-slate-100 pt-3 text-sm text-slate-500">
          <button type="button" class="inline-flex items-center gap-1.5 hover:text-blue-600" :class="post.isLiked ? 'text-blue-600' : ''" :aria-label="`Thích bài viết của ${post.author}`" @click="toggleLike(post)"><span class="material-symbols-outlined text-xl">thumb_up</span>{{ post.likes }}</button>
          <button type="button" class="inline-flex items-center gap-1.5 hover:text-blue-600" :aria-label="`Xem bình luận bài viết của ${post.author}`" @click="emit('comment', post.id)"><span class="material-symbols-outlined text-xl">chat_bubble</span>{{ post.commentsCount }}</button>
          <button type="button" class="inline-flex items-center gap-1.5 hover:text-blue-600" :aria-label="`Chia sẻ bài viết của ${post.author}`" @click="emit('share', post)"><span class="material-symbols-outlined text-xl">share</span></button>
        </div>
      </article>
    </section>
  </section>
</template>
