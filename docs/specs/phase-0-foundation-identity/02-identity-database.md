# P0 — Identity: database

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| ID-DB-001 | Migration `organizations` | `DONE` | PLAT-006 | Fresh migration + unique domain test | AI | 2026-09-16 | Fresh MySQL migration PASS; organization code và domain uniqueness PASS trong DB thử. Xem báo cáo Identity 2026-09-16. |
| ID-DB-002 | Migration `organization_email_domains` | `DONE` | ID-DB-001 | FK/unique domain tests | AI | 2026-09-16 | Migration, unique domain trong organization và FK từ chối organization không tồn tại PASS. |
| ID-DB-003 | Migration `users` gồm email login, password hash, failed count, lock state | `DONE` | ID-DB-001 | Unique email + no plaintext password test | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| ID-DB-004 | Migration `user_profiles` với PII/ avatar metadata | `DONE` | ID-DB-003 | One-profile-per-user test | AI | 2026-09-16 | Migration + one-profile-per-user constraint PASS; không đồng nghĩa API kiểm soát PII đã triển khai. |
| ID-DB-005 | Migration `org_units` tree | `NEEDS_REVIEW` | ID-DB-001 | Parent/cycle validation test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-DB-006 | Migration `org_unit_memberships` và primary membership rule | `NEEDS_REVIEW` | ID-DB-003, ID-DB-005 | Membership invariant test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-DB-007 | Migration RBAC: roles, permissions, role_permissions, assignments | `NEEDS_REVIEW` | ID-DB-003 | Unique role/permission assignment test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-DB-008 | Migration `user_sessions` và `user_devices` | `DONE` | ID-DB-003 | Token hash only + revoke test | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| ID-DB-009 | Migration user import jobs/rows | `NEEDS_REVIEW` | ID-DB-003 | Import-row validation test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-DB-010 | Migration audit projection/outbox/inbox tables | `NEEDS_REVIEW` | ID-DB-003, PLAT-010 | Event idempotency test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-DB-011 | Seed system roles and permissions | `DONE` | ID-DB-007 | Idempotent seed test | AI | 2026-09-16 | Seed 5 roles/permissions chạy hai lần không tăng số lượng; đã sửa seed user để không ghi lại credential/account state. Chưa nghiệm thu RBAC runtime. |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| 2026-09-16 | PLAT-004/006/015; ID-DB-001/002/003/004/011 | AI | Kiểm chứng Identity migration/seed, sửa seeder và khắc phục lỗi schema connection của script | [Báo cáo Identity](../../Base_du_an/IDENTITY_DATABASE_VERIFICATION_2026-09-16.md) | 16 migrations + repeated seed/constraints PASS; migrate:status 16 Ran | identity_db có schema trống và ledger, chưa seed user; tiếp tục schema/auth |
| 2026-09-17 | Auth/session + PLAT-008 | AI | Thêm migration auth, token hash/revocation, password change và protected proxy | [Báo cáo auth](../../Base_du_an/AUTH_SESSION_IMPLEMENTATION_2026-09-17.md) | 12 tests/87 assertions; MySQL HTTP integration và cleanup PASS; 2 contracts PASS | Còn Admin unlock/RBAC/Audit; Vue auth được cập nhật cùng ngày trong nhóm WEB-ID; Foundation chưa hoàn tất |
