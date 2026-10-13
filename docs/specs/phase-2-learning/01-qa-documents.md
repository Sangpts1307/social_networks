# P2 — Q&A và thư viện tài liệu

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| LEARN-QA-001 | Migration `questions` without manually accepted-answer field | `IN_PROGRESS` | LEARN-DB-002 | Best answer stays derived | AI + chủ dự án | 2026-09-24 | API không đọc/ghi các cột legacy accept; best answer đã derived. Cần kiểm kê dữ liệu trước migration xóa cột. |
| LEARN-QA-002 | Migration `answers` | `DONE` | LEARN-QA-001 | Question ownership FK test | AI + chủ dự án | 2026-09-24 | Schema đã có; create/edit/delete answer chỉ trong question thuộc tổ chức caller, Feature test PASS. |
| LEARN-QA-003 | Migration `qa_votes` supporting +1/-1 | `DONE` | LEARN-QA-002 | One vote per user/target test | AI + chủ dự án | 2026-09-24 | `PUT` vote `-1/0/1`, unique per user/target và test request lặp PASS. |
| LEARN-QA-004 | Migration Q&A comments | `DEFERRED` | LEARN-QA-002 | Parent target validation test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| LEARN-QA-005 | Create published non-anonymous question | `DONE` | LEARN-QA-001 | Auth/validation test | AI + chủ dự án | 2026-09-24 | Gateway context là author/organization; validation category-topic tenant scope PASS. |
| LEARN-QA-006 | List/filter/paginate questions | `DONE` | LEARN-QA-005 | Cursor test | AI + chủ dự án | 2026-09-24 | List tenant-scoped, filters và opaque cursor; Feature cursor test PASS. |
| LEARN-QA-007 | Add/edit/soft-delete own answer | `DONE` | LEARN-QA-002 | Ownership test | AI + chủ dự án | 2026-09-24 | API create/PATCH/DELETE; cross-user edit bị 403, soft-delete giảm count; Feature test PASS. |
| LEARN-QA-008 | Cast/change/remove Q&A vote | `DONE` | LEARN-QA-003 | Idempotency/race test | AI + chủ dự án | 2026-09-24 | Set/change/remove cùng unique constraint; same request không thêm vote; Feature test PASS. |
| LEARN-QA-009 | Calculate best answer from score, then created time, then UUID | `DONE` | LEARN-QA-007, LEARN-QA-008 | Tie-break test | AI + chủ dự án | 2026-09-24 | Derived by score ↓, created_at ↑, UUID ↑; no manual accept API; Feature + MySQL integration PASS. |
| LEARN-QA-010 | Add Q&A comment | `DEFERRED` | LEARN-QA-004 | Authorization test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| LEARN-DOC-001 | Upload by allowed uploader roles only | `DEFERRED` | LEARN-DB-005, PLAT-012 | Role/MIME/scan test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| LEARN-DOC-002 | Create immutable document version | `DEFERRED` | LEARN-DOC-001 | Version sequencing test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| LEARN-DOC-003 | Super Admin approve/publish document | `DEFERRED` | LEARN-DOC-002, ID-RBAC-002 | Role/audit test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| LEARN-DOC-004 | All authenticated users view published document | `DEFERRED` | LEARN-DOC-003 | Authenticated vs anonymous test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |
| LEARN-DOC-005 | Soft-delete/restore document by Super Admin | `DEFERRED` | LEARN-DOC-003 | Restore/audit test | — | — | Chưa xác minh mức triển khai; chỉ bắt đầu khi đủ điều kiện mở. |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — |
| 2026-09-24 | LEARN-QA-002/003/005–009 | AI + chủ dự án | Bổ sung Q&A MVP qua Gateway: create/list/detail question, answer ownership, vote và best-answer derived; OpenAPI + isolated integration. | `QuestionController`, routes Learning/Gateway, `learning-v1.yaml`, `QuestionApiTest.php` | `php artisan test`: 4 PASS/45 assertions; `python3 scripts/test-learning-local.py`: MySQL/Gateway/Vue/Q&A PASS và cleanup PASS | Chưa có comments (QA-004/010), Q&A UI/browser E2E, outbox/provenance event và data audit để xóa legacy accept columns. |
