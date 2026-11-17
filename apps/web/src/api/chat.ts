import { ApiError } from './auth'

export interface ConversationDto {
  id?: string
  _id?: string
  organizationId: string
  type: 'direct' | 'group'
  title: string | null
  directKey?: string | null
  createdByUserId: string
  status: string
  memberCount: number
  unread_count?: number
  lastMessage?: {
    id: string
    sentAt: string
    senderUserId: string
  } | null
  createdAt?: string
  updatedAt?: string
}

export interface ConversationMemberDto {
  userId: string
  memberRole: 'owner' | 'member'
  joinedAt: string
  lastReadAt?: string | null
}

export interface ChatAttachmentDto {
  id: string
  filename: string
  mime_type: string
  size_bytes: number
  url?: string
}

export interface ChatMessageDto {
  id?: string
  _id?: string
  conversationId: string
  clientMessageId: string
  senderUserId: string
  messageType: string
  bodyMarkdown: string
  bodyPlaintext: string
  attachments?: ChatAttachmentDto[]
  sentAt: string
  createdAt?: string
}

export interface ChatCursorPage<T> {
  data: T[]
  meta: {
    next_cursor: string | null
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
    throw new ApiError('Không thể kết nối tới máy chủ Chat.', 0)
  }

  if (response.status === 204) {
    return undefined as T
  }

  const body = await response.json().catch(() => ({})) as { message?: string; code?: string; errors?: Record<string, string[]> }
  if (!response.ok) {
    const validation = body.errors ? Object.values(body.errors).flat()[0] : undefined
    throw new ApiError(String(validation ?? body.message ?? 'Không thể hoàn thành yêu cầu chat.'), response.status, body.code, body.errors)
  }

  return body as T
}

export const chatApi = {
  conversations(token: string, cursor?: string) {
    const query = cursor ? `?cursor=${encodeURIComponent(cursor)}` : ''
    return request<ChatCursorPage<ConversationDto>>(`/v1/chat/conversations${query}`, token)
  },

  createConversation(
    token: string,
    payload: { type: 'direct' | 'group'; title?: string | null; member_ids: string[] },
  ) {
    return request<{ data: ConversationDto }>('/v1/chat/conversations', token, 'POST', payload)
  },

  conversation(token: string, conversationId: string) {
    return request<{ data: ConversationDto; members: ConversationMemberDto[] }>(
      `/v1/chat/conversations/${encodeURIComponent(conversationId)}`,
      token,
    )
  },

  updateConversation(token: string, conversationId: string, title: string) {
    return request<{ data: ConversationDto }>(
      `/v1/chat/conversations/${encodeURIComponent(conversationId)}`,
      token,
      'PATCH',
      { title },
    )
  },

  addMember(token: string, conversationId: string, userId: string) {
    return request<void>(
      `/v1/chat/conversations/${encodeURIComponent(conversationId)}/members`,
      token,
      'POST',
      { user_id: userId },
    )
  },

  removeMember(token: string, conversationId: string, userId: string) {
    return request<void>(
      `/v1/chat/conversations/${encodeURIComponent(conversationId)}/members/${encodeURIComponent(userId)}`,
      token,
      'DELETE',
    )
  },

  messages(token: string, conversationId: string, cursor?: string) {
    const query = cursor ? `?cursor=${encodeURIComponent(cursor)}` : ''
    return request<ChatCursorPage<ChatMessageDto>>(
      `/v1/chat/conversations/${encodeURIComponent(conversationId)}/messages${query}`,
      token,
    )
  },

  sendMessage(token: string, conversationId: string, bodyMarkdown: string, clientMessageId: string, attachments?: string[]) {
    return request<{ data: ChatMessageDto }>(
      `/v1/chat/conversations/${encodeURIComponent(conversationId)}/messages`,
      token,
      'POST',
      { client_message_id: clientMessageId, body_markdown: bodyMarkdown, attachments: attachments ?? [] },
    )
  },

  async uploadAttachment(token: string, conversationId: string, file: File) {
    const formData = new FormData()
    formData.append('file', file)
    const response = await fetch(`${API_BASE}/v1/chat/conversations/${encodeURIComponent(conversationId)}/attachments`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: formData,
    })
    if (!response.ok) {
      throw new ApiError('Không thể tải tệp đính kèm lên.', response.status)
    }
    return response.json() as Promise<{ data: { id: string; scan_status: string } }>
  },

  downloadAttachment(token: string, conversationId: string, attachmentId: string) {
    return request<{ data: { url: string; expires_at: string } }>(
      `/v1/chat/conversations/${encodeURIComponent(conversationId)}/attachments/${encodeURIComponent(attachmentId)}/download`,
      token,
    )
  },

  markRead(token: string, conversationId: string) {
    return request<void>(
      `/v1/chat/conversations/${encodeURIComponent(conversationId)}/read`,
      token,
      'PUT',
      {},
    )
  },
}
