import { ApiError } from './auth'

export interface KnowledgeCategory {
  id: string
  topic_id: string
  parent_id: string | null
  code: string
  name: string
  description: string | null
}

export interface KnowledgeTopic {
  id: string
  code: string
  name: string
  description: string | null
  categories: KnowledgeCategory[]
}

export interface KnowledgeItem {
  id: string
  topic_id: string
  category_id: string | null
  title: string
  summary: string | null
  published_at: string
  current_version_id: string | null
  published_version_id: string | null
  topic_name: string
  category_name: string | null
}

export interface LearningCourse {
  id: string
  topic_id: string | null
  title: string
  description_markdown: string | null
  published_at: string
  topic_name: string | null
}

export interface QuestionSummary {
  id: string
  topic_id: string
  category_id: string
  author_user_id: string
  title: string
  body_plaintext: string | null
  question_status: 'open' | 'closed' | 'hidden'
  answer_count: number
  score: number
  created_at: string
}

export interface Answer {
  id: string
  question_id: string
  author_user_id: string
  body_markdown: string
  body_plaintext: string
  score: number
  created_at: string
  edited_at: string | null
}

export interface QuestionDetail extends QuestionSummary {
  best_answer_id: string | null
  answers: Answer[]
}

const API_BASE = (import.meta.env.VITE_API_BASE_URL ?? '/api').replace(/\/$/, '')

async function request<T>(path: string, token: string, method = 'GET', payload?: unknown): Promise<T> {
  let response: Response
  try {
    response = await fetch(`${API_BASE}/v1/learning${path}`, {
      method,
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
        ...(payload === undefined ? {} : { 'Content-Type': 'application/json' }),
      },
      ...(payload === undefined ? {} : { body: JSON.stringify(payload) }),
    })
  } catch {
    throw new ApiError('Không thể kết nối tới máy chủ. Hãy kiểm tra Gateway và Learning Service.', 0)
  }
  const responseBody = await response.json().catch(() => ({})) as { message?: string }
  if (! response.ok) throw new ApiError(responseBody.message ?? 'Không thể tải dữ liệu tri thức.', response.status)
  return responseBody as T
}

function get<T>(path: string, token: string): Promise<T> {
  return request<T>(path, token)
}

export const learningApi = {
  topics(token: string) {
    return get<{ data: KnowledgeTopic[] }>('/topics', token)
  },
  items(token: string, topicId?: string) {
    const query = topicId ? `?topic_id=${encodeURIComponent(topicId)}` : ''
    return get<{ data: KnowledgeItem[] }>(`/knowledge-items${query}`, token)
  },
  courses(token: string) {
    return get<{ data: LearningCourse[] }>('/courses', token)
  },
  questions(token: string, filters: { topicId?: string; categoryId?: string; query?: string; cursor?: string } = {}) {
    const params = new URLSearchParams()
    if (filters.topicId) params.set('topic_id', filters.topicId)
    if (filters.categoryId) params.set('category_id', filters.categoryId)
    if (filters.query) params.set('q', filters.query)
    if (filters.cursor) params.set('cursor', filters.cursor)
    const query = params.size ? `?${params.toString()}` : ''
    return get<{ data: QuestionSummary[]; meta: { next_cursor: string | null } }>(`/questions${query}`, token)
  },
  question(token: string, questionId: string) {
    return get<{ data: QuestionDetail }>(`/questions/${encodeURIComponent(questionId)}`, token)
  },
  documentDownload(token: string, documentId: string) {
    return get<{ data: { url: string; expires_at: string } }>(`/knowledge-items/${encodeURIComponent(documentId)}/download`, token)
  },
  createQuestion(token: string, payload: { topic_id: string; category_id: string; title: string; body_markdown: string }) {
    return request<{ data: QuestionSummary }>('/questions', token, 'POST', payload)
  },
  createAnswer(token: string, questionId: string, body_markdown: string) {
    return request<{ data: Answer }>(`/questions/${encodeURIComponent(questionId)}/answers`, token, 'POST', { body_markdown })
  },
  voteQuestion(token: string, questionId: string, value: -1 | 0 | 1) {
    return request<{ data: QuestionSummary }>(`/questions/${encodeURIComponent(questionId)}/votes`, token, 'PUT', { value })
  },
  voteAnswer(token: string, answerId: string, value: -1 | 0 | 1) {
    return request<{ data: Answer }>(`/answers/${encodeURIComponent(answerId)}/votes`, token, 'PUT', { value })
  },
}
