import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import BaseInsideView from '../src/views/modules/BaseInsideView.vue'
import { profileApi } from '../src/api/profile'
import { socialApi } from '../src/api/social'
import type { AuthUser } from '../src/api/auth'

vi.mock('../src/api/profile', () => ({
  profileApi: {
    me: vi.fn(),
    update: vi.fn(),
  },
}))

vi.mock('../src/api/social', () => ({
  socialApi: {
    channels: vi.fn(),
    channel: vi.fn(),
    channelMembers: vi.fn(),
    joinChannel: vi.fn(),
    leaveChannel: vi.fn(),
    posts: vi.fn(),
    createPost: vi.fn(),
    uploadAttachment: vi.fn(),
    attachments: vi.fn(),
    attachmentUrl: vi.fn(),
    updatePost: vi.fn(),
    deletePost: vi.fn(),
    react: vi.fn(),
    save: vi.fn(),
    comments: vi.fn(),
    createComment: vi.fn(),
    deleteComment: vi.fn(),
    followStats: vi.fn(),
    birthdays: vi.fn().mockResolvedValue({ data: [] }),
    directoryUsers: vi.fn(),
  },
}))

const mockedSocialApi = vi.mocked(socialApi)
const mockedProfileApi = vi.mocked(profileApi)

const token = 'a'.repeat(64)
const user: AuthUser = {
  id: '30000000-0000-4000-8000-000000000001',
  organization_id: '10000000-0000-4000-8000-000000000001',
  email: 'member@example.com',
  display_name: 'Nguyen Van A',
  must_change_password: false,
  permissions: ['social.announcement.publish'],
}

const channelTech = {
  id: '60000000-0000-4000-8000-000000000001',
  code: 'tech-arch',
  name: 'Tech & Architecture',
  description: 'Kênh kỹ thuật và kiến trúc hệ thống',
  visibility: 'organization' as const,
  membership_policy: 'open' as const,
  owner_user_id: '30000000-0000-4000-8000-000000000002',
}

const initialPost = {
  id: '80000000-0000-4000-8000-000000000001',
  channel_id: channelTech.id,
  author_user_id: user.id,
  post_type: 'update' as const,
  title: null,
  body_plaintext: 'Bài viết thực tế từ Gateway Social API',
  published_at: '2026-11-09T10:00:00Z',
  reaction_count: 3,
  comment_count: 1,
  my_reaction: null,
  is_saved: false,
}

describe('BaseInsideView real API integration', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    window.history.replaceState({}, '', '/?app=inside')
    mockedProfileApi.me.mockResolvedValue({
      id: user.id,
      display_name: user.display_name,
      email: user.email,
      job_title: 'Kỹ sư phần mềm',
      bio: 'Giới thiệu từ Identity API',
      phone: null,
      location: 'Hà Nội',
      skills: ['Vue', 'Laravel'],
    })
    mockedSocialApi.followStats.mockResolvedValue({
      data: { following_count: 5, followers_count: 12 },
    })
    mockedSocialApi.directoryUsers.mockResolvedValue({
      data: [
        {
          id: user.id,
          display_name: user.display_name,
          job_title: 'Kỹ sư phần mềm',
          bio: null,
          skills: [],
        },
        {
          id: '30000000-0000-4000-8000-000000000002',
          display_name: 'Tran Thi B',
          job_title: 'Tech Lead',
          bio: null,
          skills: [],
        },
      ],
      meta: { next_cursor: null },
    })
    mockedSocialApi.channels.mockResolvedValue({
      data: [channelTech],
      meta: { next_cursor: null },
    })
    mockedSocialApi.posts.mockResolvedValue({
      data: [initialPost],
      meta: { next_cursor: null },
    })
    mockedSocialApi.attachments.mockResolvedValue({ data: [] })
  })

  it('renders an existing post with an embedded image without exposing its data URL', async () => {
    const image = 'data:image/jpeg;base64,/9j/4AAQSkZJRg=='
    const body = `Demo đăng bài cá nhân\n\n![Ảnh đính kèm](${image})`
    mockedSocialApi.posts.mockResolvedValue({
      data: [{ ...initialPost, body_markdown: body, body_plaintext: body }],
      meta: { next_cursor: null },
    })

    const wrapper = mount(BaseInsideView, { props: { user, token, page: 'feed' } })
    await flushPromises()

    expect(wrapper.text()).toContain('Demo đăng bài cá nhân')
    expect(wrapper.text()).not.toContain('Ảnh đính kèm')
    expect(wrapper.text()).not.toContain('base64')
    expect(wrapper.find(`img[src="${image}"]`).exists()).toBe(true)
  })

  it('shows an image and lets the owner delete a post from the personal profile', async () => {
    const image = 'data:image/jpeg;base64,/9j/4AAQSkZJRg=='
    const body = `Demo đăng bài cá nhân\n\n![Ảnh đính kèm](${image})`
    mockedSocialApi.posts.mockResolvedValue({
      data: [{ ...initialPost, body_markdown: body, body_plaintext: body }],
      meta: { next_cursor: null },
    })
    mockedSocialApi.deletePost.mockResolvedValue({ data: { id: initialPost.id } })

    const wrapper = mount(BaseInsideView, { props: { user, token, page: 'profile' } })
    await flushPromises()

    expect(wrapper.text()).toContain('Demo đăng bài cá nhân')
    expect(wrapper.text()).not.toContain('Bài viết của Nguyen Van A')
    expect(wrapper.text()).not.toContain('base64')
    expect(wrapper.find(`img[src="${image}"]`).exists()).toBe(true)

    await wrapper.get('button[title="Xóa bài viết"]').trigger('click')
    await wrapper.get('[role="dialog"] button.bg-rose-600').trigger('click')
    await flushPromises()

    expect(mockedSocialApi.deletePost).toHaveBeenCalledWith(token, initialPost.id)
    expect(wrapper.text()).not.toContain('Demo đăng bài cá nhân')
  })

  it('loads real feed posts, channels, follow stats, and handles create, edit, delete, like, save, and comments via socialApi', async () => {
    const wrapper = mount(BaseInsideView, {
      props: { user, token, page: 'feed' },
    })
    await flushPromises()

    expect(mockedSocialApi.channels).toHaveBeenCalledWith(token)
    expect(mockedSocialApi.posts).toHaveBeenCalledWith(token)
    expect(wrapper.text()).toContain('Bài viết thực tế từ Gateway Social API')
    expect(wrapper.text()).toContain('12')

    // Like post via API
    mockedSocialApi.react.mockResolvedValue({
      data: { post_id: initialPost.id, reaction_type: 'like' },
    })
    await wrapper.get(`button[aria-label="Thích bài viết của ${user.display_name}"]`).trigger('click')
    await flushPromises()
    expect(mockedSocialApi.react).toHaveBeenCalledWith(token, initialPost.id, 'like')

    // Save post via API
    mockedSocialApi.save.mockResolvedValue({
      data: { post_id: initialPost.id, is_saved: true },
    })
    await wrapper.get('button[title="Lưu bài viết"]').trigger('click')
    await flushPromises()
    expect(mockedSocialApi.save).toHaveBeenCalledWith(token, initialPost.id, true)

    // Edit post via API
    mockedSocialApi.updatePost.mockResolvedValue({
      data: {
        ...initialPost,
        body_plaintext: 'Nội dung đã cập nhật qua API',
      },
    })
    await wrapper.get(`button[aria-label="Sửa bài viết của ${user.display_name}"]`).trigger('click')
    await wrapper.find('article textarea').setValue('Nội dung đã cập nhật qua API')
    await wrapper.findAll('article button').find(btn => btn.text().includes('Lưu thay đổi'))!.trigger('click')
    await flushPromises()
    expect(mockedSocialApi.updatePost).toHaveBeenCalledWith(token, initialPost.id, {
      body_markdown: 'Nội dung đã cập nhật qua API',
    })
    expect(wrapper.text()).toContain('Nội dung đã cập nhật qua API')

    // Load comments and add a comment via API
    mockedSocialApi.comments.mockResolvedValue({
      data: [
        {
          id: '90000000-0000-4000-8000-000000000001',
          post_id: initialPost.id,
          parent_comment_id: null,
          author_user_id: '30000000-0000-4000-8000-000000000002',
          body_plaintext: 'Bình luận gốc từ đồng nghiệp',
          created_at: '2026-11-09T10:05:00Z',
        },
      ],
      meta: { next_cursor: null },
    })
    mockedSocialApi.createComment.mockResolvedValue({
      data: {
        id: '90000000-0000-4000-8000-000000000002',
        post_id: initialPost.id,
        parent_comment_id: null,
        author_user_id: user.id,
        body_plaintext: 'Bình luận mới qua API',
        created_at: '2026-11-09T10:10:00Z',
      },
    })

    await wrapper.get(`button[aria-label="Xem bình luận bài viết của ${user.display_name}"]`).trigger('click')
    await flushPromises()
    expect(mockedSocialApi.comments).toHaveBeenCalledWith(token, initialPost.id)
    expect(wrapper.text()).toContain('Bình luận gốc từ đồng nghiệp')

    const commentInput = wrapper.get(`input[aria-label="Viết bình luận cho bài viết của ${user.display_name}"]`)
    await commentInput.setValue('Bình luận mới qua API')
    await commentInput.element.closest('form')!.dispatchEvent(new Event('submit'))
    await flushPromises()
    expect(mockedSocialApi.createComment).toHaveBeenCalledWith(token, initialPost.id, 'Bình luận mới qua API')
    expect(wrapper.text()).toContain('Bình luận mới qua API')

    // Close comment modal
    await wrapper.get('button[aria-label="Đóng bình luận"]').trigger('click')

    // Create a new post via API
    mockedSocialApi.createPost.mockResolvedValue({
      data: {
        id: '80000000-0000-4000-8000-000000000009',
        channel_id: channelTech.id,
        author_user_id: user.id,
        post_type: 'update',
        title: null,
        body_plaintext: 'Bài đăng mới từ trình soạn thảo',
        published_at: '2026-11-09T11:00:00Z',
        reaction_count: 0,
        comment_count: 0,
        my_reaction: null,
        is_saved: false,
      },
    })
    await wrapper.get('button[title="Tạo nhanh"]').trigger('click')
    await wrapper.find('textarea[placeholder*="Nhập nội dung"]').setValue('Bài đăng mới từ trình soạn thảo')
    await wrapper.findAll('button').find(btn => btn.text() === 'Đăng bài')!.trigger('click')
    await flushPromises()
    expect(mockedSocialApi.createPost).toHaveBeenCalledWith(token, {
      channel_id: channelTech.id,
      body_markdown: 'Bài đăng mới từ trình soạn thảo',
      title: null,
      post_type: 'update',
      publication_status: 'published',
    })
    expect(wrapper.text()).toContain('Bài đăng mới từ trình soạn thảo')
  })

  it('keeps the composer and entered content when the pin expiry has passed', async () => {
    const pinUser = { ...user, permissions: [...user.permissions, 'social.posts.pin'] }
    const wrapper = mount(BaseInsideView, { props: { user: pinUser, token, page: 'feed' } })
    await flushPromises()
    await wrapper.get('button[title="Tạo nhanh"]').trigger('click')
    const body = wrapper.get('textarea[placeholder*="Nhập nội dung"]')
    await body.setValue('Thông báo cần giữ lại')
    await wrapper.get('input[type="checkbox"]').setValue(true)
    await wrapper.get('input[type="datetime-local"]').setValue('2020-01-01T09:00')
    await wrapper.findAll('button').find(button => button.text() === 'Đăng bài')!.trigger('click')

    expect(mockedSocialApi.createPost).not.toHaveBeenCalled()
    expect(wrapper.get('textarea[placeholder*="Nhập nội dung"]').element.value).toBe('Thông báo cần giữ lại')
    expect(wrapper.text()).toContain('Hạn ghim phải là thời điểm trong tương lai')
  })

  it('shows an attached PDF on the post and opens its preview', async () => {
    const attachment = { id: 'attachment-1', attachment_type: 'file' as const, original_filename: 'CV mẫu.pdf', mime_type: 'application/pdf', size_bytes: 4096 }
    mockedSocialApi.attachments.mockResolvedValue({ data: [attachment] })
    mockedSocialApi.attachmentUrl.mockResolvedValue({ data: { url: 'https://files.example.test/cv.pdf' } })
    const wrapper = mount(BaseInsideView, { props: { user, token, page: 'feed' } })
    await flushPromises()

    expect(wrapper.text()).toContain('CV mẫu.pdf')
    await wrapper.findAll('button').find(button => button.text().includes('CV mẫu.pdf'))!.trigger('click')
    await flushPromises()
    expect(mockedSocialApi.attachmentUrl).toHaveBeenCalledWith(token, initialPost.id, attachment.id)
    expect(wrapper.get('iframe[title="CV mẫu.pdf"]').attributes('src')).toBe('https://files.example.test/cv.pdf')
  })

  it('loads channel members and allows leaving and joining a channel via socialApi', async () => {
    mockedSocialApi.channelMembers.mockResolvedValue({
      data: [
        {
          id: 'm-1',
          user_id: user.id,
          member_role: 'member',
          joined_at: '2026-11-01T00:00:00Z',
        },
      ],
      meta: { next_cursor: null },
    })
    mockedSocialApi.leaveChannel.mockResolvedValue(undefined)
    mockedSocialApi.joinChannel.mockResolvedValue({
      data: { channel_id: channelTech.id, is_member: true },
    })

    const wrapper = mount(BaseInsideView, {
      props: { user, token, page: 'feed' },
    })
    await flushPromises()

    const channelBtn = wrapper.findAll('aside button').find(btn => btn.text().includes('Tech & Architecture'))
    expect(channelBtn).toBeDefined()
    await channelBtn!.trigger('click')
    await flushPromises()

    expect(mockedSocialApi.channelMembers).toHaveBeenCalledWith(token, channelTech.id)
    const leaveBtn = wrapper.findAll('button').find(btn => btn.text().includes('Đã tham gia · Rời kênh'))
    expect(leaveBtn).toBeDefined()

    // Leave channel
    mockedSocialApi.channelMembers.mockResolvedValueOnce({
      data: [],
      meta: { next_cursor: null },
    })
    await leaveBtn!.trigger('click')
    await flushPromises()
    expect(mockedSocialApi.leaveChannel).toHaveBeenCalledWith(token, channelTech.id)

    // Join channel back
    const joinBtn = wrapper.findAll('button').find(btn => btn.text().includes('Tham gia kênh'))
    expect(joinBtn).toBeDefined()
    mockedSocialApi.channelMembers.mockResolvedValueOnce({
      data: [
        {
          id: 'm-1',
          user_id: user.id,
          member_role: 'member',
          joined_at: '2026-11-01T00:00:00Z',
        },
      ],
      meta: { next_cursor: null },
    })
    await joinBtn!.trigger('click')
    await flushPromises()
    expect(mockedSocialApi.joinChannel).toHaveBeenCalledWith(token, channelTech.id)
  })

  it('renders birthdays widget and handles channel list expansion and delete confirmation popup', async () => {
    mockedSocialApi.birthdays.mockResolvedValueOnce({ data: [{ id: 'user-1', name: 'Nguyễn Minh Anh', position: 'Kế toán', avatar: '/avatar.jpg', date: '05/11' }] })
    // Add extra channels to test expand
    mockedSocialApi.channels.mockResolvedValue({
      data: [
        channelTech,
        { ...channelTech, id: 'ch-2', name: 'Kinh doanh & Khách hàng' },
        { ...channelTech, id: 'ch-3', name: 'HR & Văn hóa' },
        { ...channelTech, id: 'ch-4', name: 'AI & Knowledge Graph' },
        { ...channelTech, id: 'ch-5', name: 'Thông báo chung' },
      ],
      meta: { next_cursor: null },
    })

    const wrapper = mount(BaseInsideView, {
      props: { user, token, page: 'feed' },
    })
    await flushPromises()

    // 1. Birthdays widget exists
    expect(wrapper.find('#birthdays-widget').exists()).toBe(true)
    expect(wrapper.find('#birthdays-widget').text()).toContain('Sinh nhật tháng')
    expect(wrapper.find('#birthdays-widget').text()).toContain('Nguyễn Minh Anh')
    expect(wrapper.find('#birthdays-widget').text()).toContain('Kế toán')
    expect(wrapper.get('#birthdays-widget img').attributes('src')).toBe('/avatar.jpg')

    // 2. Channels list defaults to 3 and expands with 'Xem thêm'
    const expandBtn = wrapper.findAll('aside button').find(btn => btn.text().includes('Xem thêm'))
    expect(expandBtn).toBeDefined()
    await expandBtn!.trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('AI & Knowledge Graph')
    expect(wrapper.findAll('aside button').some(btn => btn.text().includes('Thu gọn'))).toBe(true)

    // 3. Delete confirmation popup
    mockedSocialApi.deletePost.mockResolvedValue(undefined)
    const deleteBtn = wrapper.get(`button[aria-label="Xóa bài viết của ${user.display_name}"]`)
    await deleteBtn.trigger('click')
    await flushPromises()

    // Confirmation popup must appear
    expect(wrapper.text()).toContain('Xác nhận xóa bài viết')
    expect(wrapper.text()).toContain('Hành động này không thể hoàn tác')

    // Click confirm delete
    const confirmDeleteBtn = wrapper.findAll('button').find(btn => btn.text() === 'Xóa bài viết' && btn.classes().includes('bg-rose-600'))
    expect(confirmDeleteBtn).toBeDefined()
    await confirmDeleteBtn!.trigger('click')
    await flushPromises()
    expect(mockedSocialApi.deletePost).toHaveBeenCalledWith(token, initialPost.id)
  })
})
