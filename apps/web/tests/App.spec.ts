import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import App from '../src/App.vue'
import { ApiError, authApi, type AuthUser } from '../src/api/auth'

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
    sessionStorage.clear()
    localStorage.clear()
  })

  it('shows the real login form when there is no saved session', async () => {
    const wrapper = mount(App)
    await flushPromises()

    expect(wrapper.get('h2').text()).toBe('Chào mừng quay lại')
    expect(wrapper.get('#login-email').attributes('value')).toBeUndefined()
    expect(wrapper.text()).not.toContain('Tài khoản demo')
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
    expect(wrapper.text()).toContain('member@example.com')
    expect(sessionStorage.getItem('OpenHello.auth.token')).toBe('active-token')
  })

  it('restores an active session from local storage', async () => {
    localStorage.setItem('OpenHello.auth.token', 'remembered-token')
    mockedAuthApi.inspect.mockResolvedValue({ user })

    const wrapper = mount(App)
    await flushPromises()

    expect(mockedAuthApi.inspect).toHaveBeenCalledWith('remembered-token')
    expect(wrapper.text()).toContain('Đã đăng nhập')
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
