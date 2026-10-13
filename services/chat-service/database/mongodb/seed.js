const chat = db.getSiblingDB('chat_db');
const organizationId = '10000000-0000-4000-8000-000000000001';
const expertUserId = '30000000-0000-4000-8000-000000000002';
const memberUserId = '30000000-0000-4000-8000-000000000003';
const conversationId = '80000000-0000-4000-8000-000000000001';
const now = new Date();

chat.conversations.updateOne(
  { _id: conversationId },
  { $set: { organizationId, type: 'direct', directKey: `${expertUserId}:${memberUserId}`, createdByUserId: expertUserId, status: 'active', memberCount: 2, updatedAt: now }, $setOnInsert: { createdAt: now } },
  { upsert: true },
);
chat.conversation_members.updateOne(
  { conversationId, userId: expertUserId },
  { $set: { organizationId, memberRole: 'owner', membershipStatus: 'active', joinedAt: now, unreadCount: 0, isPinned: false, updatedAt: now }, $setOnInsert: { createdAt: now } },
  { upsert: true },
);
chat.conversation_members.updateOne(
  { conversationId, userId: memberUserId },
  { $set: { organizationId, memberRole: 'member', membershipStatus: 'active', joinedAt: now, unreadCount: 0, isPinned: false, updatedAt: now }, $setOnInsert: { createdAt: now } },
  { upsert: true },
);
chat.messages.updateOne(
  { conversationId, clientMessageId: '80000000-0000-4000-8000-000000000002' },
  { $set: { _id: '80000000-0000-4000-8000-000000000003', organizationId, senderUserId: expertUserId, messageType: 'text', bodyMarkdown: 'Chào mừng đến Chat nội bộ.', bodyPlaintext: 'Chào mừng đến Chat nội bộ.', attachments: [], mentions: [], sentAt: now, metadata: { schemaVersion: 1 } }, $setOnInsert: { createdAt: now } },
  { upsert: true },
);
