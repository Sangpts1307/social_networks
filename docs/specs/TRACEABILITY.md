# Traceability Matrix

> Identity schema/seed đã có bằng chứng [kiểm tra database](../Base_du_an/IDENTITY_DATABASE_VERIFICATION_2026-09-16.md). Auth API, Vue component flow và local HTTP integration đã có; browser E2E với UI và server thật còn thiếu nên FR-AUTH vẫn ở mức partial.

> **Đối soát Foundation 2026-09-16:** đã có source, model/migration, Web/Gateway build và HTTP health local PASS. Các nhận định “chưa có source” bên dưới là snapshot cũ, không còn dùng cho Foundation. Xem [bằng chứng hiện tại](../Base_du_an/FOUNDATION_AUDIT_2026-09-16.md). Chưa xác minh business flow/auth hay migration database; không nâng feature lên VERIFIED_IMPLEMENTED.

## Usage

This matrix maps the evidence-backed requirements to owner boundaries and current implementation artifacts. `NOT_FOUND` is an intentional result, not an invitation to infer missing code. When a feature is approved, replace the relevant `TBD` cells with links to actual contract/schema/code/test files while retaining stable IDs.

| Feature ID | Module | API | Tables/Collections | Backend | Frontend | Tests | Status |
| --- | --- | --- | --- | --- | --- | --- | --- |
| FR-AUTH-001 | MOD-AUTH, MOD-GATEWAY | `POST /api/v1/auth/login`; `GET /api/v1/auth/session`; `POST /api/v1/auth/logout` | `users`, `user_sessions` | Identity auth/session + protected Gateway | Vue login, restore session, logout | `AuthSessionTest`, `test-auth-local.py`, `App.spec.ts` | `VERIFIED_PARTIAL` — còn browser E2E |
| FR-AUTH-002 | MOD-AUTH, MOD-GATEWAY | `POST /api/v1/auth/password` | `users.password_version`, `users.must_change_password_at`, `user_sessions` | Forced password-change policy/session revocation | Vue first-login password-change view | `AuthSessionTest`, `test-auth-local.py`, `App.spec.ts` | `VERIFIED_PARTIAL` — còn browser E2E |
| FR-PROFILE-001 | MOD-AUTH | `TBD` | `TBD` | `NOT_FOUND` | `NOT_FOUND` | `NOT_FOUND` | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-HOME-001 | MOD-WEB, MOD-GATEWAY | `TBD` | `TBD` | `NOT_FOUND` | Static `01-welcome.html` | `NOT_FOUND` | `VERIFIED_PARTIAL` UI only |
| FR-SOCIAL-001 | MOD-SOCIAL, MOD-GATEWAY | `GET /api/v1/social/posts`; gateway-social-v1.yaml | `posts, social_channels, channel_members` | Social core controllers + Gateway auth proxy | UI còn mock/chưa nối API | SocialApiTest; test-social-local.py (SQLite/MySQL/HTTP) | `VERIFIED_PARTIAL` — backend core PASS; UI chưa nối; attachment/event và moderation comment còn lại |
| FR-SOCIAL-002 | MOD-SOCIAL, MOD-GATEWAY | `POST/PATCH/DELETE /api/v1/social/posts`; gateway-social-v1.yaml | `posts, outbox_events` | Social core controllers + Gateway auth proxy | UI còn mock/chưa nối API | SocialApiTest; test-social-local.py (SQLite/MySQL/HTTP) | `VERIFIED_PARTIAL` — backend core PASS; UI chưa nối; attachment/event và moderation comment còn lại |
| FR-SOCIAL-003 | MOD-SOCIAL, MOD-GATEWAY | `GET/POST comments; PATCH/DELETE comments/{id}`; gateway-social-v1.yaml | `post_comments, outbox_events` | Social core controllers + Gateway auth proxy | UI còn mock/chưa nối API | SocialApiTest; test-social-local.py (SQLite/MySQL/HTTP) | `VERIFIED_PARTIAL` — backend core PASS; UI chưa nối; attachment/event và moderation comment còn lại |
| FR-SOCIAL-004 | MOD-SOCIAL, MOD-GATEWAY | `PUT/DELETE posts/{id}/save; GET posts?saved=1`; gateway-social-v1.yaml | `post_saves` | Social core controllers + Gateway auth proxy | UI còn mock/chưa nối API | SocialApiTest; test-social-local.py (SQLite/MySQL/HTTP) | `VERIFIED_PARTIAL` — backend core PASS; UI chưa nối; attachment/event và moderation comment còn lại |
| FR-SOCIAL-005 | MOD-SOCIAL, MOD-GATEWAY | `POST posts/{id}/reports; GET admin/reports; POST admin/posts/{id}/moderation`; gateway-social-v1.yaml | `post_reports, moderation_actions, outbox_events` | Social core controllers + Gateway auth proxy | UI còn mock/chưa nối API | SocialApiTest; test-social-local.py (SQLite/MySQL/HTTP) | `VERIFIED_PARTIAL` — backend core PASS; UI chưa nối; attachment/event và moderation comment còn lại |
| FR-NOTIF-001 | MOD-NOTIFICATION | `TBD` | `TBD` | `NOT_FOUND` | Static bell/summary only | `NOT_FOUND` | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-CHAT-001 | MOD-CHAT | `TBD` | `TBD` | `NOT_FOUND` | `05-messenger.html` placeholder | `NOT_FOUND` | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-LEARN-001 | MOD-LEARNING | `TBD` | `TBD` | `NOT_FOUND` | `08/09` placeholders | `NOT_FOUND` | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-LEARN-002 | MOD-LEARNING | `TBD` | `TBD` | `NOT_FOUND` | `NOT_FOUND` | `NOT_FOUND` | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-LEARN-003 | MOD-LEARNING | `TBD` | `TBD` | `NOT_FOUND` | `NOT_FOUND` | `NOT_FOUND` | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-LEARN-004 | MOD-LEARNING | `TBD` | `TBD` | `NOT_FOUND` | `NOT_FOUND` | `NOT_FOUND` | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-DOC-001 | MOD-LEARNING, MOD-AI | `GET /api/v1/learning/knowledge-items` | `documents`, `document_versions`, taxonomy | Published metadata read API + Gateway guard | Base Square taxonomy/Knowledge Item view | `KnowledgeReadTest`, `test-learning-local.py`, `LearningView.spec.ts` | `VERIFIED_PARTIAL` — read-only internal metadata; upload, file access, AI ingestion and browser E2E remain |
| FR-GAME-001 | MOD-SOCIAL/MOD-LEARNING | `TBD` | `TBD` | `NOT_FOUND` | `NOT_FOUND` | `NOT_FOUND` | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-AI-001 | MOD-AI | `TBD` | `TBD` | Product: `NOT_FOUND`; separate mock demo | `NOT_FOUND` | `NOT_FOUND` | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-AI-002 | MOD-AI, MOD-NOTIFICATION | `TBD` | `TBD` | `NOT_FOUND` | `14/15` placeholders | `NOT_FOUND` | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-MOBILE-001 | MOD-MOBILE, MOD-GATEWAY | `TBD` | `TBD` | `NOT_FOUND` | `NOT_FOUND` | `NOT_FOUND` | `DOCUMENTED_NOT_IMPLEMENTED` |

## Evidence-to-Requirement Sources

| Requirement group | Intended evidence |
| --- | --- |
| Identity/Home/Social/Chat/Learning/Library/Mobile | `ananylics/Social Networks.xlsx`, sheet `Tổng quan` |
| Service ownership/architecture | `Cấu trúc cuối cùng.md`; `services/*/README.md` |
| Static UI partials | `design/exports/html-tailwind/01-welcome.html`, `02-login.html` |
| AI/KG purpose | `Mô tả đề tài.docx`; `knowledge_graph_detail.md`; AI README |

## Update Rules

1. Do not replace a `NOT_FOUND` cell until a file is actually present and inspected.
2. A new FR requires a new stable ID and a new row before code starts.
3. A code path without an FR is an implementation/spec discrepancy; create a `SPEC_GAP` or change request.
4. An API/table/event/test can map to multiple FRs but must list each ID explicitly in its own spec.
5. Do not mark a row `VERIFIED_IMPLEMENTED` until code, supporting contract/data evidence and required tests are present.

## Bằng chứng backend auth — 2026-09-17

FR-AUTH-001/002: `AuthController`, `AuthenticateSession`, migration `2026_09_17_000001_add_auth_session_state`, Gateway `IdentityController`/`IdentitySessionGuard`, 2 OpenAPI contracts, Vue auth views và `AuthSessionTest`/`test-auth-local.py`/`App.spec.ts` đã triển khai/kiểm chứng. Trạng thái vẫn VERIFIED_PARTIAL vì chưa có browser E2E với UI và server thật. Xem [báo cáo](../Base_du_an/AUTH_SESSION_IMPLEMENTATION_2026-09-17.md).
