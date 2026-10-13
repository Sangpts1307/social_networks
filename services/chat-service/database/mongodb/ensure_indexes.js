const chat = db.getSiblingDB('chat_db');

chat.conversations.createIndex(
  { organizationId: 1, directKey: 1 },
  { unique: true, partialFilterExpression: { type: 'direct' }, name: 'direct_conversation_unique' },
);
chat.conversations.createIndex({ organizationId: 1, status: 1, updatedAt: -1 });
chat.conversations.createIndex({ 'lastMessage.sentAt': -1 });

chat.conversation_members.createIndex({ conversationId: 1, userId: 1 }, { unique: true });
chat.conversation_members.createIndex({ organizationId: 1, userId: 1, membershipStatus: 1, lastSeenAt: -1 });
chat.conversation_members.createIndex({ userId: 1, unreadCount: -1 });
chat.conversation_members.createIndex({ conversationId: 1, membershipStatus: 1 });

chat.messages.createIndex({ conversationId: 1, clientMessageId: 1 }, { unique: true });
chat.messages.createIndex({ conversationId: 1, sentAt: -1, _id: -1 });
chat.messages.createIndex({ organizationId: 1, senderUserId: 1, sentAt: -1 });

chat.processed_events.createIndex({ _id: 1 }, { unique: true });
chat.outbox_events.createIndex({ publishedAt: 1, occurredAt: 1 });
