# P0 — Identity: chức năng và API

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| ID-AUTH-001 | Validate email/password request payload | `DONE` | ID-DB-003 | Request validation unit tests | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| ID-AUTH-002 | Authenticate active account bằng email/password | `DONE` | ID-AUTH-001, ID-DB-008 | Success/failure integration tests | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| ID-AUTH-003 | Tăng failed-login count cho login sai | `DONE` | ID-AUTH-002 | Counter update test | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| ID-AUTH-004 | Khóa account ở lần sai thứ 10 | `DONE` | ID-AUTH-003 | 10-attempt integration test | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| ID-AUTH-005 | Chặn login khi account locked/suspended/terminated | `DONE` | ID-AUTH-004 | Negative authorization tests | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| ID-AUTH-006 | Admin unlock account và reset failed counter | `NEEDS_REVIEW` | ID-RBAC-002 | Audit + authorization test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-AUTH-007 | Cấp session/token sau login thành công | `DONE` | ID-AUTH-002, ID-DB-008 | Token hash/session test | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| ID-AUTH-008 | Logout/revoke một session | `DONE` | ID-AUTH-007 | Revoked token negative test | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| ID-AUTH-009 | Logout all sessions của current user | `DONE` | ID-AUTH-007 | Multi-session revoke test | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| ID-AUTH-010 | Force change password ở login đầu tiên | `DONE` | ID-AUTH-007 | Protected-route denial/change flow test | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| ID-AUTH-011 | Change password của current user và invalidate prior sessions | `DONE` | ID-AUTH-010 | Password-version/session invalidation test | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| ID-ORG-001 | Create organization (bootstrap only) | `NEEDS_REVIEW` | ID-DB-001 | Authorization + unique test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-ORG-002 | Create org unit | `NEEDS_REVIEW` | ID-DB-005 | Parent/permission test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-ORG-003 | Update org unit | `NEEDS_REVIEW` | ID-ORG-002 | Audit test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-ORG-004 | Move org unit within tree without cycle | `NEEDS_REVIEW` | ID-ORG-002 | Cycle rejection test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-ORG-005 | Archive org unit only after member handling | `NEEDS_REVIEW` | ID-ORG-002 | Negative archive test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-USER-001 | Admin create employee account | `NEEDS_REVIEW` | ID-DB-003, ID-DB-004, ID-DB-006 | Account/provision/audit integration test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-USER-002 | Admin import employees from approved CSV/XLSX format | `NEEDS_REVIEW` | ID-DB-009, ID-USER-001 | Mixed valid/invalid row test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-USER-003 | Assign/replace primary org unit | `NEEDS_REVIEW` | ID-DB-006, ID-USER-001 | Exactly-one-primary test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-USER-004 | Add/remove secondary team membership | `NEEDS_REVIEW` | ID-USER-003 | Membership history test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-USER-005 | Suspend/terminate account without deleting records | `NEEDS_REVIEW` | ID-USER-001 | Existing content reference remains test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-PROFILE-001 | Get current user profile | `NEEDS_REVIEW` | ID-AUTH-007, ID-DB-004 | Owner-only PII test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-PROFILE-002 | Update allowed current user profile fields | `NEEDS_REVIEW` | ID-PROFILE-001 | Field allow-list test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-PROFILE-003 | Upload/replace avatar through private object storage | `NEEDS_REVIEW` | ID-PROFILE-002, PLAT-012 | MIME/size/access test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-PROFILE-004 | Public/internal profile projection excludes sensitive PII | `NEEDS_REVIEW` | ID-PROFILE-001 | Phone/birth/address negative test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-RBAC-001 | Permission middleware/guard | `NEEDS_REVIEW` | ID-DB-007, ID-AUTH-007 | Deny/allow tests | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-RBAC-002 | Super Admin all-permission resolution | `NEEDS_REVIEW` | ID-RBAC-001 | Permission matrix test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-RBAC-003 | HR Manager personnel-only permission resolution | `NEEDS_REVIEW` | ID-RBAC-001 | Cannot moderate/social-admin test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-RBAC-004 | Assign/revoke user role | `NEEDS_REVIEW` | ID-RBAC-001, ID-USER-001 | Audit + effective-date test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-AUDIT-001 | Emit audit event for sensitive identity actions | `NEEDS_REVIEW` | ID-DB-010 | Redacted audit payload test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-AUDIT-002 | Super Admin audit log list/filter | `NEEDS_REVIEW` | ID-AUDIT-001, ID-RBAC-002 | Non-super-admin denial test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-API-001 | Publish OpenAPI auth endpoints | `DONE` | ID-AUTH-011 | Contract validation | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| ID-API-002 | Publish OpenAPI admin user endpoints | `NEEDS_REVIEW` | ID-USER-005 | Contract validation | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| ID-API-003 | Publish OpenAPI org/RBAC endpoints | `NEEDS_REVIEW` | ID-RBAC-004 | Contract validation | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — |
| 2026-09-17 | Auth/session + PLAT-008 | AI | Thêm migration auth, token hash/revocation, password change và protected proxy | [Báo cáo auth](../../Base_du_an/AUTH_SESSION_IMPLEMENTATION_2026-09-17.md) | 12 tests/87 assertions; MySQL HTTP integration và cleanup PASS; 2 contracts PASS | Còn Admin unlock/RBAC/Audit; Vue auth được cập nhật cùng ngày trong nhóm WEB-ID; Foundation chưa hoàn tất |
