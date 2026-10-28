# P5 — Mobile / React Native

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| MOB-001 | Chốt stack mobile, thiết bị hỗ trợ và phạm vi màn hình | `NEEDS_REVIEW` | Quyết định stack đã có; cần chốt màn hình MVP | Ghi quyết định stack và danh sách màn hình | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| MOB-002 | Khởi tạo app và API client chỉ gọi Gateway | `IN_PROGRESS` | MOB-001 | Expo TypeScript + NativeWind chạy được; API client chỉ gọi Gateway | — | — | Đã có source demo 2026-10-19; còn kiểm tra API thật và thiết bị native. |
| MOB-003 | Đăng nhập, đổi mật khẩu lần đầu, lưu phiên và đăng xuất | `IN_PROGRESS` | MOB-002, ID-AUTH-011 | Kiểm tra phiên hết hạn/thu hồi và bảo vệ màn hình | — | — | Đã có source demo 2026-10-19; còn kiểm tra API thật và thiết bị native. |
| MOB-004 | Feed và xem chi tiết bài viết | `IN_PROGRESS` | MOB-003, SOC-API-003 | Kiểm tra pagination, trạng thái lỗi và quyền truy cập | — | — | Đã có source demo 2026-10-19; còn kiểm tra API thật và thiết bị native. |
| MOB-005 | Chat, lịch sử, read/unread và reconnect | `DEFERRED` | MOB-003, CHAT-RT-002 | Hai thiết bị gửi/nhận; reconnect không trùng tin | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| MOB-006 | Đăng ký/thu hồi push token và mở thông báo | `DEFERRED` | MOB-003, NOTIF-PUSH-002 | Kiểm tra push đúng tài khoản và sau logout | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| MOB-007 | Kiểm tra thiết bị và đóng gói bản nghiệm thu | `DEFERRED` | MOB-004, MOB-005, MOB-006 | Bản build Android/iOS theo phạm vi nghiệm thu, kết quả kiểm tra thiết bị và hướng dẫn cài | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — |

### Quyết định 2026-10-16

MOB-001: Đã chốt React Native + Expo + TypeScript + NativeWind, Android/iOS; chưa đánh dấu DONE vì danh sách màn hình MVP và phạm vi nghiệm thu chưa chốt. MOB-002 chưa triển khai. Xem [thiết kế mobile](02-mobile-design.md).

### Triển khai 2026-10-19

MOB-002/003/004: source đã có tại apps/mobile; TypeScript/lint/export cả ba nền tảng đạt, doctor 20/20. Smoke browser demo đạt: thích, đăng bài, thư viện, hồ sơ, logout, form validation. Chưa đánh dấu DONE: Gateway chưa chạy khi kiểm tra, chưa có bằng chứng API thật/native. MOB-005/006 chưa triển khai; MOB-007 chưa có APK/IPA nghiệm thu.

### Demo Social được ưu tiên — 2026-10-19

MOB-004 có demo tương tác gồm feed, tìm/lọc kênh, đăng/sửa/xóa bài, cảm xúc, lưu bài, bình luận/trả lời và bài cá nhân. State giữ chung qua bốn tab; không đưa Learning vào luồng demo. Smoke browser 390×844 đạt các luồng trên. Task vẫn IN_PROGRESS do chưa nghiệm thu API thật/native; không suy ra chat/push hoặc media đã triển khai. Xem apps/mobile/README.md.

### 2026-10-20 — mở rộng demo Social

MOB-004: UI Kênh gợi ý/đã tham gia; composer Bài viết/Story, ảnh/video từ system picker và playback local; Story chữ/video đã smoke test browser. Có TTL 24 giờ. Chưa có upload/Story service thật, chưa kiểm tra native; giữ IN_PROGRESS. Typecheck/lint, doctor 20/20 và export ba nền tảng đạt.
