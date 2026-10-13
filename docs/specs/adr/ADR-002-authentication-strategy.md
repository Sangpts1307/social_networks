# ADR-002 — Auth/session local Phase 0

## Trạng thái

Đã triển khai kỹ thuật ngày 2026-09-17 trong phạm vi yêu cầu hoàn thiện schema đăng nhập, auth/session và protected Gateway. Các thông số chưa có trong baseline được ghi rõ là default triển khai local, không phải quyết định production do chủ dự án phê duyệt.

## Lựa chọn và lý do

Identity cấp opaque Bearer token và lưu hash trong user_sessions. Gateway introspect phiên qua Identity trước protected proxy, Identity kiểm tra lại tại endpoint. Cách này tận dụng schema session và thu hồi ngay khi logout/password change/lock; đổi lại phụ thuộc Identity và thêm round-trip mỗi request. JWT bất đối xứng vẫn là ưu tiên kiến trúc dài hạn, chưa được triển khai ở lượt local này.

Email/password, khóa 10 lần, Admin provision và bắt buộc đổi password theo baseline. Default local: session 480 phút, password mới 12–72 ký tự và không quá 72 UTF-8 bytes, gồm hoa/thường/số/ký hiệu; tăng password_version và thu hồi tất cả phiên khi đổi password. Không có refresh token. Email trùng xuyên organization bị từ chối chung, không tự chọn organization.

Gateway → Identity có shared service key riêng qua header; chỉ lưu trong env phía server, không gửi cho browser. Không thay thế TLS/mTLS/rotation cho production. Các endpoint auth trả user projection tối thiểu; admin unlock/RBAC/Audit còn task riêng.

## Bằng chứng và giới hạn

Xem [implementation và kiểm thử](../../Base_du_an/AUTH_SESSION_IMPLEMENTATION_2026-09-17.md) và [contract Gateway](../../../contracts/openapi/gateway-identity-v1.yaml). Bộ kiểm thử có unit/feature in-memory và HTTP với MySQL thật; chưa có UI E2E hoặc concurrency stress test.
