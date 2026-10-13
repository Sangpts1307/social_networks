# P4 — AI production

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| AI-PROD-001 | Chọn AI provider/model/region/budget | `BLOCKED` | Owner decision | Written approval/data policy | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| AI-PROD-002 | Approve source-content egress policy | `BLOCKED` | AI-PROD-001, security approval | Approved data classification policy | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| AI-PROD-003 | Build ingest job for published Q&A/document only | `DEFERRED` | AI-PROD-002, LEARN-QA-005, LEARN-DOC-003 | Permission-aware ingest test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| AI-PROD-004 | Chunk content with provenance/version | `DEFERRED` | AI-PROD-003 | Reingest/idempotency test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| AI-PROD-005 | Generate/store embeddings with deletion propagation | `DEFERRED` | AI-PROD-004 | Source withdraw cleanup test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| AI-PROD-006 | Hybrid lexical/vector/graph search | `DEFERRED` | AI-PROD-005 | Authorization + relevance test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| AI-PROD-007 | Risk assessment as moderation proposal only | `DEFERRED` | AI-PROD-002 | No-auto-delete negative test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — |
