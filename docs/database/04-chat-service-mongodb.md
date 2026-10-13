# Chat Service — `chat_db` MongoDB

## Trách nhiệm và chọn mô hình

Chat Service sở hữu conversation, member, message, attachment metadata và read state. MongoDB phù hợp stream message append-heavy. Message binary ở MinIO/S3; realtime/Pusher/WebSocket chỉ vận chuyển update sau persistence, không là source of truth.

Identity/Social user relationship không được join trực tiếp. Nếu “chỉ nhắn tin với bạn bè” vẫn là rule, Chat nhận một read model/event hoặc gọi Social API tại thời điểm tạo conversation; không copy bảng `user_follows`.

## Collections

### `conversations`

```json
{
  "_id": "UUIDv7 string",
  "organizationId": "UUID",
  "type": "direct | group | project",
  "directKey": "uuid-smaller:uuid-larger",
  "title": "string or null",
  "avatarObjectKey": "string or null",
  "createdByUserId": "UUID",
  "status": "active | archived | closed",
  "lastMessage": {
    "messageId": "UUID",
    "senderUserId": "UUID",
    "preview": "safe short plaintext",
    "sentAt": "UTC datetime",
    "messageType": "text | file | system"
  },
  "memberCount": 2,
  "createdAt": "UTC datetime",
  "updatedAt": "UTC datetime",
  "archivedAt": null
}
```

Indexes:

| Index | Reason |
| --- | --- |
| `unique(organizationId, directKey)` with partial filter `type=direct` | Prevent duplicate direct conversation. |
| `(organizationId, status, updatedAt desc)` | Admin/maintenance. |
| `(lastMessage.sentAt desc)` | Optional recent activity support. |

`directKey` is canonical sorted pair of user UUIDs. It is null for group/project conversations.

### `conversation_members`

Separate collection prevents a large array/hot conversation document and gives efficient per-user inbox query.

```json
{
  "_id": "UUIDv7 string",
  "organizationId": "UUID",
  "conversationId": "UUID",
  "userId": "UUID",
  "memberRole": "owner | admin | member",
  "membershipStatus": "active | left | removed | muted",
  "joinedAt": "UTC datetime",
  "leftAt": null,
  "lastReadMessageId": "UUID or null",
  "lastReadAt": "UTC datetime or null",
  "lastDeliveredMessageId": "UUID or null",
  "unreadCount": 0,
  "isPinned": false,
  "mutedUntil": null,
  "lastSeenAt": "UTC datetime or null",
  "createdAt": "UTC datetime",
  "updatedAt": "UTC datetime"
}
```

Indexes: `unique(conversationId,userId)`, `(organizationId,userId,membershipStatus,lastSeenAt desc)`, `(userId,unreadCount desc)`, `(conversationId,membershipStatus)`.

`unreadCount` is a denormalized inbox projection. Its correctness is repaired from `lastReadMessageId`/messages if worker retry fails. Do not create one receipt document per message/user unless product later needs delivery receipts at that granularity.

### `messages`

```json
{
  "_id": "UUIDv7 string",
  "organizationId": "UUID",
  "conversationId": "UUID",
  "senderUserId": "UUID | null",
  "clientMessageId": "UUID",
  "messageType": "text | file | system | event",
  "bodyMarkdown": "string | null",
  "bodyPlaintext": "string | null",
  "replyToMessageId": "UUID | null",
  "attachments": [
    {
      "id": "UUID",
      "objectKey": "private object key",
      "filename": "string",
      "mimeType": "string",
      "sizeBytes": 0,
      "sha256": "hex",
      "scanStatus": "pending | clean | blocked | failed"
    }
  ],
  "mentions": ["UUID"],
  "sentAt": "UTC datetime",
  "editedAt": null,
  "deletedAt": null,
  "deleteReason": null,
  "metadata": { "schemaVersion": 1 }
}
```

Indexes:

| Index | Reason |
| --- | --- |
| `unique(conversationId, clientMessageId)` | Idempotent send retry from client. |
| `(conversationId, sentAt desc, _id desc)` | Cursor-paginated history; never “load all history.” |
| `(organizationId, senderUserId, sentAt desc)` | Audit/user activity. |
| text index on `bodyPlaintext` | Only if private message search is approved; index must remain organization/member filtered. |

Rules: message persistence occurs before realtime event; raw `bodyMarkdown` is sanitized; attachment is not visible until scan clean; deleted message keeps tombstone/audit instead of erasing sequence.

### `chat_read_models`

Optional, minimal Identity projection for inbox rendering if Gateway cannot aggregate user details cheaply.

| Field | Type | Purpose |
| --- | --- | --- |
| `_id` | `UUID userId` | Projection key. |
| `organizationId` | UUID | Scope. |
| `displayName`, `avatarObjectKey`, `jobTitle`, `orgUnitName` | string | UI-only display data. |
| `sourceVersion`, `syncedAt` | datetime/string | Event consistency. |

Never store password, email, birth date, address or roles here. Source: Identity event/API.

### Reliability collections

| Collection | Essential fields | Purpose |
| --- | --- | --- |
| `outbox_events` | `_id`, `aggregateType`, `aggregateId`, `eventType`, `version`, `payload`, `occurredAt`, `publishedAt`, `attempts`, `lastError`, `correlationId` | Transactional/outbox-equivalent event publisher. |
| `processed_events` | `_id`=`eventId`, `eventType`, `producer`, `processedAt`, `resultHash`, expiry/TTL policy | Idempotent consume. |
| `message_delivery_attempts` | `id`, `messageId`, `recipientUserId`, `transport`, `status`, `attemptedAt`, `errorCode` | Optional operational trace only; Notification owns durable push/email. |

## Chat invariants

1. Member authorization is checked against active `conversation_members` for every read/send/download.
2. A direct conversation has exactly two active members and one canonical `directKey`.
3. Client retry supplies a stable `clientMessageId`; service never duplicates a message.
4. Message history uses cursor pagination by `(sentAt,_id)`; no unbounded history fetch.
5. Read state is per member/conversation and monotonic; do not decrease `lastReadMessageId`.
6. Presence uses Redis with TTL; it is not saved permanently as user employment/availability state.
7. Group chat, calls, reactions, edit/delete duration and file limits require product approval before implementation.

## Evidence

- `design/exports/html-tailwind/05-messenger.html`
- `Cấu trúc cuối cùng.md`, sections 4 and 8
- `services/chat-service/README.md`
