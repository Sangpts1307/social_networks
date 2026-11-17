# Phase 5 — Mobile / Flutter

[← Tất cả phase](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

- **Trạng thái phase:** IN_PROGRESS
- **Người phụ trách:** sangpt
- **Cập nhật gần nhất:** 2026-11-17 (Chuyển đổi toàn diện sang Flutter theo kiến trúc `rentid-app`)
- **Điều kiện bắt đầu:** Đã chuẩn hóa kiến trúc sang Flutter (Dart 3), Riverpod, Dio, GoRouter; API endpoints khớp 100% với Web VueJS & Gateway NestJS.

## Các nhóm công việc

- [Mobile / Flutter](01-tasks.md) — 7 công việc

## Điều kiện kết thúc

- [x] Chuyển đổi toàn bộ ứng dụng sang Flutter (Dart 3) đa nền tảng (Android & iOS).
- [x] Áp dụng kiến trúc Clean MVVM + Repository pattern học tập từ `rentid-app`.
- [x] Hỗ trợ build và chạy trên cả Android và iOS bằng lệnh chuẩn `flutter run`.
- [x] Đồng nhất 100% API calls với Web Client (`apps/web/src/api`) và API Gateway NestJS:
  - Auth: `/v1/auth/login`, `/v1/auth/session`, `/v1/auth/logout`, `/v1/auth/password`, `/v1/me/profile`.
  - Social: `/v1/social/posts`, `/v1/social/channels`, `/v1/social/channels/:id/membership`, `/v1/social/posts/:id/reaction`, `/v1/social/posts/:id/save`, `/v1/social/follow-stats`, `/v1/social/posts/:id/comments`.
  - Chat: `/v1/chat/conversations`, `/v1/chat/conversations/:id/messages`, `PUT /v1/chat/conversations/:id/read`, `/v1/directory/users`.
  - Notification: `/v1/notifications`, `/v1/notifications/unread-count`, `PUT /v1/notifications/:id/read`, `PUT /v1/notifications/read-all`, `/v1/devices`.
  - Learning: `/v1/learning/knowledge-items`.
- [x] Kiểm tra phân tích tĩnh `dart analyze` (0 errors) và widget test `flutter test` (passed).
- [ ] Kiểm thử end-to-end trên thiết bị thật và hoàn tất nghiệm thu toàn hệ thống.

## Thiết kế và môi trường

- [Thiết kế Flutter và cấu trúc Feature-First Clean MVVM](02-mobile-design.md).
- [Cài đặt, preview và build](../../../apps/mobile/README.md).
- Android và iOS là nền tảng mục tiêu chính thức (`flutter run -d <device_id>`).
