# P5 — Mobile / Android

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| MOB-001 | Chốt stack mobile, thiết bị hỗ trợ và phạm vi màn hình | `BLOCKED` | Quyết định chủ dự án; Web API ổn định | Ghi quyết định stack và danh sách màn hình | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| MOB-002 | Khởi tạo app và API client chỉ gọi Gateway | `DEFERRED` | MOB-001 | Build và kết nối dev thành công | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| MOB-003 | Đăng nhập, đổi mật khẩu lần đầu, lưu phiên và đăng xuất | `DEFERRED` | MOB-002, ID-AUTH-011 | Kiểm tra phiên hết hạn/thu hồi và bảo vệ màn hình | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| MOB-004 | Feed và xem chi tiết bài viết | `DEFERRED` | MOB-003, SOC-API-003 | Kiểm tra pagination, trạng thái lỗi và quyền truy cập | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| MOB-005 | Chat, lịch sử, read/unread và reconnect | `DEFERRED` | MOB-003, CHAT-RT-002 | Hai thiết bị gửi/nhận; reconnect không trùng tin | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| MOB-006 | Đăng ký/thu hồi push token và mở thông báo | `DEFERRED` | MOB-003, NOTIF-PUSH-002 | Kiểm tra push đúng tài khoản và sau logout | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| MOB-007 | Kiểm tra thiết bị và đóng gói bản nghiệm thu | `DEFERRED` | MOB-004, MOB-005, MOB-006 | Bản build, kết quả kiểm tra và hướng dẫn cài | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — |
