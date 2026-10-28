import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import App from '../src/App.vue'
import { ApiError, authApi, type AuthUser } from '../src/api/auth'
import { profileApi } from '../src/api/profile'
import { portalCategory } from '../src/components/layout/portalState'

vi.mock('../src/api/auth', async () => {
  const actual = await vi.importActual<typeof import('../src/api/auth')>('../src/api/auth')
  return {
    ...actual,
    authApi: {
      login: vi.fn(),
      inspect: vi.fn(),
      me: vi.fn(),
      changePassword: vi.fn(),
      logout: vi.fn(),
    },
  }
})

vi.mock('../src/api/profile', () => ({ profileApi: { me: vi.fn(), update: vi.fn() } }))

const user: AuthUser = {
  id: 'user-1',
  organization_id: 'org-1',
  email: 'member@example.com',
  display_name: 'Nguyen Van A',
  must_change_password: false,
}

const mockedAuthApi = vi.mocked(authApi)

describe('App authentication flow', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    sessionStorage.clear()
    localStorage.clear()
    window.history.replaceState({}, '', '/')
    portalCategory.value = 'ALL'
  })

  it('shows the real login form when there is no saved session', async () => {
    const wrapper = mount(App)
    await flushPromises()

    expect(wrapper.get('h2').text()).toBe('Chào mừng quay lại')
    expect(wrapper.get('#login-email').attributes('value')).toBeUndefined()
    expect((wrapper.get('input[type="checkbox"]').element as HTMLInputElement).checked).toBe(true)
    expect(wrapper.text()).not.toContain('Tài khoản demo')
  })

  it('fills the local demo account from the selector without submitting the form', async () => {
    const wrapper = mount(App)
    await flushPromises()

    await wrapper.get('#demo-account').setValue('qa_expert@gmail.com')
    expect((wrapper.get('#login-email').element as HTMLInputElement).value).toBe('qa_expert@gmail.com')
    expect((wrapper.get('#login-password').element as HTMLInputElement).value).toBe('12345678')
    expect(mockedAuthApi.login).not.toHaveBeenCalled()
  })

  it('logs in through the API and shows the authenticated user', async () => {
    mockedAuthApi.login.mockResolvedValue({
      access_token: 'active-token',
      expires_at: '2026-09-18T00:00:00Z',
      user,
    })
    const wrapper = mount(App)
    await flushPromises()

    await wrapper.get('#login-email').setValue('MEMBER@EXAMPLE.COM')
    await wrapper.get('#login-password').setValue('Correct-password-1!')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(mockedAuthApi.login).toHaveBeenCalledWith('member@example.com', 'Correct-password-1!')
    expect(wrapper.text()).toContain('Nguyen Van A')
    expect(wrapper.text()).not.toContain('member@example.com')
    expect(localStorage.getItem('OpenHello.auth.token')).toBe('active-token')
  })

  it('restores an active session from local storage', async () => {
    localStorage.setItem('OpenHello.auth.token', 'remembered-token')
    mockedAuthApi.inspect.mockResolvedValue({ user })

    const wrapper = mount(App)
    await flushPromises()

    expect(mockedAuthApi.inspect).toHaveBeenCalledWith('remembered-token')
    expect(wrapper.text()).toContain('BASE.VN')
    expect(wrapper.text()).not.toContain('Phân hệ Quản trị')
  })

  it('shows administration only to an account with its permission', async () => {
    sessionStorage.setItem('OpenHello.auth.token', 'admin-token')
    mockedAuthApi.inspect.mockResolvedValue({ user: { ...user, permissions: ['identity.users.read'] } })
    const wrapper = mount(App)
    await flushPromises()
    expect(wrapper.text()).toContain('Phân hệ Quản trị')
  })

  it('restores the requested Social page from its URL', async () => {
    window.history.replaceState({}, '', '/?app=inside')
    localStorage.setItem('OpenHello.auth.token', 'remembered-token')
    mockedAuthApi.inspect.mockResolvedValue({ user })

    const wrapper = mount(App)
    await flushPromises()

    expect(wrapper.text()).toContain('Bảng tin Home')
    expect(window.location.search).toBe('?app=inside')
  })

  it('keeps the Inside layout when opening and leaving the personal page', async () => {
    window.history.replaceState({}, '', '/?app=inside')
    sessionStorage.setItem('OpenHello.auth.token', 'shared-token')
    mockedAuthApi.inspect.mockResolvedValue({ user })
    vi.mocked(profileApi.me).mockResolvedValue({
      id: user.id, display_name: user.display_name, email: user.email,
      employee_code: 'NV-001', primary_org_unit_id: null, primary_org_unit: null,
      job_title: 'Kỹ sư phần mềm', bio: 'Giới thiệu cá nhân', phone: null, location: null, skills: [],
    })

    const wrapper = mount(App)
    await flushPromises()
    await wrapper.get('button[title="Xem trang cá nhân"]').trigger('click')
    await flushPromises()

    expect(window.location.search).toBe('?app=inside-profile')
    expect(wrapper.find('section[aria-label="Trang cá nhân"]').exists()).toBe(true)
    expect(wrapper.find('nav[aria-label="Điều hướng mạng xã hội"]').exists()).toBe(true)
    expect(wrapper.find('section[aria-label="Tầm nhìn và sứ mệnh công ty"]').exists()).toBe(true)
    expect(wrapper.find('aside[aria-label="Liên hệ nhanh"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Giới thiệu cá nhân')

    await wrapper.find('aside[aria-label="Liên hệ nhanh"]').findAll('button')[0]!.trigger('click')
    expect(wrapper.find('section[aria-label="Khung trò chuyện"]').exists()).toBe(true)
    await wrapper.findAll('button').find(button => button.text().includes('Bảng tin Home'))!.trigger('click')
    expect(window.location.search).toBe('?app=inside')
    expect(wrapper.text()).toContain('Chia sẻ với đồng nghiệp')
  })

  it('keeps the saved session when the server is temporarily unreachable', async () => {
    localStorage.setItem('OpenHello.auth.token', 'remembered-token')
    mockedAuthApi.inspect.mockRejectedValue(new ApiError('Không kết nối được Gateway.', 0))

    const wrapper = mount(App)
    await flushPromises()

    expect(wrapper.text()).toContain('Phiên đăng nhập vẫn được giữ')
    expect(localStorage.getItem('OpenHello.auth.token')).toBe('remembered-token')
  })

  it('clears an expired session and returns to login', async () => {
    localStorage.setItem('OpenHello.auth.token', 'expired-token')
    mockedAuthApi.inspect.mockRejectedValue(new ApiError('Unauthenticated.', 401))

    const wrapper = mount(App)
    await flushPromises()

    expect(wrapper.text()).toContain('Phiên đăng nhập không còn hiệu lực')
    expect(localStorage.getItem('OpenHello.auth.token')).toBeNull()
  })

  it('opens the shared account from the portal and keeps the same session for Social', async () => {
    sessionStorage.setItem('OpenHello.auth.token', 'shared-token')
    mockedAuthApi.inspect.mockResolvedValue({ user })
    vi.mocked(profileApi.me).mockResolvedValue({
      id: user.id, display_name: user.display_name, email: user.email,
      employee_code: 'NV-001', primary_org_unit_id: 'unit-1',
      primary_org_unit: { id: 'unit-1', code: 'DEV', name: 'Phòng Phát triển', unit_type: 'department' },
      job_title: 'Kỹ sư phần mềm', bio: null, phone: null, location: null, skills: [],
    })
    const wrapper = mount(App)
    await flushPromises()

    await wrapper.get('button[title="Xem hồ sơ"]').trigger('click')
    await flushPromises()
    expect(profileApi.me).toHaveBeenCalledWith('shared-token')
    expect(wrapper.text()).toContain('Thông tin tài khoản')
    expect(wrapper.text()).toContain('TẤT CẢ ỨNG DỤNG')
    expect(wrapper.text()).not.toContain('Khối Tri thức và Học tập')
    expect(wrapper.text()).not.toContain('Phân hệ Quản trị')
    expect(wrapper.text()).toContain('Tên nhân viên')
    expect((wrapper.get('input[title="Mã nhân viên do quản trị viên quản lý"]').element as HTMLInputElement).value).toBe('NV-001')

    await wrapper.get('nav[aria-label="Ứng dụng trên cổng"] button:nth-child(4)').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Base Inside')
    await wrapper.findAll('.group').find(card => card.text().includes('Base Inside'))!.trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Bảng tin Home')
    expect(sessionStorage.getItem('OpenHello.auth.token')).toBe('shared-token')
  })

  it('opens a compact conversation from the Inside home screen', async () => {
    sessionStorage.setItem('OpenHello.auth.token', 'shared-token')
    window.history.replaceState({}, '', '/?app=inside')
    mockedAuthApi.inspect.mockResolvedValue({ user })
    const wrapper = mount(App)
    await flushPromises()

    expect(wrapper.findAll('article')[0]!.text()).toContain('Bài viết được ghim')
    expect(wrapper.findAll('article')[0]!.text()).toContain('Lịch trình sắp tới của công ty')
    await wrapper.find('aside[aria-label="Liên hệ nhanh"]').findAll('button')[0]!.trigger('click')
    expect(wrapper.find('section[aria-label="Khung trò chuyện"]').exists()).toBe(true)
    expect(wrapper.find('section[aria-label="Khung trò chuyện"]').text()).toContain('Võ Thành Luân')

    await wrapper.get('section[aria-label="Khung trò chuyện"] input').setValue('Đã nhận được nhé')
    await wrapper.get('section[aria-label="Khung trò chuyện"] form').trigger('submit')
    expect(wrapper.find('section[aria-label="Khung trò chuyện"]').text()).toContain('Đã nhận được nhé')
  })

  it('requires first-login password change and returns to login after success', async () => {
    mockedAuthApi.login.mockResolvedValue({
      access_token: 'limited-token',
      expires_at: '2026-09-18T00:00:00Z',
      user: { ...user, must_change_password: true },
    })
    mockedAuthApi.changePassword.mockResolvedValue({ message: 'Password changed.' })
    mockedAuthApi.logout.mockResolvedValue(undefined)
    const wrapper = mount(App)
    await flushPromises()

    await wrapper.get('#login-email').setValue(user.email)
    await wrapper.get('#login-password').setValue('Temporary-password-1!')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.text()).toContain('Đổi mật khẩu lần đầu')

    const inputs = wrapper.findAll('input[type="password"]')
    await inputs[0]!.setValue('Temporary-password-1!')
    await inputs[1]!.setValue('New-secure-password-2@')
    await inputs[2]!.setValue('New-secure-password-2@')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(mockedAuthApi.changePassword).toHaveBeenCalledWith(
      'limited-token',
      'Temporary-password-1!',
      'New-secure-password-2@',
      'New-secure-password-2@',
    )
    expect(wrapper.text()).toContain('Đổi mật khẩu thành công')
    expect(sessionStorage.getItem('OpenHello.auth.token')).toBeNull()
  })

  it('shows a locked-account error returned by the API', async () => {
    mockedAuthApi.login.mockRejectedValue(new ApiError('Tài khoản đã bị khóa.', 403, 'ACCOUNT_LOCKED'))
    const wrapper = mount(App)
    await flushPromises()

    await wrapper.get('#login-email').setValue(user.email)
    await wrapper.get('#login-password').setValue('Wrong-password-1!')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Tài khoản đã bị khóa.')
  })
})
