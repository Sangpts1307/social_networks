export interface AuthUser {
  id: string
  organization_id: string
  email: string
  display_name: string
  must_change_password: boolean
}

export interface AuthSession {
  access_token: string
  expires_at: string
  user: AuthUser
}

interface ApiErrorBody {
  message?: string
  code?: string
  errors?: Record<string, string[]>
}

export class ApiError extends Error {
  readonly status: number
  readonly code?: string
  readonly errors?: Record<string, string[]>

  constructor(
    message: string,
    status: number,
    code?: string,
    errors?: Record<string, string[]>,
  ) {
    super(message)
    this.status = status
    this.code = code
    this.errors = errors
  }
}

const API_BASE = (import.meta.env.VITE_API_BASE_URL ?? '/api').replace(/\/$/, '')

function readableMessage(body: ApiErrorBody, status: number): string {
  const validationMessage = body.errors ? Object.values(body.errors).flat()[0] : undefined
  if (validationMessage) return validationMessage
  if (body.code === 'PASSWORD_CHANGE_REQUIRED') return 'Bạn cần đổi mật khẩu trước khi tiếp tục.'
  if (status === 401) return 'Email, mật khẩu hoặc phiên đăng nhập không hợp lệ.'
  if (status === 403) return body.message ?? 'Tài khoản hiện không thể truy cập.'
  if (status === 429) return 'Bạn thao tác quá nhanh. Vui lòng thử lại sau ít phút.'
  if (status >= 500) return 'Hệ thống tạm thời chưa sẵn sàng. Vui lòng thử lại.'
  return body.message ?? 'Không thể hoàn thành yêu cầu.'
}

async function request<T>(path: string, init: RequestInit = {}, token?: string): Promise<T> {
  let response: Response
  try {
    response = await fetch(`${API_BASE}${path}`, {
      ...init,
      headers: {
        Accept: 'application/json',
        ...(init.body ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...init.headers,
      },
    })
  } catch {
    throw new ApiError('Không thể kết nối tới máy chủ. Hãy kiểm tra Gateway đang chạy.', 0)
  }

  const body = response.status === 204
    ? undefined
    : await response.json().catch(() => ({})) as ApiErrorBody
  if (!response.ok) {
    throw new ApiError(readableMessage(body ?? {}, response.status), response.status, body?.code, body?.errors)
  }
  return body as T
}

export const authApi = {
  login(email: string, password: string) {
    return request<AuthSession>('/v1/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    })
  },
  inspect(token: string) {
    return request<{ user: AuthUser }>('/v1/auth/session', {}, token)
  },
  me(token: string) {
    return request<{ user: AuthUser }>('/v1/me', {}, token)
  },
  changePassword(token: string, currentPassword: string, password: string, passwordConfirmation: string) {
    return request<{ message: string }>('/v1/auth/password', {
      method: 'POST',
      body: JSON.stringify({
        current_password: currentPassword,
        password,
        password_confirmation: passwordConfirmation,
      }),
    }, token)
  },
  logout(token: string) {
    return request<void>('/v1/auth/logout', { method: 'POST', body: '{}' }, token)
  },
}
