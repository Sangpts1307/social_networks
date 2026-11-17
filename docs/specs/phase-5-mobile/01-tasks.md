# P5 — Mobile / Flutter

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Cập nhật ngày 2026-11-17: Đã chuyển đổi hoàn toàn sang Flutter (Dart 3) học tập kiến trúc Feature-First Clean MVVM từ `rentid-app`. Hỗ trợ build Android & iOS và chạy bằng `flutter run`.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / ghi chú |
|---|---|---|---|---|---|---|---|
| MOB-001 | Chốt stack mobile Flutter, thiết bị và kiến trúc chuẩn | `DONE` | — | Kiến trúc Clean MVVM + Repository (Riverpod, Dio, GoRouter, SecureStorage) theo `rentid-app` | sangpt | 2026-11-17 | Đã chốt và cấu hình hoàn thiện. |
| MOB-002 | Khởi tạo app Flutter và API client gọi Gateway | `DONE` | MOB-001 | DioClient, Interceptors, EnvConfig hỗ trợ Android emulator, iOS simulator và thiết bị thật | sangpt | 2026-11-17 | Đã kết nối Gateway, dart analyze 0 lỗi. |
| MOB-003 | Đăng nhập, đổi mật khẩu, lưu phiên SecureStorage | `DONE` | MOB-002 | FlutterSecureStorage, AuthGuard chuyển hướng trên GoRouter, session inspection | sangpt | 2026-11-17 | Đã hoàn thành kèm chế độ demo chuyển đổi linh hoạt. |
| MOB-004 | Feed, Kênh, Bài viết, Bình luận, Cảm xúc, Lưu bài | `DONE` | MOB-003 | Đồng nhất 100% với Web VueJS và Gateway `/v1/social/*` | sangpt | 2026-11-17 | Giao diện chuẩn Facebook/Tailwind, hỗ trợ tìm kiếm bài viết, xem và gửi bình luận. |
| MOB-005 | Chat hội thoại, nhắn tin, danh bạ nhân viên | `DONE` | MOB-003 | Gọi `/v1/chat/conversations`, `/v1/chat/conversations/:id/messages`, `PUT .../read`, `/v1/directory/users` | sangpt | 2026-11-17 | Đã hoàn thành chat UI, danh bạ nhân viên, tạo nhóm/chat 1-1. |
| MOB-006 | Thông báo, đếm chưa đọc, đánh dấu đã đọc | `DONE` | MOB-003 | Gọi `/v1/notifications`, `/v1/notifications/unread-count`, `PUT .../read`, `PUT .../read-all` | sangpt | 2026-11-17 | Đã tích hợp NotificationsView, phân loại icon theo sự kiện và hỗ trợ pull-to-refresh. |
| MOB-007 | Build & chạy native Android/iOS (`flutter run`) | `DONE` | MOB-004..006 | Chạy mượt mà trên iOS Simulator & Android, biên dịch thành công không lỗi mã nguồn | sangpt | 2026-11-17 | Đã build thành công Runner.app trên iOS simulator, macOS, và Android config. |

## Nhật ký thực hiện

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| 2026-11-17 | MOB-001..007 | sangpt | Chuyển đổi toàn diện sang Flutter (Dart 3), kiến trúc Feature-First Clean MVVM từ `rentid-app`. Khớp 100% API calls với Web VueJS. Dọn dẹp mã nguồn thừa. | `apps/mobile/lib/*`, `android/*`, `ios/*` | `dart analyze` (0 errors), `flutter test` (passed), `flutter build ios --simulator --no-codesign` (succeeded) | Sẵn sàng chạy bằng `flutter run` trên mọi thiết bị. |
