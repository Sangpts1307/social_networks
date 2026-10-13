# Phase 2 — Learning / Base Square

[← Tất cả phase](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

- **Trạng thái phase:** Đang lập kế hoạch mở sớm phần Knowledge Foundation
- **Người phụ trách:** Chủ dự án + AI
- **Ngày bắt đầu / kết thúc:** 2026-09-19 / —
- **Cập nhật gần nhất:** 2026-09-19 (kế hoạch Learning → Knowledge Graph → AI)
- **Điều kiện bắt đầu:** Có thể bắt đầu S0 baseline reconciliation ngay. Document upload binary, production AI và phát hành graph vẫn phụ thuộc Identity/RBAC, object storage, Neo4j và các quyết định liên quan.

## Các nhóm công việc

- [Kế hoạch Knowledge Foundation](00-ai-knowledge-foundation-plan.md) — thứ tự Learning → Graph → AI và các decision gate
- [Kế hoạch thực thi hiện tại](03-execution-plan.md) — mốc R0–R6, contract Q&A và tiêu chí chuyển mốc
- [Q&A và thư viện tài liệu](01-qa-documents.md) — 15 công việc
- [Mở rộng đang hoãn: leaderboard, coin, course](02-deferred-extensions.md) — 4 công việc

## Điều kiện kết thúc

- [ ] Hỏi → trả lời → vote/best answer; upload → duyệt → xem tài liệu published.
- [ ] Các task bắt buộc có trạng thái `DONE` và bằng chứng; task ngoài phạm vi/hoãn được ghi lý do.
- [ ] API, dữ liệu, UI và quyền truy cập được kiểm tra theo phạm vi phase.
- [ ] Cập nhật spec liên quan, TRACEABILITY và hướng dẫn local/run.

## Ghi chú điều phối

- Đã hoàn tất: S0 đối soát Learning trên MySQL test riêng; migration/seed lặp, health và cleanup PASS. `learning_db` local đã có 3 migration + seed demo.
- Đang làm: S1 taxonomy + document metadata read-only đã có API/Gateway/Vue; browser E2E bằng account local là bằng chứng còn lại. S2 Q&A backend/Gateway MVP đã bắt đầu và các core API đã PASS test.
- Vướng mắc: Q&A comments/UI, RBAC taxonomy, object storage, Neo4j runtime và AI provider chưa được mở; legacy manual-accept fields cần data audit trước khi xóa.
- Công việc tiếp theo: hoàn tất browser E2E và Q&A UI/comments, sau đó làm S3 event/provenance. AI chỉ bắt đầu sau S3–S5 và các decision gate.
