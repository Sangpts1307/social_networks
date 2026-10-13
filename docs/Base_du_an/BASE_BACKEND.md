# Backend foundation

> **Cập nhật tiếp nối:** MySQL Identity đã kết nối và kiểm chứng migration/seed thành công. Có sự cố script tạo schema nhầm database và đã sửa ledger, không mất dữ liệu; đọc [báo cáo Identity](IDENTITY_DATABASE_VERIFICATION_2026-09-16.md). Các dòng MySQL auth BLOCKED bên dưới là kết quả trước khi chủ dự án sửa env.

> **Đối soát Foundation 2026-09-16:** đã có source, model/migration, Web/Gateway build và HTTP health local PASS. Các nhận định “chưa có source” bên dưới là snapshot cũ, không còn dùng cho Foundation. Xem [bằng chứng hiện tại](FOUNDATION_AUDIT_2026-09-16.md). Chưa xác minh business flow/auth hay migration database; không nâng feature lên VERIFIED_IMPLEMENTED.

## Components

| Component | Runtime | Persistence | Base status |
|---|---|---|---|
| API Gateway | NestJS | None | CORS, correlation ID, service registry, health |
| Identity | Laravel 12 | `identity_db` MySQL | Organization, users, profile, sessions, RBAC, audit, outbox/inbox |
| Social | Laravel 12 | `social_db` MySQL | Channels, posts, interactions, tags, moderation, outbox/inbox |
| Learning | Laravel 12 | `learning_db` MySQL | Taxonomy, Q&A, documents, courses, rewards, moderation, outbox/inbox |
| Chat | Laravel 12 + MongoDB driver | `chat_db` MongoDB | Conversations, members, messages, display read model, event collections and Mongo index/seed scripts |
| Notification | Laravel 12 | `notification_db` MySQL | Templates, inbox, preferences, devices, deliveries, broadcasts, outbox/inbox |
| AI Knowledge | FastAPI | `ai_knowledge_db` + Neo4j | Deterministic graph demo, Alembic metadata migration and Neo4j seed |

## Ownership rules

- All service-owned relational primary keys are UUIDs.
- `organization_id`, `user_id`, `org_unit_id` from Identity are UUID references, never cross-database foreign keys.
- Each MySQL service has local foreign keys only for its own data.
- Chat does not have a MySQL schema; MongoDB collection indexes are its schema bootstrap.
- Files are object keys for MinIO/S3; Redis is ephemeral; RabbitMQ carries async events; Neo4j owns graph projections.
- Each data-owning service has local outbox/processed-event storage as the starting point for reliable event publishing/consumption.

## Seed topology

Seed fixtures share stable UUIDs. Identity creates the demo organization and three users. Social, Learning, Notification and Chat seed records reference those UUIDs without reading Identity's database. This keeps boundaries valid while making local demos coherent.

## Historical verification (chưa tái kiểm chứng runtime ngày 2026-09-16)

- Identity, Social, Learning and Notification migrations/seeds ran against local MySQL 8.4.
- Social, Learning and Notification recovery migrations preserve partially created schema from initial local validation; no database was reset.
- All four new Laravel services pass PHP syntax checks and expose `GET /api/v1/health`.
- Gateway passes TypeScript typecheck/build; Compose and event schema syntax pass validation.
- Ghi nhận cũ về thiếu `ext-mongodb` không còn áp dụng cho PHP vừa kiểm tra: PHP 8.4 hiện có extension này. MongoDB runtime/model/seeder vẫn cần kiểm chứng local.

Use [LOCAL_DEVELOPMENT.md](LOCAL_DEVELOPMENT.md) for exact local run commands and local trực tiếp trước và Docker sau.
