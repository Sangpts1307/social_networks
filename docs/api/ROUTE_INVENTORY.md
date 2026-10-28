# Kiểm kê route thực tế

Sinh bằng `python3 scripts/inventory-api.py`. HEAD được tính cùng GET. Số route không đồng nghĩa đã nghiệm thu nghiệp vụ.

| Thành phần | Số REST operation |
|---|---:|
| identity | 31 |
| social | 39 |
| learning | 58 |
| chat | 14 |
| notification | 15 |
| Gateway | 150 |

## Gateway

| Method | Path | Source |
|---|---|---|
| GET | `/api/v1/admin/dashboard/identity` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| GET | `/api/v1/admin/users` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| GET | `/api/v1/admin/users/{userId}` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| PATCH | `/api/v1/admin/users/{userId}/status` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| POST | `/api/v1/admin/users/{userId}/unlock` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| POST | `/api/v1/admin/users/{userId}/force-password-change` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| GET | `/api/v1/admin/org-units` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| POST | `/api/v1/admin/org-units` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| PATCH | `/api/v1/admin/org-units/{orgUnitId}` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| PATCH | `/api/v1/admin/org-units/{orgUnitId}/manager` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| GET | `/api/v1/admin/roles` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| GET | `/api/v1/admin/permissions` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| PATCH | `/api/v1/admin/roles/{roleId}/permissions` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| POST | `/api/v1/admin/users/{userId}/role-assignments` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| DELETE | `/api/v1/admin/users/{userId}/role-assignments/{assignmentId}` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| GET | `/api/v1/admin/audit-logs` | `gateway/api-gateway/src/proxy/admin-identity.controller.ts` |
| GET | `/api/v1/admin/learning/taxonomy` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| POST | `/api/v1/admin/learning/topics` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| PATCH | `/api/v1/admin/learning/topics/{topicId}` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| POST | `/api/v1/admin/learning/categories` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| PATCH | `/api/v1/admin/learning/categories/{categoryId}` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| GET | `/api/v1/admin/learning/questions` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| POST | `/api/v1/admin/learning/questions/{questionId}/moderation` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| GET | `/api/v1/admin/learning/documents` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| POST | `/api/v1/admin/learning/documents` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| PATCH | `/api/v1/admin/learning/documents/{documentId}` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| POST | `/api/v1/admin/learning/documents/{documentId}/files` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| POST | `/api/v1/admin/learning/documents/{documentId}/review` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| GET | `/api/v1/admin/learning/documents/{documentId}/access-rules` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| PUT | `/api/v1/admin/learning/documents/{documentId}/access-rules` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| POST | `/api/v1/admin/learning/documents/{documentId}/lifecycle` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| GET | `/api/v1/admin/learning/courses` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| POST | `/api/v1/admin/learning/courses` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| POST | `/api/v1/admin/learning/courses/{courseId}/lifecycle` | `gateway/api-gateway/src/proxy/admin-learning.controller.ts` |
| GET | `/api/v1/admin/learning/reward-rules` | `gateway/api-gateway/src/proxy/admin-reward.controller.ts` |
| POST | `/api/v1/admin/learning/reward-rules` | `gateway/api-gateway/src/proxy/admin-reward.controller.ts` |
| PUT | `/api/v1/admin/learning/reward-rules/{ruleId}` | `gateway/api-gateway/src/proxy/admin-reward.controller.ts` |
| GET | `/api/v1/admin/learning/reward-settlements` | `gateway/api-gateway/src/proxy/admin-reward.controller.ts` |
| POST | `/api/v1/admin/learning/reward-settlements` | `gateway/api-gateway/src/proxy/admin-reward.controller.ts` |
| GET | `/api/v1/admin/learning/reward-settlements/{settlementId}` | `gateway/api-gateway/src/proxy/admin-reward.controller.ts` |
| POST | `/api/v1/admin/learning/reward-settlements/{settlementId}/approve` | `gateway/api-gateway/src/proxy/admin-reward.controller.ts` |
| POST | `/api/v1/admin/learning/reward-settlements/{settlementId}/cancel` | `gateway/api-gateway/src/proxy/admin-reward.controller.ts` |
| GET | `/api/v1/ai/status` | `gateway/api-gateway/src/proxy/ai.controller.ts` |
| POST | `/api/v1/ai/suggestions` | `gateway/api-gateway/src/proxy/ai.controller.ts` |
| GET | `/api/v1/ai/search` | `gateway/api-gateway/src/proxy/ai.controller.ts` |
| GET | `/api/v1/chat/conversations` | `gateway/api-gateway/src/proxy/chat.controller.ts` |
| POST | `/api/v1/chat/conversations` | `gateway/api-gateway/src/proxy/chat.controller.ts` |
| GET | `/api/v1/chat/conversations/{conversationId}` | `gateway/api-gateway/src/proxy/chat.controller.ts` |
| GET | `/api/v1/chat/conversations/{conversationId}/messages` | `gateway/api-gateway/src/proxy/chat.controller.ts` |
| POST | `/api/v1/chat/conversations/{conversationId}/messages` | `gateway/api-gateway/src/proxy/chat.controller.ts` |
| PUT | `/api/v1/chat/conversations/{conversationId}/read` | `gateway/api-gateway/src/proxy/chat.controller.ts` |
| POST | `/api/v1/chat/conversations/{conversationId}/members` | `gateway/api-gateway/src/proxy/chat.controller.ts` |
| DELETE | `/api/v1/chat/conversations/{conversationId}/members/{userId}` | `gateway/api-gateway/src/proxy/chat.controller.ts` |
| POST | `/api/v1/chat/conversations/{conversationId}/attachments` | `gateway/api-gateway/src/proxy/chat.controller.ts` |
| GET | `/api/v1/chat/conversations/{conversationId}/attachments/{attachmentId}/download` | `gateway/api-gateway/src/proxy/chat.controller.ts` |
| PATCH | `/api/v1/chat/conversations/{conversationId}` | `gateway/api-gateway/src/proxy/chat.controller.ts` |
| PUT | `/api/v1/chat/conversations/{conversationId}/owner` | `gateway/api-gateway/src/proxy/chat.controller.ts` |
| GET | `/api/v1/me/profile` | `gateway/api-gateway/src/proxy/identity-extension.controller.ts` |
| PATCH | `/api/v1/me/profile` | `gateway/api-gateway/src/proxy/identity-extension.controller.ts` |
| GET | `/api/v1/directory/users` | `gateway/api-gateway/src/proxy/identity-extension.controller.ts` |
| GET | `/api/v1/directory/users/{userId}` | `gateway/api-gateway/src/proxy/identity-extension.controller.ts` |
| POST | `/api/v1/admin/users` | `gateway/api-gateway/src/proxy/identity-extension.controller.ts` |
| PATCH | `/api/v1/admin/users/{userId}` | `gateway/api-gateway/src/proxy/identity-extension.controller.ts` |
| POST | `/api/v1/admin/users/{userId}/reset-password` | `gateway/api-gateway/src/proxy/identity-extension.controller.ts` |
| POST | `/api/v1/auth/login` | `gateway/api-gateway/src/proxy/identity.controller.ts` |
| GET | `/api/v1/auth/session` | `gateway/api-gateway/src/proxy/identity.controller.ts` |
| GET | `/api/v1/me` | `gateway/api-gateway/src/proxy/identity.controller.ts` |
| POST | `/api/v1/auth/logout` | `gateway/api-gateway/src/proxy/identity.controller.ts` |
| POST | `/api/v1/auth/logout-all` | `gateway/api-gateway/src/proxy/identity.controller.ts` |
| POST | `/api/v1/auth/password` | `gateway/api-gateway/src/proxy/identity.controller.ts` |
| GET | `/api/v1/learning/rewards/wallet` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| GET | `/api/v1/learning/rewards/ledger` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| PATCH | `/api/v1/learning/questions/{questionId}` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| DELETE | `/api/v1/learning/questions/{questionId}` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| PUT | `/api/v1/learning/questions/{questionId}/follow` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| DELETE | `/api/v1/learning/questions/{questionId}/follow` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| PUT | `/api/v1/learning/questions/{questionId}/bookmark` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| DELETE | `/api/v1/learning/questions/{questionId}/bookmark` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| GET | `/api/v1/learning/questions/{questionId}/comments` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| POST | `/api/v1/learning/questions/{questionId}/comments` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| PATCH | `/api/v1/learning/qa-comments/{commentId}` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| DELETE | `/api/v1/learning/qa-comments/{commentId}` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| PUT | `/api/v1/learning/knowledge-items/{documentId}/votes` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| PUT | `/api/v1/learning/knowledge-items/{documentId}/follow` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| DELETE | `/api/v1/learning/knowledge-items/{documentId}/follow` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| GET | `/api/v1/learning/rewards/accruals` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| GET | `/api/v1/learning/rewards/leaderboard` | `gateway/api-gateway/src/proxy/learning-extension.controller.ts` |
| GET | `/api/v1/learning/topics` | `gateway/api-gateway/src/proxy/learning.controller.ts` |
| GET | `/api/v1/learning/knowledge-items` | `gateway/api-gateway/src/proxy/learning.controller.ts` |
| GET | `/api/v1/learning/knowledge-items/{documentId}` | `gateway/api-gateway/src/proxy/learning.controller.ts` |
| GET | `/api/v1/learning/knowledge-items/{documentId}/download` | `gateway/api-gateway/src/proxy/learning.controller.ts` |
| GET | `/api/v1/learning/courses` | `gateway/api-gateway/src/proxy/learning.controller.ts` |
| GET | `/api/v1/learning/questions` | `gateway/api-gateway/src/proxy/learning.controller.ts` |
| POST | `/api/v1/learning/questions` | `gateway/api-gateway/src/proxy/learning.controller.ts` |
| GET | `/api/v1/learning/questions/{questionId}` | `gateway/api-gateway/src/proxy/learning.controller.ts` |
| POST | `/api/v1/learning/questions/{questionId}/answers` | `gateway/api-gateway/src/proxy/learning.controller.ts` |
| PATCH | `/api/v1/learning/answers/{answerId}` | `gateway/api-gateway/src/proxy/learning.controller.ts` |
| DELETE | `/api/v1/learning/answers/{answerId}` | `gateway/api-gateway/src/proxy/learning.controller.ts` |
| PUT | `/api/v1/learning/questions/{questionId}/votes` | `gateway/api-gateway/src/proxy/learning.controller.ts` |
| PUT | `/api/v1/learning/answers/{answerId}/votes` | `gateway/api-gateway/src/proxy/learning.controller.ts` |
| GET | `/api/v1/notifications` | `gateway/api-gateway/src/proxy/notification.controller.ts` |
| GET | `/api/v1/notifications/unread-count` | `gateway/api-gateway/src/proxy/notification.controller.ts` |
| PUT | `/api/v1/notifications/read-all` | `gateway/api-gateway/src/proxy/notification.controller.ts` |
| PUT | `/api/v1/notifications/{notificationId}/read` | `gateway/api-gateway/src/proxy/notification.controller.ts` |
| GET | `/api/v1/notification-preferences` | `gateway/api-gateway/src/proxy/notification.controller.ts` |
| PUT | `/api/v1/notification-preferences/{type}` | `gateway/api-gateway/src/proxy/notification.controller.ts` |
| GET | `/api/v1/devices` | `gateway/api-gateway/src/proxy/notification.controller.ts` |
| POST | `/api/v1/devices` | `gateway/api-gateway/src/proxy/notification.controller.ts` |
| DELETE | `/api/v1/devices/{deviceId}` | `gateway/api-gateway/src/proxy/notification.controller.ts` |
| GET | `/api/v1/admin/broadcasts` | `gateway/api-gateway/src/proxy/notification.controller.ts` |
| POST | `/api/v1/admin/broadcasts` | `gateway/api-gateway/src/proxy/notification.controller.ts` |
| GET | `/api/v1/admin/broadcasts/{broadcastId}` | `gateway/api-gateway/src/proxy/notification.controller.ts` |
| POST | `/api/v1/admin/broadcasts/{broadcastId}/publish` | `gateway/api-gateway/src/proxy/notification.controller.ts` |
| PUT | `/api/v1/social/users/{userId}/follow` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| DELETE | `/api/v1/social/users/{userId}/follow` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| GET | `/api/v1/social/following` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| PUT | `/api/v1/social/posts/{postId}/pin` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| DELETE | `/api/v1/social/posts/{postId}/pin` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| GET | `/api/v1/social/posts/{postId}/event` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| PUT | `/api/v1/social/posts/{postId}/rsvp` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| DELETE | `/api/v1/social/posts/{postId}/rsvp` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| POST | `/api/v1/social/posts/{postId}/attachments` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| GET | `/api/v1/social/posts/{postId}/attachments/{attachmentId}/download` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| GET | `/api/v1/social/posts/{postId}/attachments` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| DELETE | `/api/v1/social/posts/{postId}/attachments/{attachmentId}` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| PATCH | `/api/v1/social/channels/{channelId}` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| GET | `/api/v1/social/channels/{channelId}/members` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| PUT | `/api/v1/social/channels/{channelId}/members/{userId}` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| DELETE | `/api/v1/social/channels/{channelId}/members/{userId}` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| PUT | `/api/v1/social/posts/{postId}/acknowledgement` | `gateway/api-gateway/src/proxy/social-extension.controller.ts` |
| GET | `/api/v1/social/channels` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| POST | `/api/v1/social/channels` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| PUT | `/api/v1/social/channels/{channelId}/membership` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| DELETE | `/api/v1/social/channels/{channelId}/membership` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| GET | `/api/v1/social/posts` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| POST | `/api/v1/social/posts` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| GET | `/api/v1/social/posts/{postId}` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| PATCH | `/api/v1/social/posts/{postId}` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| DELETE | `/api/v1/social/posts/{postId}` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| GET | `/api/v1/social/posts/{postId}/comments` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| POST | `/api/v1/social/posts/{postId}/comments` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| PATCH | `/api/v1/social/comments/{commentId}` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| DELETE | `/api/v1/social/comments/{commentId}` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| PUT | `/api/v1/social/posts/{postId}/reaction` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| DELETE | `/api/v1/social/posts/{postId}/reaction` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| PUT | `/api/v1/social/posts/{postId}/save` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| DELETE | `/api/v1/social/posts/{postId}/save` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| POST | `/api/v1/social/posts/{postId}/reports` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| GET | `/api/v1/social/admin/reports` | `gateway/api-gateway/src/proxy/social.controller.ts` |
| POST | `/api/v1/social/admin/posts/{postId}/moderation` | `gateway/api-gateway/src/proxy/social.controller.ts` |
