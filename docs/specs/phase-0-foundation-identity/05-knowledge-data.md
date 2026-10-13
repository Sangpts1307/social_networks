# P0 — Dữ liệu cho demo tri thức

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| LEARN-DB-001 | Migration `knowledge_topics` | `IN_PROGRESS` | PLAT-006 | CRUD migration test | AI + chủ dự án | 2026-09-19 | Migration/model chạy trên `learning_db` và MySQL test riêng. Còn CRUD policy/API và constraint test chuyên biệt. |
| LEARN-DB-002 | Migration `knowledge_categories` tree | `IN_PROGRESS` | LEARN-DB-001 | Topic/category ownership test | AI + chủ dự án | 2026-09-19 | Migration/model, seed lặp và tree read API có mặt. Còn mutation/cycle test và policy quản trị taxonomy. |
| LEARN-DB-003 | Migration `knowledge_tags` | `IN_PROGRESS` | PLAT-006 | Unique normalized-name test | AI + chủ dự án | 2026-09-19 | Schema đã migrate; chưa có tag API hoặc unique test chuyên biệt. |
| LEARN-DB-004 | Migration minimal `documents` metadata | `IN_PROGRESS` | LEARN-DB-001, ID-DB-003 | Published-document scope test | AI + chủ dự án | 2026-09-19 | Published internal metadata đã tenant-scope qua Gateway; upload/lifecycle write chưa làm. |
| LEARN-DB-005 | Migration `document_versions` and `document_files` metadata | `IN_PROGRESS` | LEARN-DB-004, PLAT-012 | Immutable-version test | AI + chủ dự án | 2026-09-19 | Schema/version provenance có mặt; object storage/file scan và immutable-version workflow chưa làm. |
| LEARN-DB-006 | Seed demo topics/categories/documents with no production PII | `DONE` | LEARN-DB-005 | Seed repeatability test | AI + chủ dự án | 2026-09-19 | Seed demo không có PII production, chạy lặp trên MySQL test PASS; tạo Knowledge Item published làm dữ liệu nguồn graph. |
| LEARN-API-001 | List topic/category tree endpoint | `DONE` | LEARN-DB-002 | Contract + tenant scope test | AI + chủ dự án | 2026-09-19 | `GET /api/v1/learning/topics` qua Gateway, OpenAPI và tenant-scope Feature/HTTP integration PASS. |
| LEARN-API-002 | Read published document metadata endpoint | `DONE` | LEARN-DB-005 | Authenticated-only test | AI + chủ dự án | 2026-09-19 | `GET /api/v1/learning/knowledge-items` chỉ trả document `published`, `internal`, đúng organization; Gateway session guard/first-login denial PASS. |
| LEARN-WEB-001 | Web knowledge-topic tree view | `IN_PROGRESS` | LEARN-API-001 | Browser E2E test | AI + chủ dự án | 2026-09-19 | Base Square Vue đọc taxonomy thật qua Gateway; component test/build/HTTP integration PASS. Còn browser E2E với account local. |
| LEARN-WEB-002 | Web document detail preview metadata view | `IN_PROGRESS` | LEARN-API-002 | Browser E2E test | AI + chủ dự án | 2026-09-19 | Vue hiển thị Knowledge Item đã publish và source/version provenance; chưa preview/download binary. Còn browser E2E với account local. |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| 2026-09-19 | LEARN-DB-001–006, LEARN-API-001/002, LEARN-WEB-001/002 | AI + chủ dự án | Đối soát baseline Learning, mở API read-only taxonomy/Knowledge Item, Gateway proxy và Base Square Vue. | `services/learning-service`, `gateway/api-gateway`, `apps/web`, OpenAPI Learning | MySQL migration + repeat seed + Gateway/Identity/Vue integration PASS; Learning Feature 2/2 PASS; Web 6/6 PASS | Q&A write, taxonomy quản trị, file/object storage, browser E2E, Neo4j và AI chưa làm. |
