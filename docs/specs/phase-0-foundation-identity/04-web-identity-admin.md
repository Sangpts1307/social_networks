# P0 — Web: Identity và Admin

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| WEB-ID-001 | Vue login page/API state/error UI | `IN_PROGRESS` | ID-AUTH-007 | E2E success/lock error test | AI + chủ dự án | 2026-09-17 | Đã nối Gateway thật, lưu/khôi phục phiên, logout và lỗi khóa tài khoản; 5 component-flow tests + local Gateway/Identity integration PASS. Còn browser E2E với server thật trước khi `DONE`. |
| WEB-ID-002 | Force-change-password page | `IN_PROGRESS` | ID-AUTH-010 | E2E test | AI + chủ dự án | 2026-09-17 | Đã có form, kiểm tra policy và gọi API đổi mật khẩu; sau thành công xóa token và yêu cầu đăng nhập lại. Component-flow + backend integration PASS; còn browser E2E với server thật. |
| WEB-ID-003 | Current profile page and allowed edit form | `NEEDS_REVIEW` | ID-PROFILE-002 | E2E + PII visibility test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| WEB-ADM-001 | Admin user list/filter/pagination | `NEEDS_REVIEW` | ID-API-002, ID-RBAC-002 | E2E role denial test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| WEB-ADM-002 | Admin create user form | `NEEDS_REVIEW` | WEB-ADM-001, ID-USER-001 | E2E create test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| WEB-ADM-003 | Admin import user screen/result rows | `NEEDS_REVIEW` | WEB-ADM-001, ID-USER-002 | E2E mixed-file test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| WEB-ADM-004 | Admin lock/unlock/suspend action UI | `NEEDS_REVIEW` | WEB-ADM-001, ID-AUTH-006, ID-USER-005 | E2E/audit test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| WEB-ADM-005 | Department tree screen | `NEEDS_REVIEW` | ID-ORG-004 | E2E tree mutation test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| WEB-ADM-006 | Role assignment screen | `NEEDS_REVIEW` | ID-RBAC-004 | E2E authorization test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| WEB-ADM-007 | Audit log screen, Super Admin only | `NEEDS_REVIEW` | ID-AUDIT-002 | E2E denial test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| 2026-09-17 | WEB-ID-001, WEB-ID-002 | AI + chủ dự án | Thay login giả lập bằng API Gateway; thêm restore session, remember session, logout và màn đổi mật khẩu lần đầu. | `apps/web/src/App.vue`, `src/api/auth.ts`, `src/auth/storage.ts`, `src/views/LoginView.vue`, `src/views/ChangePasswordView.vue`, `src/views/WelcomeView.vue`, `apps/web/tests/App.spec.ts` | `npm test`: 5/5 PASS; `npm run build`: PASS; `python3 scripts/test-auth-local.py`: Web/Gateway/Identity/MySQL flow PASS | Chạy browser E2E bằng account fixture trước khi nâng hai task lên `DONE`; WEB-ID-003 và Admin chờ backend tương ứng. |
