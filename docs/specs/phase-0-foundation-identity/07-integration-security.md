# P0 — Tích hợp và kiểm tra bảo mật

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|

| INT-001 | Identity user-change event contract | `NEEDS_REVIEW` | ID-DB-010, PLAT-009 | Consumer compatibility test | — | — | Chưa đối soát source; trạng thái cũ: `NOT_STARTED` |
| INT-002 | Identity directory read projection policy | `NEEDS_REVIEW` | ID-PROFILE-004, INT-001 | No PII leakage test | — | — | Chưa đối soát source; trạng thái cũ: `NOT_STARTED` |
| INT-003 | Service-to-service auth strategy | `NEEDS_REVIEW` | PLAT-008 | Unauthorized service denial test | — | — | Chưa đối soát source; trạng thái cũ: `NOT_STARTED` |
| INT-004 | Tenant/organization isolation test suite | `NEEDS_REVIEW` | ID-DB-001 | Cross-org negative tests | — | — | Chưa đối soát source; trạng thái cũ: `NOT_STARTED` |
| INT-005 | Object-storage authorization test suite | `NEEDS_REVIEW` | PLAT-012 | URL expiry/access denial tests | — | — | Chưa đối soát source; trạng thái cũ: `NOT_STARTED` |
| INT-006 | Audit redaction/security-event test suite | `NEEDS_REVIEW` | ID-AUDIT-001 | No password/token/PII raw payload test | — | — | Chưa đối soát source; trạng thái cũ: `NOT_STARTED` |
| INT-007 | Fresh environment E2E: admin provision → first login → password change | `NEEDS_REVIEW` | ID-AUTH-011, WEB-ID-002, PLAT-015 | Automated or scripted PASS evidence | — | — | Chưa đối soát source; trạng thái cũ: `NOT_STARTED` |
| INT-008 | Fresh environment E2E: seeded knowledge tree → graph explorer | `NEEDS_REVIEW` | AI-GRAPH-009, PLAT-015 | Browser/API PASS evidence | — | — | Chưa đối soát source; trạng thái cũ: `NOT_STARTED` |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — |
