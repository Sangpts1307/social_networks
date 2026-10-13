# P1 — Social: dữ liệu, API và giao diện

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| SOC-DB-001 | Migration channels and channel memberships | `DONE` | ID-DB-003, ID-DB-006 | Membership unique test | Chủ dự án + AI | 2026-10-13 | Migration channel/membership có sẵn; MySQL migrate/seed lặp, unique membership, join/leave và scope tests PASS. Managed membership administration còn ngoài lát cắt này. |
| SOC-DB-002 | Migration posts and post stats | `IN_PROGRESS` | SOC-DB-001 | Feed cursor index test | Chủ dự án + AI | 2026-10-13 | Bổ sung index feed cursor; API count từ bảng nguồn, cursor tie-break/MySQL PASS. post_stats chưa có projection worker/rebuild. |
| SOC-DB-003 | Migration post attachment metadata | `DEFERRED` | SOC-DB-002, PLAT-012 | Object key/scan test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| SOC-DB-004 | Migration post event metadata and `event_rsvps` | `DEFERRED` | SOC-DB-002 | One RSVP/user/event test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| SOC-DB-005 | Migration comments with one-level reply policy | `DONE` | SOC-DB-002 | Parent-depth validation test | Chủ dự án + AI | 2026-10-13 | Schema comments có sẵn; Feature + MySQL/HTTP PASS cho ownership, parent cùng post và reply một cấp. |
| SOC-DB-006 | Migration reactions, saves and follows | `IN_PROGRESS` | SOC-DB-002 | Unique interaction tests | Chủ dự án + AI | 2026-10-13 | Reaction/save unique + retry tests PASS; follow chưa có Identity target lookup. |
| SOC-DB-007 | Migration social tags | `DEFERRED` | SOC-DB-002 | Unique normalized-tag test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| SOC-DB-008 | Migration reports, moderation action and outbox | `IN_PROGRESS` | SOC-DB-002, PLAT-010 | Audit/idempotency test | Chủ dự án + AI | 2026-10-13 | Report post, moderation audit và transactional outbox đã PASS. Relay/consumer và moderation comment chưa làm. |
| SOC-API-001 | Create/update/publish normal text post | `DONE` | SOC-DB-002 | Ownership/visibility test | Chủ dự án + AI | 2026-10-13 | PostController + OpenAPI + Gateway: create/edit/publish draft/delete own post; tenant/channel/owner và retry tests PASS. |
| SOC-API-002 | Upload and attach scanned image/file | `DEFERRED` | SOC-DB-003 | Unsafe file denial test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| SOC-API-003 | List feed with cursor pagination | `DONE` | SOC-DB-002 | Tenant/channel access test | Chủ dự án + AI | 2026-10-13 | Feed cursor published_at/id, filter channel/author/type/saved, ACL current và encrypted scoped cursor PASS trên SQLite/MySQL/HTTP. |
| SOC-API-004 | Create event and set RSVP | `DEFERRED` | SOC-DB-004 | RSVP/auth test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| SOC-API-005 | Add comment and one-level reply | `DONE` | SOC-DB-005 | Depth/ownership test | Chủ dự án + AI | 2026-10-13 | Comment create/list/edit/delete, reply một cấp, parent scope, locked comments và author guard PASS qua Gateway. |
| SOC-API-006 | Set/change/remove reaction | `DONE` | SOC-DB-006 | One-reaction-per-user test | Chủ dự án + AI | 2026-10-13 | PUT/DELETE like/love/sad/angry; unique per actor/post, concurrent MySQL retry PASS. |
| SOC-API-007 | Save/unsave post | `DONE` | SOC-DB-006 | Private-save test | Chủ dự án + AI | 2026-10-13 | PUT/DELETE save và saved feed caller-only; current ACL reapplied; private-save tests PASS. |
| SOC-API-008 | Follow/unfollow user | `DEFERRED` | SOC-DB-006 | Self-follow rejection test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| SOC-API-009 | Report content | `IN_PROGRESS` | SOC-DB-008 | Report/audit test | Chủ dự án + AI | 2026-10-13 | Report post + chống trùng open report PASS; report comment chưa làm. |
| SOC-API-010 | Moderation hide/remove/restore content | `IN_PROGRESS` | SOC-DB-008, ID-RBAC-001 | Permission/audit test | Chủ dự án + AI | 2026-10-13 | Post hide/remove/restore/lock/unlock, permission, append-only audit, outbox PASS; moderation comment chưa làm. |
| SOC-API-011 | Publish news/announcement with permission guard | `DONE` | SOC-API-001, ID-RBAC-001 | Member denial test | Chủ dự án + AI | 2026-10-13 | News/announcement yêu cầu title và permission riêng; member denial + explicit permission test PASS; không hard-code role. |
| SOC-WEB-001 | Feed/channel navigation UI | `DEFERRED` | SOC-API-003 | Browser E2E test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| SOC-WEB-002 | Post composer and attachment UI | `DEFERRED` | SOC-API-002 | Browser E2E test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| SOC-WEB-003 | Post detail/interactions/comment UI | `DEFERRED` | SOC-API-005, SOC-API-006 | Browser E2E test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| SOC-WEB-004 | Event RSVP UI | `DEFERRED` | SOC-API-004 | Browser E2E test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| SOC-ADM-001 | Moderation queue UI | `DEFERRED` | SOC-API-009, SOC-API-010 | Role denial E2E test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — |

| 2026-10-13 | SOC-DB-001/002/005/006/008; SOC-API-001/003/005/006/007/009/010/011 | Chủ dự án + AI | Mở Social backend theo yêu cầu, dùng chung Web/Mobile; protected Gateway routes, auth/scope, cursor, retry, outbox/audit | Social controllers/services/middleware; social-v1.yaml; gateway-social-v1.yaml; SocialController/SocialClient | 13 Feature tests/137 assertions SQLite; cùng suite MySQL và HTTP integration + concurrent retry + cleanup PASS; Gateway typecheck/build/OpenAPI PASS | Chưa có UI/E2E; attachment/event/follow/managed membership/moderation comment còn lại. Xem 02-api-client-guide.md. |
