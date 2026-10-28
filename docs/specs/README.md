# Quản lý công việc theo phase

> **Tiến độ mới nhất 22/10/2026:** [Hiện trạng, bằng chứng kiểm thử và việc cần làm](../Base_du_an/SYSTEM_PROGRESS_2026-10-22.md). Backend đã mở rộng; chưa nghiệm thu toàn hệ thống.

> **Cập nhật 2026-09-16:** ưu tiên [chạy local trực tiếp](../Base_du_an/LOCAL_DEVELOPMENT.md) bằng môi trường đã cài; sau khi local đạt mới [build/run Docker](../Base_du_an/DOCKER_DEVELOPMENT.md). Docker không là điều kiện bắt đầu phát triển local. Ghi bằng chứng hai cách riêng; mô tả Docker-first cũ bên dưới được thay thế bởi quyết định này.

Đây là điểm bắt đầu để chọn việc và cập nhật tiến độ. Các file `00`–`16` và `adr/` là đặc tả tham chiếu dùng chung, không phải phase.

## Các giai đoạn

| Phase | Phạm vi | Điểm vào |
|---|---|---|
| P0 | Foundation, Identity và demo Cây tri thức | [Mở phase](phase-0-foundation-identity/README.md) |
| P1 | Social / Inside | [Mở phase](phase-1-social/README.md) |
| P2 | Learning / Base Square | [Mở phase](phase-2-learning/README.md) |
| P3 | Chat và Notification | [Mở phase](phase-3-chat-notification/README.md) |
| P4 | AI production và tìm kiếm | [Mở phase](phase-4-ai-production/README.md) |
| P5 | Mobile / React Native | [Mở phase](phase-5-mobile/README.md) |
| P6 | Nghiệm thu và triển khai | [Mở phase](phase-6-release/README.md) |

## Cách cập nhật mỗi lần thực hiện

1. Mở phase và file nhóm công việc, chọn ID; đọc [baseline](../Base_du_an/IMPLEMENTATION_BASELINE.md) và spec liên quan.
2. Đối soát task `NEEDS_REVIEW`: kiểm tra code có sẵn, ghi phần đã có/còn thiếu; không dựng lại một cách máy móc.
3. Chỉ chuyển `READY` khi dependency/điều kiện đã đủ. Khi thực sự bắt đầu, đổi thành `IN_PROGRESS`, điền người làm và ngày cập nhật.
4. Cập nhật cột kết quả/còn thiếu và thêm dòng nhật ký với file, lệnh kiểm tra, kết quả. Chỉ `DONE` khi đạt tiêu chí hoàn thành.
5. Cập nhật ghi chú điều phối và checklist nghiệm thu trong README của phase. Thay đổi hành vi/API/data phải cập nhật spec và [TRACEABILITY](TRACEABILITY.md).

| Trạng thái | Ý nghĩa |
|---|---|
| `NEEDS_REVIEW` | Chưa đối soát hiện trạng; không có nghĩa là chưa có code |
| `NOT_STARTED` | Đã xác nhận chưa bắt đầu |
| `READY` | Đủ điều kiện bắt đầu |
| `IN_PROGRESS` | Đang thực hiện |
| `BLOCKED` | Có vướng mắc cụ thể; ghi cách tháo gỡ |
| `DONE` | Đạt tiêu chí và có bằng chứng kiểm tra |
| `DEFERRED` | Chủ động hoãn/chưa mở phạm vi |
| `CANCELLED` | Không làm nữa; giữ lịch sử và lý do |

## Quy tắc quản lý

- Mỗi ID chỉ có một dòng trạng thái chính trong file công việc của phase. Không cập nhật bản sao ở tracker cũ.
- Ngày dùng `YYYY-MM-DD`. Người làm có thể là tên bạn hoặc AI/người được giao.
- ID và dependency cũ được giữ nguyên; chúng là đầu vào kế hoạch, vẫn cần kiểm tra khi chọn việc.
- P0–P5 theo backlog/baseline đã chốt. P6 gom nghiệm thu và triển khai cuối từ roadmap cũ; production vẫn hoãn. AI và mobile không bắt buộc cho mọi bản phát hành.
- Có thể nghiệm thu bản local/dev trong phạm vi được chọn trước khi mở AI/mobile/production.
- Các spec cũ mô tả hiện trạng ngày 2026-09-10 có phần lỗi thời. Dùng source và bằng chứng kiểm tra cho hiện trạng, baseline cho phạm vi; không tự đánh dấu hoàn thành theo spec cũ.
- Không tính phần trăm khi còn `NEEDS_REVIEW`; các task có khối lượng khác nhau nên tỷ lệ số task không phải tỷ lệ nỗ lực.

## Prompt sử dụng

```text
Thực hiện các task [ID cụ thể] trong specs/phase-0-foundation-identity/.
Đọc README phase, baseline và spec liên quan. Kiểm tra phần code đã có,
hoàn thiện phần còn thiếu theo dependency. Cập nhật trạng thái, người làm,
ngày, bằng chứng kiểm tra và nhật ký ngay trong file công việc tương ứng.
```

## Tài liệu tham chiếu

- [Đặc tả tổng thể](PROJECT_SPEC.md)
- [Danh mục module](Cac_giai_doan_can_lam/02-module-catalog.md), [chức năng](Cac_giai_doan_can_lam/03-functional-spec.md), [luồng người dùng](Cac_giai_doan_can_lam/04-user-flows.md)
- [Database](../database/README.md), [API](Cac_giai_doan_can_lam/06-api-spec.md), [frontend](Cac_giai_doan_can_lam/07-frontend-spec.md)
- [Quy trình cập nhật spec](SPEC_WORKFLOW.md)
- [Idea Inbox và quyết định](../Base_du_an/IMPLEMENTATION_PROGRESS.md)
- [Kế hoạch Admin Portal / Filament](phase-0-foundation-identity/02-admin-portal-plan.md)
