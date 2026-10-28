# API Specification

## Current implementation

Cập nhật 2026-10-21: báo cáo trước đây ghi “0 endpoint” đã lỗi thời. Repository đã có REST API tại Identity, Social, Learning, Chat, Notification và AI base, đi qua NestJS Gateway.

- [Bảng rà soát nghiệp vụ, phần đã thêm, giới hạn và câu hỏi còn mở](../../api/API_COVERAGE_2026-10-21.md).
- [Danh sách route thực tế](../../api/ROUTE_INVENTORY.md).
- [Inventory JSON sinh từ mã nguồn](../../api/route-inventory.json).
- [OpenAPI additions](../../../contracts/openapi/platform-additions-v1.json), cùng các contract Identity/Social/Learning hiện có trong `contracts/openapi`.

Sinh lại inventory bằng `python3 scripts/inventory-api.py`; kiểm tra OpenAPI bằng `npm run check:contracts` trong `gateway/api-gateway`.

## Invariants

Web/Mobile chỉ gọi Gateway. Mỗi service giữ dữ liệu, validation và quyền resource của mình. Gateway xác thực session, đưa context đã xác minh tới service; không tin các header danh tính do client tự gửi. Internal API có key riêng và không được proxy công khai. Mọi route tài nguyên phải cách ly organization và kiểm tra membership/ACL liên quan. File private chỉ được tải sau scan sạch. Outbox/consumer phải chống xử lý trùng. Không suy ra hoàn thiện nghiệp vụ hay nghiệm thu production chỉ từ số lượng route.

## Current scope decisions

Course/Lesson tiếp tục hoãn. Social không sinh thưởng học tập. Tri thức tích lũy points/coin pending theo rule do Admin sửa, tổng kết tuần/tháng và chỉ cộng ví sau duyệt, bảo toàn lịch sử. AI là provider adapter base và keyword search; không gắn nhãn hybrid/production khi chưa triển khai. Các mục cần chốt và hạn chế môi trường nằm trong báo cáo coverage.
