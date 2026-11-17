import { flushPromises, mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import BaseMessageView from '../src/views/modules/BaseMessageView.vue'
import type { AuthUser } from '../src/api/auth'

const user: AuthUser = {
  id: '30000000-0000-4000-8000-000000000005',
  organization_id: '10000000-0000-4000-8000-000000000001',
  email: 'member@gmail.com',
  display_name: 'Thành viên',
  must_change_password: false,
  permissions: ['chat.groups.create'],
}

describe('BaseMessageView', () => {
  it('renders with 2 tabs: Tất cả and Nhóm chat, without Trực tiếp or top plus icon', async () => {
    const wrapper = mount(BaseMessageView, {
      props: {
        user,
      },
    })
    await flushPromises()

    const text = wrapper.text()
    expect(text).toContain('Hộp thoại Message')
    expect(text).toContain('Tất cả')
    expect(text).toContain('Nhóm chat')
    expect(text).not.toContain('Trực tiếp')

    // Top plus / post_add icon should not be in the header
    expect(wrapper.find('button[title="Nhắn tin mới"]').exists()).toBe(false)
  })

  it('renders seeded conversations for personnel and leaders with attachments', async () => {
    const wrapper = mount(BaseMessageView, {
      props: {
        user,
      },
    })
    await flushPromises()

    const text = wrapper.text()
    expect(text).toContain('Võ Thành Luân')
    expect(text).toContain('Phạm Phát')
    expect(text).toContain('Lê Thị Mai Phương')
    expect(text).toContain('Nguyễn Lê Thanh Trường')
    expect(text).toContain('Nguyễn Minh Trang')
    expect(text).toContain('Ban Kỹ thuật & Kiến trúc Core')
    expect(text).toContain('Dự án AI & Knowledge Graph')
  })

  it('filters group chats when Nhóm chat tab is active', async () => {
    const wrapper = mount(BaseMessageView, {
      props: {
        user,
      },
    })
    await flushPromises()

    const tabButtons = wrapper.findAll('button')
    const groupTabBtn = tabButtons.find(b => b.text() === 'Nhóm chat')
    expect(groupTabBtn).toBeDefined()

    await groupTabBtn!.trigger('click')
    await flushPromises()

    const sidebarText = wrapper.find('section.w-80').text()
    expect(sidebarText).toContain('Ban Kỹ thuật & Kiến trúc Core')
    expect(sidebarText).toContain('Dự án AI & Knowledge Graph')
    // Direct chat items should not appear in group tab list
    expect(sidebarText).not.toContain('Lê Thị Mai Phương')
  })

  it('clears unread badge on click and applies active effect matching Image 3', async () => {
    const wrapper = mount(BaseMessageView, {
      props: {
        user,
      },
    })
    await flushPromises()

    // Find conversation item
    const convItems = wrapper.findAll('.cursor-pointer.transition.border-l-4')
    expect(convItems.length).toBeGreaterThan(0)

    // The first item should be active and have border-l-blue-600 and bg-blue-50/70
    const first = convItems[0]!
    expect(first.classes()).toContain('border-l-blue-600')
    expect(first.classes()).toContain('bg-blue-50/70')

    // Click on another conversation
    const fourth = convItems[3]!
    await fourth.trigger('click')
    await flushPromises()

    expect(fourth.classes()).toContain('border-l-blue-600')
    expect(fourth.classes()).toContain('bg-blue-50/70')
    // Badge on the clicked item should be gone
    expect(fourth.find('.bg-blue-600.text-white.rounded-full').exists()).toBe(false)
  })

  it('displays file attachment button and sends message with files', async () => {
    const wrapper = mount(BaseMessageView, {
      props: {
        user,
      },
    })
    await flushPromises()

    // File attachment button exists
    const attachBtn = wrapper.find('button[title="Đính kèm tệp (Ảnh, PDF, tài liệu...)"]')
    expect(attachBtn.exists()).toBe(true)

    // Send a message
    const input = wrapper.find('input[placeholder="Nhập tin nhắn..."]')
    await input.setValue('Tin nhắn thử nghiệm có tệp')
    await input.trigger('keyup.enter')
    await flushPromises()

    expect(wrapper.text()).toContain('Tin nhắn thử nghiệm có tệp')
  })
})
