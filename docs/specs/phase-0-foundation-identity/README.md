# Phase 0 — Foundation, Identity và demo Cây tri thức

[← Tất cả phase](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

- **Trạng thái phase:** Đang thực hiện — Foundation local đã đối soát
- **Người phụ trách:** Chủ dự án + AI
- **Ngày bắt đầu / kết thúc:** 2026-09-16 / —
- **Cập nhật gần nhất:** 2026-09-17 (auth Web đã nối Gateway; local trước, Docker sau)
- **Điều kiện bắt đầu:** Đọc baseline; rà soát source trước khi chọn task.

## Các nhóm công việc

- [Foundation / Gateway / DevOps](01-foundation.md) — 18 công việc
- [Admin Portal / Filament](02-admin-portal-plan.md) — kế hoạch admin tập trung
- [Identity: database](02-identity-database.md) — 11 công việc
- [Identity: chức năng và API](03-identity-functions.md) — 34 công việc
- [Web: Identity và Admin](04-web-identity-admin.md) — 10 công việc
- [Dữ liệu cho demo tri thức](05-knowledge-data.md) — 10 công việc
- [Demo Knowledge Graph](06-knowledge-graph.md) — 9 công việc
- [Tích hợp và kiểm tra bảo mật](07-integration-security.md) — 8 công việc

## Điều kiện kết thúc

- [ ] Đăng nhập → đổi mật khẩu lần đầu → quản trị theo quyền; demo user–document–concept chạy local.
- [ ] Các task bắt buộc có trạng thái `DONE` và bằng chứng; task ngoài phạm vi/hoãn được ghi lý do.
- [ ] API, dữ liệu, UI và quyền truy cập được kiểm tra theo phạm vi phase.
- [ ] Cập nhật spec liên quan, TRACEABILITY và hướng dẫn local/run.

## Ghi chú điều phối

- Đã hoàn thành: PLAT-001, PLAT-002, PLAT-003.
- Đã triển khai trên Web: login thật, kiểm tra/khôi phục phiên, đổi mật khẩu lần đầu, logout và hiển thị user từ Identity. Build và 5 component-flow tests PASS; local integration qua Gateway PASS.
- Vướng mắc: còn browser E2E Web với server thật; Neo4j/Redis/object storage, Profile, RBAC/Admin và Audit chưa hoàn tất.
- Công việc tiếp theo: hoàn tất browser E2E cho WEB-ID-001/002; triển khai RBAC/Admin unlock/Audit; tiếp tục các dependency local P0 khác. Docker tiếp tục hoãn.

## Hai cách chạy

- [Local trực tiếp](../../Base_du_an/LOCAL_DEVELOPMENT.md): ưu tiên hoàn thiện và nghiệm thu trước.
- [Docker build/run](../../Base_du_an/DOCKER_DEVELOPMENT.md): kiểm chứng riêng sau local. Docker chưa đạt không chặn bắt đầu task local; khi kết thúc toàn bộ Foundation phải ghi rõ kết quả hai cách.

[Báo cáo và bằng chứng 2026-09-16](../../Base_du_an/FOUNDATION_AUDIT_2026-09-16.md).

[Kiểm chứng database Identity và sự cố đã khắc phục](../../Base_du_an/IDENTITY_DATABASE_VERIFICATION_2026-09-16.md).

[Auth/session, protected Gateway và Vue auth flow đã kiểm chứng](../../Base_du_an/AUTH_SESSION_IMPLEMENTATION_2026-09-17.md); browser E2E thật vẫn là điều kiện còn lại của WEB-ID-001/002.
