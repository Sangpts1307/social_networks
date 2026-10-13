# Notification Service — `notification_db` MySQL

## Trách nhiệm

Notification Service sở hữu inbox notification, preference, device registration, delivery attempts và broadcasts. Nó nhận event từ owners but does not own post/message/document/question content. `source_type`/`source_id` are references that client opens through Gateway/owner API after authorization.

## Tables

### `notification_templates`

| Column | Type | Null | Description |
| --- | --- | --- | --- |
| `id`, `organization_id` | `CHAR(36)` | No | PK/scope. |
| `code` / `channel` | `VARCHAR(128)` / `VARCHAR(32)` | No | Stable template code and `in_app`, `email`, `push`. |
| `locale` | `VARCHAR(16)` | No | e.g. `vi-VN`. |
| `title_template` / `body_template` | `VARCHAR(500)` / `TEXT` | No | Versioned safe template, never arbitrary executable template. |
| `version`, `status`, timestamps | typed | No | Publish lifecycle. |

Unique: `(organization_id, code, channel, locale, version)`.

### `notifications`

| Column | Type | Null | Default | Constraints / description |
| --- | --- | --- | --- | --- |
| `id` | `CHAR(36)` | No | — | PK. |
| `organization_id` | `CHAR(36)` | No | — | Scope. |
| `recipient_user_id` | `CHAR(36)` | No | — | Identity UUID. |
| `notification_type` | `VARCHAR(64)` | No | — | `post_comment`, `chat_message`, `qa_assignment`, `document_review`, `system_broadcast`, etc. |
| `priority` | `VARCHAR(16)` | No | `normal` | `low`, `normal`, `high`, `urgent`. |
| `title` / `body` | `VARCHAR(500)` / `TEXT` | No | — | Rendered safe localized text. |
| `source_service` | `VARCHAR(64)` | No | — | Owner producer. |
| `source_type` / `source_id` | `VARCHAR(64)` / `CHAR(36)` | Yes | — | Deep-link target reference; no cross FK. |
| `action_url` | `VARCHAR(1000)` | Yes | — | Gateway/internal path, never arbitrary external URL. |
| `payload_json` | `JSON` | Yes | — | Minimal safe metadata; no secret/raw confidential content. |
| `read_at` / `seen_at` | `DATETIME(6)` | Yes/Yes | — | Inbox lifecycle. |
| `expires_at` | `DATETIME(6)` | Yes | — | TTL/retention. |
| `created_at` | `DATETIME(6)` | No | — | UTC. |

Indexes: `INDEX(recipient_user_id, read_at, created_at DESC, id)`, `INDEX(organization_id, notification_type, created_at DESC)`, `INDEX(expires_at)`.

### `user_notification_preferences`

| Column | Type | Null | Description |
| --- | --- | --- | --- |
| `id`, `organization_id`, `user_id` | `CHAR(36)` | No | PK/scope/user reference. |
| `notification_type` | `VARCHAR(64)` | No | Type or `*` default. |
| `in_app_enabled`, `push_enabled`, `email_enabled` | `BOOLEAN` | No | Channel opt-in/organization policy. |
| `quiet_hours_json` | `JSON` | Yes | Timezone-safe user preference. |
| timestamps | standard | No | — |

Unique `(organization_id,user_id,notification_type)`.

### `user_devices`

| Column | Type | Null | Description |
| --- | --- | --- | --- |
| `id`, `organization_id`, `user_id` | `CHAR(36)` | No | PK/scope/user. |
| `platform` | `VARCHAR(32)` | No | `android`, `web`, future. |
| `push_provider` | `VARCHAR(32)` | No | e.g. `fcm`; provider not yet chosen. |
| `push_token_encrypted` | `TEXT` | No | Envelope-encrypted provider token, never log/export. |
| `token_fingerprint` | `CHAR(64)` | No | Lookup/dedup hash. |
| `device_label`, `app_version` | `VARCHAR(255)` | Yes | Support/debug only. |
| `status` | `VARCHAR(32)` | No | `active`, `invalid`, `revoked`. |
| `last_seen_at`, `revoked_at`, timestamps | datetime | Yes | Lifecycle. |

Unique `(push_provider, token_fingerprint)`; index `(user_id,status,last_seen_at DESC)`.

### `notification_deliveries`

| Column | Type | Null | Description |
| --- | --- | --- | --- |
| `id`, `notification_id` | `CHAR(36)` | No | PK/FK. |
| `channel` | `VARCHAR(32)` | No | `in_app`, `push`, `email`, `realtime`. |
| `device_id` | `CHAR(36)` | Yes | Notification device FK for push. |
| `destination_masked` | `VARCHAR(255)` | Yes | Redacted email/token target. |
| `provider` / `provider_message_id` | `VARCHAR(64)` / `VARCHAR(255)` | Yes | External delivery trace. |
| `delivery_status` | `VARCHAR(32)` | No | `queued`, `sent`, `delivered`, `failed`, `suppressed`, `invalid`. |
| `attempt_count`, `next_attempt_at`, `sent_at`, `delivered_at`, `failed_at` | numbers/datetime | — | Retry state. |
| `failure_code`, `failure_detail_redacted` | varchar/text | Yes | No secret/provider raw payload. |
| `created_at`, `updated_at` | datetime | No | — |

Indexes: `(notification_id,channel)`, `(delivery_status,next_attempt_at)`, `(device_id,created_at DESC)`.

### `broadcasts` and `broadcast_targets`

Admin UI can create urgent all-company banner, optional email and immediate push. A broadcast is not a Social post automatically; it may link to a post/announcement via source reference.

| Table | Essential columns | Rules |
| --- | --- | --- |
| `broadcasts` | `id`, `organization_id`, `created_by_user_id`, `title`, `body`, `priority`, `source_service`, `source_type`, `source_id`, `status`, `publish_at`, `expires_at`, `send_email`, `send_push`, timestamps | `status`=`draft`,`scheduled`,`publishing`,`published`,`cancelled`. Publisher materializes notifications async. |
| `broadcast_targets` | `id`, `broadcast_id`, `target_type`, `target_id`, `estimated_recipient_count`, timestamps | `target_type`=`organization`,`org_unit`,`role`,`user`; resolve recipients from Identity at publish time. |
| `broadcast_recipient_runs` | `id`, `broadcast_id`, `recipient_user_id`, `notification_id`, `status`, `created_at` | Idempotent materialization; unique `(broadcast_id,recipient_user_id)`. |

## Reliability tables and invariants

`outbox_events` and `processed_events` use the shared contract in [07-cross-service-data-contracts.md](07-cross-service-data-contracts.md).

1. Recipient can only read/update own notification.
2. Notification payload must remain useful after source content changes but never bypass live permission check when opening target.
3. Delivery attempts are append/update operational state; notification record remains durable even if push/realtime fails.
4. Invalid push tokens are deactivated, not retried indefinitely.
5. Urgent broadcasts require explicit RBAC permission and auditable actor/reason.
6. Notification retention/expiry is policy; current UI does not define it.

## Evidence

- `design/exports/html-tailwind/01-welcome.html`, `03-social-feed.html`, `05-messenger.html`
- `design/exports/admin/04-post-moderation.html`
- `services/notification-service/README.md`; `Cấu trúc cuối cùng.md` section 8
