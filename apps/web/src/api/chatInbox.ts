import { ref } from 'vue'
import { chatApi } from './chat'

export const unreadChatCount = ref(0)

export async function refreshUnreadChatCount(token: string) {
  const response = await chatApi.conversations(token)
  unreadChatCount.value = response.data.reduce((total, conversation) => total + (conversation.unread_count ?? 0), 0)
  return response.data
}
