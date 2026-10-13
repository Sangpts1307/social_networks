import { flushPromises, mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import LearningView from '../src/views/LearningView.vue'
import { learningApi } from '../src/api/learning'

vi.mock('../src/api/learning', () => ({
  learningApi: {
    topics: vi.fn(),
    items: vi.fn(),
  },
}))

const mockedLearningApi = vi.mocked(learningApi)

describe('LearningView', () => {
  it('renders published Knowledge Items and their graph provenance', async () => {
    mockedLearningApi.topics.mockResolvedValue({ data: [{
      id: '70000000-0000-4000-8000-000000000001', code: 'engineering', name: 'Kỹ thuật', description: 'Tri thức kỹ thuật nội bộ', categories: [],
    }] })
    mockedLearningApi.items.mockResolvedValue({ data: [{
      id: '70000000-0000-4000-8000-000000000005', topic_id: '70000000-0000-4000-8000-000000000001', category_id: null,
      title: 'Khởi đầu với Knowledge Graph', summary: 'Tài liệu demo.', published_at: '2026-09-19T00:00:00Z',
      current_version_id: '70000000-0000-4000-8000-000000000006', topic_name: 'Kỹ thuật', category_name: null,
    }] })

    const wrapper = mount(LearningView, { props: { token: 'a'.repeat(64) } })
    await flushPromises()

    expect(mockedLearningApi.topics).toHaveBeenCalledWith('a'.repeat(64))
    expect(mockedLearningApi.items).toHaveBeenCalledWith('a'.repeat(64), undefined)
    expect(wrapper.text()).toContain('Khởi đầu với Knowledge Graph')
    expect(wrapper.text()).toContain('Source ID')
    expect(wrapper.text()).toContain('AI chưa tự ghi dữ liệu vào graph')
  })
})
