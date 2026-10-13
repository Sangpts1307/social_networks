# P3 — Chat

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| CHAT-DB-001 | Mongo connection and collection indexes | `DEFERRED` | PLAT-004 | Index inspection test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-DB-002 | Conversation and member document schema | `DEFERRED` | CHAT-DB-001, ID-DB-003 | Direct-conversation uniqueness test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-DB-003 | Message document schema/cursor index | `DEFERRED` | CHAT-DB-002 | Seek-pagination test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-DB-004 | Read/unread projection schema | `DEFERRED` | CHAT-DB-003 | Concurrent read-state test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-API-001 | Create/reuse direct conversation | `DEFERRED` | CHAT-DB-002 | Same pair returns same conversation test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-API-002 | Create group conversation | `DEFERRED` | CHAT-DB-002 | Member validation test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-API-003 | Add/remove group members by authorized actor | `DEFERRED` | CHAT-API-002 | Permission test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-API-004 | Persist text message idempotently | `DEFERRED` | CHAT-DB-003 | Client-message-ID duplicate test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-API-005 | Attach scanned private file to message | `DEFERRED` | CHAT-API-004, PLAT-012 | MIME/access test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-API-006 | List conversation history by cursor | `DEFERRED` | CHAT-DB-003 | Membership negative test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-API-007 | Mark conversation read | `DEFERRED` | CHAT-DB-004 | Unread counter test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-RT-001 | WebSocket authenticate/connect | `DEFERRED` | CHAT-API-004, ID-AUTH-007 | Invalid token denial test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-RT-002 | Emit persisted message to authorized members | `DEFERRED` | CHAT-RT-001 | No message before persistence test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-WEB-001 | Conversation list/unread UI | `DEFERRED` | CHAT-API-007 | Browser E2E test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| CHAT-WEB-002 | Message history/send UI | `DEFERRED` | CHAT-API-006, CHAT-RT-002 | Realtime E2E test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — |
