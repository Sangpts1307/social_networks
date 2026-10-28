import { ApiError } from './auth'

export interface AccountProfile {
  id: string
  display_name: string
  employee_code?: string | null
  primary_org_unit_id?: string | null
  primary_org_unit?: { id: string; code: string; name: string; unit_type: string } | null
  email: string
  job_title: string | null
  bio: string | null
  phone: string | null
  location: string | null
  skills: string[]
}

async function send(token: string, method: 'GET' | 'PATCH', values?: object): Promise<AccountProfile> {
  let response: Response
  try {
    response = await fetch(`${(import.meta.env.VITE_API_BASE_URL ?? '/api').replace(/\/$/, '')}/v1/me/profile`, {
      method,
      headers: { Accept: 'application/json', Authorization: `Bearer ${token}`, ...(values ? { 'Content-Type': 'application/json' } : {}) },
      ...(values ? { body: JSON.stringify(values) } : {}),
    })
  } catch {
    throw new ApiError('Không kết nối được Gateway.', 0)
  }
  const body = await response.json().catch(() => ({}))
  if (!response.ok) {
    const validation = body.errors ? Object.values(body.errors).flat()[0] : undefined
    throw new ApiError(String(validation ?? body.message ?? 'Không tải được hồ sơ.'), response.status)
  }
  return body.data as AccountProfile
}

export const profileApi = {
  me: (token: string) => send(token, 'GET'),
  update: (token: string, values: Pick<AccountProfile, 'bio' | 'phone' | 'location' | 'skills'>) => send(token, 'PATCH', values),
}
