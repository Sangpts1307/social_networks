import { ApiError } from './auth'

export interface SocialChannelDto {
  id: string
  code?: string
  name: string
  description: string | null
  visibility?: 'organization' | 'members'
  membership_policy?: 'open' | 'managed'
  owner_user_id?: string
  created_at?: string
}

export interface SocialChannelMemberDto {
  id: string
  user_id: string
  member_role: 'owner' | 'moderator' | 'member'
  joined_at: string
}

export interface SocialPostDto {
  id: string
  channel_id: string
  author_user_id: string
  post_type: 'update' | 'news' | 'announcement' | 'event'
  title: string | null
  body_markdown?: string
  body_plaintext: string
  visibility?: 'organization' | 'channel'
  publication_status?: 'draft' | 'published'
  moderation_status?: 'clear' | 'flagged' | 'hidden'
  published_at: string | null
  created_at?: string
  updated_at?: string
  edited_at?: string | null
  comments_locked_at?: string | null
  reaction_count: number
  comment_count: number
  my_reaction: 'like' | 'love' | 'sad' | 'angry' | null
  is_saved: boolean
  is_pinned?: boolean | number
  pin_expires_at?: string | null
  image_url?: string | null
}

export interface SocialAttachmentDto {
  id: string
  attachment_type: 'image' | 'file'
  original_filename: string
  mime_type: string
  size_bytes: number
}

export interface SocialCommentDto {
  id: string
  post_id: string
  parent_comment_id: string | null
  author_user_id: string
  body_markdown?: string
  body_plaintext: string
  created_at: string
  updated_at?: string
  edited_at?: string | null
}

export interface DirectoryUserDto {
  id: string
  display_name: string
  employee_code?: string | null
  primary_org_unit_id?: string | null
  primary_org_unit?: { id: string; code: string; name: string; unit_type: string } | null
  job_title: string | null
  avatar_key?: string | null
  bio: string | null
  skills: string[]
}

export interface FollowStatsDto {
  following_count: number
  followers_count: number
}

export interface CursorPage<T> {
  data: T[]
  meta: {
    next_cursor: string | null
    has_more?: boolean
    limit?: number
  }
}

const API_BASE = (import.meta.env.VITE_API_BASE_URL ?? '/api').replace(/\/$/, '')

async function request<T>(path: string, token: string, method = 'GET', payload?: unknown): Promise<T> {
  let response: Response
  try {
    response = await fetch(`${API_BASE}${path}`, {
      method,
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
        ...(payload === undefined ? {} : { 'Content-Type': 'application/json' }),
      },
      ...(payload === undefined ? {} : { body: JSON.stringify(payload) }),
    })
  } catch {
    throw new ApiError('Không thể kết nối tới máy chủ. Hãy kiểm tra Gateway và Social Service.', 0)
  }

  if (response.status === 204) {
    return undefined as T
  }

  const body = await response.json().catch(() => ({})) as {
    message?: string
    code?: string
    errors?: Record<string, string[]>
  }
  if (!response.ok) {
    const firstError = body.errors ? Object.values(body.errors).flat()[0] : undefined
    throw new ApiError(
      String(firstError ?? body.message ?? 'Không thể thực hiện thao tác mạng xã hội.'),
      response.status,
      body.code,
      body.errors,
    )
  }
  return body as T
}

export const socialApi = {
  channels(token: string, cursor?: string) {
    const query = cursor ? `?cursor=${encodeURIComponent(cursor)}` : ''
    return request<CursorPage<SocialChannelDto>>(`/v1/social/channels${query}`, token)
  },
  channel(token: string, channelId: string) {
    return request<{ data: SocialChannelDto }>(`/v1/social/channels/${encodeURIComponent(channelId)}`, token)
  },
  createChannel(
    token: string,
    payload: {
      code: string
      name: string
      description?: string | null
      visibility?: 'organization' | 'members'
      membership_policy?: 'open' | 'managed'
    },
  ) {
    return request<{ data: SocialChannelDto }>('/v1/social/channels', token, 'POST', {
      visibility: 'organization',
      membership_policy: 'open',
      ...payload,
    })
  },
  channelMembers(token: string, channelId: string, cursor?: string) {
    const query = cursor ? `?cursor=${encodeURIComponent(cursor)}` : ''
    return request<CursorPage<SocialChannelMemberDto>>(
      `/v1/social/channels/${encodeURIComponent(channelId)}/members${query}`,
      token,
    )
  },
  joinChannel(token: string, channelId: string) {
    return request<{ data: { channel_id: string; is_member: boolean } }>(
      `/v1/social/channels/${encodeURIComponent(channelId)}/membership`,
      token,
      'PUT',
      {},
    )
  },
  leaveChannel(token: string, channelId: string) {
    return request<void>(
      `/v1/social/channels/${encodeURIComponent(channelId)}/membership`,
      token,
      'DELETE',
    )
  },
  posts(
    token: string,
    filters: {
      channel_id?: string
      author_user_id?: string
      saved?: '0' | '1'
      post_type?: 'update' | 'news' | 'announcement' | 'event'
      q?: string
      cursor?: string
      limit?: number
    } = {},
  ) {
    const params = new URLSearchParams()
    if (filters.channel_id) params.set('channel_id', filters.channel_id)
    if (filters.author_user_id) params.set('author_user_id', filters.author_user_id)
    if (filters.saved) params.set('saved', filters.saved)
    if (filters.post_type) params.set('post_type', filters.post_type)
    if (filters.q && filters.q.trim().length >= 2) params.set('q', filters.q.trim())
    if (filters.cursor) params.set('cursor', filters.cursor)
    if (filters.limit) params.set('limit', String(filters.limit))
    const query = params.size ? `?${params.toString()}` : ''
    return request<CursorPage<SocialPostDto>>(`/v1/social/posts${query}`, token)
  },
  post(token: string, postId: string) {
    return request<{ data: SocialPostDto }>(`/v1/social/posts/${encodeURIComponent(postId)}`, token)
  },
  createPost(
    token: string,
    payload: {
      channel_id: string
      body_markdown: string
      title?: string | null
      post_type?: 'update' | 'news' | 'announcement' | 'event'
      publication_status?: 'draft' | 'published'
      event?: {
        starts_at: string
        ends_at: string
        location_text?: string
        rsvp_enabled?: boolean
      }
    },
  ) {
    return request<{ data: SocialPostDto }>('/v1/social/posts', token, 'POST', {
      post_type: 'update',
      publication_status: 'published',
      ...payload,
    })
  },
  async uploadAttachment(token: string, postId: string, file: File) {
    const form = new FormData()
    form.append('file', file)
    const response = await fetch(`${API_BASE}/v1/social/posts/${encodeURIComponent(postId)}/attachments`, {
      method: 'POST',
      headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
      body: form,
    })
    const body = await response.json().catch(() => ({})) as { data?: { id: string }; message?: string; errors?: Record<string, string[]> }
    if (!response.ok) throw new ApiError(Object.values(body.errors ?? {}).flat()[0] ?? body.message ?? 'Không thể tải tệp đính kèm.', response.status)
    return body as { data: { id: string } }
  },
  attachments(token: string, postId: string) {
    return request<{ data: SocialAttachmentDto[] }>(`/v1/social/posts/${encodeURIComponent(postId)}/attachments`, token)
  },
  attachmentUrl(token: string, postId: string, attachmentId: string) {
    return request<{ data: { url: string } }>(`/v1/social/posts/${encodeURIComponent(postId)}/attachments/${encodeURIComponent(attachmentId)}/download`, token)
  },
  pinPost(token: string, postId: string, pinned: boolean, expiresAt?: string | null) {
    const path = `/v1/social/posts/${encodeURIComponent(postId)}/pin`
    return pinned
      ? request<void>(path, token, 'PUT', expiresAt ? { expires_at: expiresAt } : {})
      : request<void>(path, token, 'DELETE')
  },
  updatePost(
    token: string,
    postId: string,
    payload: {
      body_markdown?: string
      title?: string | null
      publication_status?: 'published'
    },
  ) {
    return request<{ data: SocialPostDto }>(
      `/v1/social/posts/${encodeURIComponent(postId)}`,
      token,
      'PATCH',
      payload,
    )
  },
  deletePost(token: string, postId: string) {
    return request<void>(`/v1/social/posts/${encodeURIComponent(postId)}`, token, 'DELETE')
  },
  react(token: string, postId: string, reactionType: 'like' | 'love' | 'sad' | 'angry' | null) {
    const path = `/v1/social/posts/${encodeURIComponent(postId)}/reaction`
    return reactionType
      ? request<{ data: { post_id: string; reaction_type: string } }>(path, token, 'PUT', { reaction_type: reactionType })
      : request<void>(path, token, 'DELETE')
  },
  save(token: string, postId: string, saved: boolean) {
    const path = `/v1/social/posts/${encodeURIComponent(postId)}/save`
    return saved
      ? request<{ data: { post_id: string; is_saved: boolean } }>(path, token, 'PUT', {})
      : request<void>(path, token, 'DELETE')
  },
  comments(token: string, postId: string, cursor?: string) {
    const query = cursor ? `?cursor=${encodeURIComponent(cursor)}` : ''
    return request<CursorPage<SocialCommentDto>>(
      `/v1/social/posts/${encodeURIComponent(postId)}/comments${query}`,
      token,
    )
  },
  createComment(token: string, postId: string, body_markdown: string, parent_comment_id?: string) {
    return request<{ data: SocialCommentDto }>(
      `/v1/social/posts/${encodeURIComponent(postId)}/comments`,
      token,
      'POST',
      {
        body_markdown,
        ...(parent_comment_id ? { parent_comment_id } : {}),
      },
    )
  },
  deleteComment(token: string, commentId: string) {
    return request<void>(`/v1/social/comments/${encodeURIComponent(commentId)}`, token, 'DELETE')
  },
  followStats(token: string) {
    return request<{ data: FollowStatsDto }>('/v1/social/follow-stats', token)
  },
  birthdays(token: string, month: number) {
    return request<{ data: { id: string; name: string; avatar: string | null; position: string | null; date: string }[] }>(`/v1/directory/birthdays?month=${month}`, token)
  },
  directoryUsers(token: string, q?: string, cursor?: string) {
    const params = new URLSearchParams()
    if (q) params.set('q', q)
    if (cursor) params.set('cursor', cursor)
    const query = params.size ? `?${params.toString()}` : ''
    return request<CursorPage<DirectoryUserDto>>(`/v1/directory/users${query}`, token)
  },
}
