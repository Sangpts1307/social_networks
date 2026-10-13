# P0 — Demo Knowledge Graph

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| AI-GRAPH-001 | Neo4j connection/config health check | `NEEDS_REVIEW` | PLAT-005 | Connection integration test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| AI-GRAPH-002 | Define graph constraints for `User`, `Document`, `Concept`, `Organization` | `NEEDS_REVIEW` | AI-GRAPH-001 | Constraint migration/re-run test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| AI-GRAPH-003 | Define allowed demo relations `AUTHORED`, `HAS_TAG`, `EXPERTISE_IN`, `DERIVED_FROM` | `NEEDS_REVIEW` | AI-GRAPH-002 | Invalid relation rejected/tested | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| AI-GRAPH-004 | Project seeded Identity/Learning demo data to Neo4j | `NEEDS_REVIEW` | ID-DB-003, LEARN-DB-006, AI-GRAPH-003 | Repeatable idempotent projection test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| AI-GRAPH-005 | Store provenance/source IDs for every graph relation | `NEEDS_REVIEW` | AI-GRAPH-004 | Source trace query test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| AI-GRAPH-006 | Graph query: traverse concept → document → author/expert | `NEEDS_REVIEW` | AI-GRAPH-004 | Cypher/API test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| AI-GRAPH-007 | REST endpoint for graph explorer payload | `NEEDS_REVIEW` | AI-GRAPH-006, PLAT-009 | Contract/auth test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| AI-GRAPH-008 | Vue knowledge graph explorer screen | `NEEDS_REVIEW` | AI-GRAPH-007 | Browser E2E demo test | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |
| AI-GRAPH-009 | Label demo as deterministic graph, not AI inference | `NEEDS_REVIEW` | AI-GRAPH-008 | UI/README verification | — | — | Chưa đối soát source; trạng thái cũ: NOT_STARTED |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — |
