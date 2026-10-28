<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ApiError, authApi, canOpenAdmin, type AuthUser } from './api/auth'
import { clearToken, readToken, storeToken } from './auth/storage'
import ChangePasswordView from './views/ChangePasswordView.vue'
import LoginView from './views/LoginView.vue'
import LearningView from './views/LearningView.vue'
import WelcomeView from './views/WelcomeView.vue'
import BaseProfileView from './views/modules/BaseProfileView.vue'

// Distinct primary application views
import BaseInsideView from './views/modules/BaseInsideView.vue'
import BaseMessageView from './views/modules/BaseMessageView.vue'
import BaseAdminView from './views/modules/BaseAdminView.vue'

type View =
  | 'booting'
  | 'connection-error'
  | 'login'
  | 'change-password'
  | 'welcome'
  | 'learning'
  | 'inside'
  | 'profile'
  | 'inside-profile'
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
      if (!activeUser.value) {
        currentView.value = 'login'
      } else if (activeUser.value.must_change_password) {
        currentView.value = 'change-password'
      } else if (e.state && e.state.view) {
        currentView.value = e.state.view === 'admin' && !canOpenAdmin(activeUser.value) ? 'welcome' : e.state.view === 'square' ? 'learning' : e.state.view
        if (e.state.tab) activeSquareTab.value = e.state.tab === 'home' ? 'questions' : e.state.tab
        if (currentView.value === 'learning' && activeSquareTab.value === 'profile') currentView.value = 'profile'
      } else {
        const params = new URLSearchParams(window.location.search)
        const rawApp = params.get('app')
        const tab = params.get('tab')
        if (rawApp === 'square' || rawApp === 'learning') {
          currentView.value = tab === 'profile' ? 'profile' : 'learning'
          activeSquareTab.value = (tab === 'home' ? 'questions' : tab) || 'questions'
        } else if (rawApp && ['inside', 'inside-profile', 'message', 'admin', 'profile', 'welcome'].includes(rawApp)) {
          currentView.value = rawApp === 'admin' && !canOpenAdmin(activeUser.value) ? 'welcome' : rawApp as View
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
  currentView.value = 'booting'
  try {
    const session = await authApi.inspect(savedToken)
    token.value = savedToken
    activeUser.value = session.user
    if (session.user.must_change_password) {
      currentView.value = 'change-password'
      updateHistory('change-password', undefined, false)
    } else {
      const requested = new URLSearchParams(window.location.search).get('app')
      const allowed: View[] = ['welcome', 'inside', 'inside-profile', 'profile', 'message', 'admin', 'learning']
      const initialView = requested === 'admin' && !canOpenAdmin(session.user) ? 'welcome' : requested === 'square' ? 'learning' : allowed.includes(requested as View) ? requested as View : 'welcome'
      currentView.value = initialView
      if (initialView === 'learning') activeSquareTab.value = new URLSearchParams(window.location.search).get('tab') || 'questions'
      updateHistory(initialView, initialView === 'learning' ? activeSquareTab.value : undefined, false)
    }
  } catch (error) {
    if (error instanceof ApiError && (error.status === 401 || error.status === 403)) {
      clearToken()
      token.value = null
      activeUser.value = null
      currentView.value = 'login'
      globalMessage.value = 'Phiên đăng nhập không còn hiệu lực. Vui lòng đăng nhập lại.'
      updateHistory('login', undefined, false)
    } else {
      currentView.value = 'connection-error'
      globalMessage.value = 'Chưa kết nối được hệ thống. Phiên đăng nhập vẫn được giữ; hãy thử lại.'
    }
  }
}

function updateHistory(view: View, tab?: string, push = true) {
  if (typeof window === 'undefined') return
  try {
    const url = new URL(window.location.href)
    const appQuery = view === 'learning' ? 'square' : view
    url.searchParams.set('app', appQuery)
    if (view !== 'inside') url.searchParams.delete('company')
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
  if (!activeUser.value || activeUser.value.must_change_password) return
  if (appId === 'square' || appId === 'learning') {
    currentView.value = subTab === 'profile' ? 'profile' : 'learning'
    activeSquareTab.value = subTab || 'questions'
    updateHistory('learning', activeSquareTab.value, true)
  } else if (appId === 'library' || appId === 'knowledge' || appId === 'reward' || appId === 'search' || appId === 'questions') {
    currentView.value = 'learning'
    activeSquareTab.value = appId
    updateHistory('learning', appId, true)
  } else if (appId === 'profile' || appId === 'square-profile') {
    currentView.value = 'profile'
    updateHistory('profile', undefined, true)
  } else if (appId === 'inside-profile') {
    currentView.value = 'inside-profile'
    updateHistory('inside-profile', undefined, true)
  } else if (appId === 'inside') {
    currentView.value = 'inside'
    updateHistory('inside', undefined, true)
  } else if (appId === 'message') {
    currentView.value = 'message'
    updateHistory('message', undefined, true)
  } else if (appId === 'admin') {
    if (!canOpenAdmin(activeUser.value)) return
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
  if (tab === 'profile') {
    openApplication('profile')
    return
  }
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

    <div v-else-if="currentView === 'connection-error'" class="min-h-screen grid place-items-center bg-slate-50 px-4">
      <div class="max-w-md rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
        <h1 class="text-xl font-bold">Chưa kết nối được hệ thống</h1>
        <p class="mt-2 text-sm text-slate-600">{{ globalMessage }}</p>
        <button class="mt-5 rounded bg-blue-600 px-4 py-2 font-semibold text-white" @click="restoreSession">Thử lại</button>
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
      v-else-if="(currentView === 'inside' || currentView === 'inside-profile') && activeUser"
      :user="activeUser"
      :token="token!"
      :page="currentView === 'inside-profile' ? 'profile' : 'feed'"
      @back="goBack"
      @select-app="openApplication"
      @logout="logout()"
    />

    <BaseProfileView
      v-else-if="currentView === 'profile' && activeUser && token"
      :user="activeUser"
      :token="token"
      @back="openApplication('welcome')"
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
      v-else-if="currentView === 'admin' && activeUser && canOpenAdmin(activeUser)"
      :user="activeUser"
      @back="goBack"
      @select-app="openApplication"
      @logout="logout()"
    />


  </div>
</template>
