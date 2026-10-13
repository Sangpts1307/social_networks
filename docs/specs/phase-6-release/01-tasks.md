# P6 — Nghiệm thu và triển khai

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| REL-001 | Chốt phạm vi phiên bản, môi trường và tiêu chí nghiệm thu | `BLOCKED` | Quyết định chủ dự án | Danh sách phase/feature phát hành; mục hoãn được ghi rõ | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| REL-002 | Chạy E2E các luồng trong phạm vi phát hành | `DEFERRED` | REL-001; các task thuộc phạm vi phát hành | Báo cáo pass/fail, lỗi còn lại và bằng chứng | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| REL-003 | Rà soát bảo mật, phân quyền và hiệu năng theo tải mục tiêu | `DEFERRED` | REL-002 | Kiểm tra truy cập trái phép, file upload và tải đã thống nhất | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| REL-004 | Chốt hosting, domain/SSL, secrets và cấu hình phát hành | `BLOCKED` | REL-001; quyết định hạ tầng | Tài liệu cấu hình không chứa secret và kế hoạch triển khai | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| REL-005 | Diễn tập migration, backup/restore và rollback trên môi trường thử nghiệm | `DEFERRED` | REL-004 | Lệnh và kết quả phục hồi/rollback thành công | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| REL-006 | Kiểm tra health, log, cảnh báo và worker/retry | `DEFERRED` | REL-004 | Kịch bản lỗi và bằng chứng nhận cảnh báo | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| REL-007 | Hoàn thiện hướng dẫn chạy, sử dụng và bàn giao | `DEFERRED` | REL-002, REL-005, REL-006 | Tài liệu chạy từ môi trường sạch và biên bản nghiệm thu | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| REL-008 | Triển khai phiên bản đã được chấp thuận và smoke test | `DEFERRED` | REL-003, REL-007; quyết định phát hành | Phiên bản, thời điểm, kết quả smoke test và phương án rollback | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — |
