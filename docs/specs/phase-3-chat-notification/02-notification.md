# P3 — Notification

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| NOTIF-DB-001 | Migration in-app notifications | `DEFERRED` | PLAT-006, ID-DB-003 | Recipient index/cursor test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| NOTIF-DB-002 | Migration push devices and delivery attempts | `DEFERRED` | NOTIF-DB-001 | Token uniqueness/retry test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| NOTIF-DB-003 | Migration processed events/outbox | `DEFERRED` | PLAT-010 | Duplicate event test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| NOTIF-API-001 | Create in-app notification from domain event | `DEFERRED` | NOTIF-DB-001, NOTIF-DB-003 | Idempotency test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| NOTIF-API-002 | List/read in-app notifications | `DEFERRED` | NOTIF-DB-001 | Recipient isolation test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| NOTIF-PUSH-001 | Register/revoke FCM device token | `DEFERRED` | NOTIF-DB-002, ID-AUTH-007 | Owner-only test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| NOTIF-PUSH-002 | Send/retry FCM notification | `DEFERRED` | NOTIF-PUSH-001 | Provider failure retry test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| NOTIF-EVENT-001 | Subscribe comment/answer/mention events | `DEFERRED` | NOTIF-API-001, SOC-API-005, LEARN-QA-007 | Duplicate suppression test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| NOTIF-EVENT-002 | Subscribe document-published/chat-message/announcement events | `DEFERRED` | NOTIF-API-001, CHAT-API-004, SOC-API-011, LEARN-DOC-003 | Targeting test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| NOTIF-WEB-001 | Notification inbox/read UI | `DEFERRED` | NOTIF-API-002 | Browser E2E test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — |
