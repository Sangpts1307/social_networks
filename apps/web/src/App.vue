<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ApiError, authApi, type AuthUser } from './api/auth'
import { clearToken, readToken, storeToken } from './auth/storage'
import ChangePasswordView from './views/ChangePasswordView.vue'
import LoginView from './views/LoginView.vue'
import LearningView from './views/LearningView.vue'
import WelcomeView from './views/WelcomeView.vue'

// Distinct primary application views
import BaseInsideView from './views/modules/BaseInsideView.vue'
import BaseMessageView from './views/modules/BaseMessageView.vue'
import BaseAdminView from './views/modules/BaseAdminView.vue'

type View =
  | 'booting'
  | 'login'
  | 'change-password'
  | 'welcome'
  | 'learning'
  | 'inside'
  | 'message'
  | 'admin'

const currentView = ref<View>('booting')
const activeSquareTab = ref('questions')
const activeUser = ref<AuthUser | null>(null)
const token = ref<string | null>(null)
const globalMessage = ref('')

onMounted(() => {
  restoreSession()

  if (typeof window !== 'undefined') {
    window.addEventListener('popstate', (e) => {
      if (e.state && e.state.view) {
        currentView.value = e.state.view === 'square' ? 'learning' : e.state.view
        if (e.state.tab) activeSquareTab.value = e.state.tab === 'home' ? 'questions' : e.state.tab
      } else {
        const params = new URLSearchParams(window.location.search)
        const rawApp = params.get('app')
        const tab = params.get('tab')
        if (rawApp === 'square' || rawApp === 'learning') {
          currentView.value = 'learning'
          activeSquareTab.value = (tab === 'home' ? 'questions' : tab) || 'questions'
        } else if (rawApp) {
          currentView.value = rawApp as View
        } else {
          currentView.value = activeUser.value ? 'welcome' : 'login'
        }
      }
    })
  }
})

async function restoreSession(): Promise<void> {
  const savedToken = readToken()
  if (!savedToken) {
    currentView.value = 'login'
    return
  }
  try {
    const session = await authApi.inspect(savedToken)
    token.value = savedToken
    activeUser.value = session.user
    if (session.user.must_change_password) {
      currentView.value = 'change-password'
      updateHistory('change-password', undefined, false)
    } else {
      let next: View = 'welcome'
      let tab: string | undefined = undefined
      if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search)
        const appParam = params.get('app')
        const tabParam = params.get('tab')
        if (appParam === 'square' || appParam === 'learning') {
          next = 'learning'
          tab = (tabParam === 'home' ? 'questions' : tabParam) || 'questions'
          activeSquareTab.value = tab
        } else if (appParam === 'inside' || appParam === 'message' || appParam === 'admin') {
          next = appParam as View
        }
      }
      currentView.value = next
      updateHistory(next, tab, false)
    }
  } catch {
    clearToken()
    currentView.value = 'login'
    globalMessage.value = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.'
  }
}

function updateHistory(view: View, tab?: string, push = true) {
  if (typeof window === 'undefined') return
  try {
    const url = new URL(window.location.href)
    const appQuery = view === 'learning' ? 'square' : view
    url.searchParams.set('app', appQuery)
    if (tab) url.searchParams.set('tab', tab)
    else url.searchParams.delete('tab')
    if (push) {
      window.history.pushState({ view, tab }, '', url.toString())
    } else {
      window.history.replaceState({ view, tab }, '', url.toString())
    }
  } catch {
    // In restricted test environments
  }
}

function onLoginSuccess(payload: { token: string; user: AuthUser; remember: boolean }): void {
  token.value = payload.token
  activeUser.value = payload.user
  storeToken(payload.token, payload.remember)
  globalMessage.value = ''
  const next = payload.user.must_change_password ? 'change-password' : 'welcome'
  currentView.value = next
  updateHistory(next, undefined, true)
}

async function logout(message = ''): Promise<void> {
  const currentToken = token.value
  clearToken()
  token.value = null
  activeUser.value = null
  currentView.value = 'login'
  globalMessage.value = message
  updateHistory('login', undefined, true)
  if (!currentToken) return
  try {
    await authApi.logout(currentToken)
  } catch (error) {
    if (!(error instanceof ApiError) || error.status !== 401) {
      globalMessage.value = 'Đã đăng xuất trên thiết bị. Máy chủ chưa xác nhận thu hồi phiên.'
    }
  }
}

function onPasswordChanged(): void {
  logout('Đổi mật khẩu thành công. Vui lòng đăng nhập lại bằng mật khẩu mới.')
}

function openApplication(appId: string, subTab?: string): void {
  if (appId === 'square' || appId === 'learning') {
    currentView.value = 'learning'
    activeSquareTab.value = subTab || 'questions'
    updateHistory('learning', activeSquareTab.value, true)
  } else if (appId === 'library' || appId === 'knowledge' || appId === 'reward' || appId === 'search' || appId === 'questions') {
    currentView.value = 'learning'
    activeSquareTab.value = appId
    updateHistory('learning', appId, true)
  } else if (appId === 'profile' || appId === 'square-profile') {
    // Mở tab cá nhân ngay trong suite
    currentView.value = 'learning'
    activeSquareTab.value = 'profile'
    updateHistory('learning', 'profile', true)
  } else if (appId === 'inside') {
    currentView.value = 'inside'
    updateHistory('inside', undefined, true)
  } else if (appId === 'message') {
    currentView.value = 'message'
    updateHistory('message', undefined, true)
  } else if (appId === 'admin') {
    currentView.value = 'admin'
    updateHistory('admin', undefined, true)
  } else {
    currentView.value = 'welcome'
    updateHistory('welcome', undefined, true)
  }
}

function goBack(): void {
  if (typeof window !== 'undefined' && window.history.length > 1) {
    window.history.back()
  } else {
    openApplication('welcome')
  }
}

function onSquareTabChange(tab: string) {
  activeSquareTab.value = tab
  updateHistory('learning', tab, true)
}
</script>

<template>
  <div class="min-h-screen w-full bg-slate-50 text-slate-800 antialiased font-sans">
    
    <!-- Booting Spinner -->
    <div v-if="currentView === 'booting'" class="min-h-screen grid place-items-center text-slate-600">
      <div class="text-center">
        <span class="material-symbols-outlined animate-spin text-4xl text-slate-700">progress_activity</span>
        <p class="mt-3 text-sm font-semibold text-slate-600">Đang kiểm tra phiên đăng nhập...</p>
      </div>
    </div>

    <!-- Login Screen (02-login.html) -->
    <LoginView
      v-else-if="currentView === 'login'"
      :notice="globalMessage"
      @login-success="onLoginSuccess"
    />

    <!-- Force Change Password Screen on First Login -->
    <ChangePasswordView
      v-else-if="currentView === 'change-password' && token && activeUser"
      :token="token"
      :user="activeUser"
      @completed="onPasswordChanged"
      @logout="logout()"
    />

    <!-- Portal / Welcome Screen (01-welcome.html - ĐÃ TINH GỌN THEO YÊU CẦU) -->
    <WelcomeView
      v-else-if="currentView === 'welcome' && activeUser"
      :user="activeUser"
      @logout="logout()"
      @select-app="openApplication"
    />

    <!-- Base Square (HỆ SINH THÁI TRI THỨC TOÀN DIỆN CHUẨN MENU XANH ẢNH THỨ 2) -->
    <LearningView
      v-else-if="currentView === 'learning' && token"
      :token="token"
      :initial-tab="activeSquareTab"
      :user="activeUser"
      @back="goBack"
      @logout="logout()"
      @change-tab="onSquareTabChange"
    />

    <!-- Base Inside (Mạng xã hội & Bản tin nội bộ - 03-social-feed.html) -->
    <BaseInsideView
      v-else-if="currentView === 'inside' && activeUser"
      :user="activeUser"
      @back="goBack"
      @select-app="openApplication"
      @logout="logout()"
    />

    <!-- Base Message (Tin nhắn trực tiếp - 05-messenger.html) -->
    <BaseMessageView
      v-else-if="currentView === 'message' && activeUser"
      :user="activeUser"
      @back="goBack"
      @select-app="openApplication"
      @logout="logout()"
    />

    <!-- Quản trị hệ thống (07-admin-social.html) -->
    <BaseAdminView
      v-else-if="currentView === 'admin' && activeUser"
      :user="activeUser"
      @back="goBack"
      @select-app="openApplication"
      @logout="logout()"
    />

    <!-- QUICK NAVIGATION OVERLAY -->
    <aside
      v-if="activeUser && currentView !== 'login' && currentView !== 'booting'"
      class="fixed bottom-4 right-4 z-50 bg-slate-900 text-white px-3 py-2 rounded-lg shadow-xl text-xs flex items-center gap-2 border border-slate-700 select-none"
    >
      <span class="text-slate-400 font-mono">Màn hình:</span>
      <select
        :value="currentView === 'learning' ? activeSquareTab : currentView"
        class="bg-slate-800 text-white text-xs rounded px-2 py-1 outline-none border border-slate-700 cursor-pointer font-medium"
        @change="(e) => openApplication((e.target as HTMLSelectElement).value)"
      >
        <option value="welcome">01 - Cổng ứng dụng (Welcome Portal)</option>
        <option value="inside">03 - Bảng tin MXH (Base Inside)</option>
        <option value="message">05 - Trò chuyện Messenger</option>
        <option value="admin">07 - Quản trị kiểm duyệt</option>
        <optgroup label="Hệ sinh thái Tri thức Base Square">
          <option value="questions">09 - Hỏi đáp Q&amp;A</option>
          <option value="knowledge">14 - Knowledge Graph</option>
          <option value="library">08 - Thư viện tài liệu</option>
          <option value="reward">12 - Bảng xếp hạng</option>
          <option value="search">15 - Tìm kiếm AI</option>
          <option value="profile">06 - Hồ sơ cá nhân</option>
        </optgroup>
      </select>
    </aside>

  </div>
</template>
