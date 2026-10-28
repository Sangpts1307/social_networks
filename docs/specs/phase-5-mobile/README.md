# Phase 5 — Mobile / React Native

[← Tất cả phase](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

- **Trạng thái phase:** IN_PROGRESS
- **Người phụ trách:** —
- **Ngày bắt đầu / kết thúc:** — / —
- **Cập nhật gần nhất:** 2026-09-15 (tổ chức tài liệu)
- **Điều kiện bắt đầu:** Stack đã chốt 2026-10-16; API tương ứng cần ổn định trước tích hợp.

## Các nhóm công việc

- [Mobile / React Native](01-tasks.md) — 7 công việc

## Điều kiện kết thúc

- [ ] Ứng dụng dùng Gateway, đăng nhập/feed/chat/push được kiểm tra trên thiết bị.
- [ ] Các task bắt buộc có trạng thái `DONE` và bằng chứng; task ngoài phạm vi/hoãn được ghi lý do.
- [ ] API, dữ liệu, UI và quyền truy cập được kiểm tra theo phạm vi phase.
- [ ] Cập nhật spec liên quan, TRACEABILITY và hướng dẫn local/run.

## Ghi chú điều phối

- Task đang làm: —
- Vướng mắc: —
- Công việc tiếp theo: —

## Thiết kế và môi trường

- [Thiết kế React Native và cấu trúc đơn giản](02-mobile-design.md).
- [Cài đặt, preview và build](../../../apps/mobile/README.md).
- Android/iOS là nền tảng mục tiêu; kiểm tra riêng trên từng nền tảng và tablet khi nghiệm thu. App demo đã có source; kiểm thử API thật/native và push/chat chưa hoàn thành.

## Tiến độ 2026-10-19

Đã tạo Expo TypeScript + NativeWind, login/đổi mật khẩu/phiên SecureStore, feed/phân trang/đăng bài/thích qua Gateway, thư viện metadata và hồ sơ. Có chế độ demo tách biệt dữ liệu thật. Typecheck/lint/export Android/iOS/web đạt; doctor 20/20. Đã smoke test browser 390x844. Chưa kiểm tra API thật vì Gateway chưa chạy; chưa chạy native, chưa làm chat/push. Xem apps/mobile/README.md để chạy.

## Ưu tiên hiện tại: demo mạng xã hội

Bảng tin, Kênh, Đã lưu, Cá nhân; tương tác bài viết và bình luận chạy được trong demo. Learning đã đưa ra khỏi điều hướng demo. Xem [thiết kế cập nhật](02-mobile-design.md) và [kịch bản trình diễn](../../../apps/mobile/README.md). Chưa nghiệm thu native/API thật.
