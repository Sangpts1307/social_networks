# Phase 1 — Social / Inside

[← Tất cả phase](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

- **Trạng thái phase:** IN_PROGRESS — backend Social core đã kiểm chứng; UI và phần mở rộng còn lại
- **Người phụ trách:** Chủ dự án + AI
- **Ngày bắt đầu / kết thúc:** 2026-10-13 / —
- **Cập nhật gần nhất:** 2026-10-13 (API dùng chung Web/Mobile)
- **Điều kiện bắt đầu:** P0 Identity/Gateway và các dependency trong từng task.

## Các nhóm công việc

- [Social: dữ liệu, API và giao diện](01-social.md) — 24 công việc

## Điều kiện kết thúc

- [ ] Tạo bài → feed → tương tác; event RSVP và moderation đúng quyền.
- [ ] Các task bắt buộc có trạng thái `DONE` và bằng chứng; task ngoài phạm vi/hoãn được ghi lý do.
- [ ] API, dữ liệu, UI và quyền truy cập được kiểm tra theo phạm vi phase.
- [ ] Cập nhật spec liên quan, TRACEABILITY và hướng dẫn local/run.

## Ghi chú điều phối

- Đã kiểm chứng: Gateway + Social core API, 13 Feature tests/137 assertions trên SQLite, cùng suite trên MySQL và HTTP integration thật có cleanup.
- Còn lại: attachments/scan, event/RSVP, follows/Identity lookup, managed membership, moderation comment và UI.
- Công việc tiếp theo: nối Social Web vào API đã ổn định; tiếp tục phần backend attachment/event theo contract riêng.

## API dùng chung

Xem [hướng dẫn client và bằng chứng](02-api-client-guide.md), [OpenAPI công khai](../../../contracts/openapi/gateway-social-v1.yaml). Chủ dự án yêu cầu mở Social backend ngày 13/10, dùng chung cho Web/Mobile. Chưa đóng toàn phase.
