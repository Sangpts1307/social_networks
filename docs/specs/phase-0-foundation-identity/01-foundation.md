# P0 — Foundation / Gateway / DevOps

[← Tổng quan phase](README.md) · [Hướng dẫn cập nhật](../README.md) · [Baseline](../../Base_du_an/IMPLEMENTATION_BASELINE.md)

> Tách backlog ngày 2026-09-15; chưa phải kết quả kiểm toán code. `NEEDS_REVIEW` nghĩa là cần đối soát source và kiểm tra trước khi xác định trạng thái. Task `DEFERRED`/`BLOCKED` vẫn giữ giới hạn phạm vi cũ dù có thể đã tồn tại bộ khung.

## Thứ tự triển khai từ 2026-09-16

Ưu tiên ứng dụng và database chạy trực tiếp trên máy đã cài môi trường. PLAT-004/005/006/011/012/015 kiểm chứng local; Docker được tách sang PLAT-016–018, chưa bắt đầu. Trạng thái dưới đây dựa trên đợt kiểm tra 2026-09-16; local và Docker được nghiệm thu riêng.

## Công việc

| ID | Công việc cần làm | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành | Người làm | Cập nhật | Kết quả / còn thiếu |
|---|---|---|---|---|---|---|---|
| PLAT-001 | Kiểm kê workspace hiện tại, xác nhận không ghi đè tài sản người dùng | `DONE` | — | Inventory + quyết định thư mục source được ghi log | AI | 2026-09-16 | Đã kiểm kê source/môi trường; giữ source tại social_networks, không scaffold lại/không sửa dữ liệu. Xem báo cáo kiểm tra. |
| PLAT-002 | Tạo cấu trúc source Laravel/Vue theo service boundary đã chốt | `DONE` | PLAT-001 | Tree source + README run guide | AI | 2026-09-16 | Có source Laravel/Vue/Nest/FastAPI; Web build, Gateway build/typecheck và HTTP smoke Web/Gateway/Identity PASS. Đây là bộ khung, không phải nghiệp vụ hoàn chỉnh. |
| PLAT-003 | Chuẩn hóa `.env.example` và quy tắc không commit secret | `DONE` | PLAT-002 | Secret scan/manual review PASS | AI | 2026-09-16 | Chuẩn hóa template local, thêm root .gitignore; ignore checks và rà soát template/pattern secret PASS trong phạm vi báo cáo; không đổi .env thật. |
| PLAT-004 | Kiểm kê và xác minh kết nối dịch vụ local cần dùng: MySQL, MongoDB, Redis, object storage | `IN_PROGRESS` | PLAT-002 | Health/kết nối local PASS theo phạm vi task; ghi host/port, không ghi secret | AI | 2026-09-16 | MySQL Identity local kết nối PASS; đã xác minh schema/ledger. Các dịch vụ local khác chưa kiểm chứng; không còn blocker credential Identity. |
| PLAT-005 | Xác minh Neo4j chạy trực tiếp trên máy cho graph demo | `BLOCKED` | PLAT-004 | Neo4j local health + connection test PASS | AI | 2026-09-16 | Có Cypher seed/driver khai báo; chưa thấy listener Neo4j hoặc xác nhận endpoint local. Graph API hiện trả fixtures. |
| PLAT-006 | Thiết lập DB migration runner riêng theo từng service | `IN_PROGRESS` | PLAT-004 | Không migration cross-service; migrate PASS trên database kiểm thử local riêng, giữ nguyên dữ liệu hiện có | AI | 2026-09-16 | Identity: 16 migration + seed lặp + local FK/unique tests PASS trên DB riêng; có runner guarded. Còn service/P0 graph metadata khác. Sự cố tạo nhầm schema đã sửa ledger; xem báo cáo Identity. |
| PLAT-007 | Chuẩn hóa UUID, UTC time, error response, pagination cursor | `READY` | PLAT-002 | Shared conventions test/documented | AI | 2026-09-16 | Đã có HasUuids và UTC; thiếu quy ước/error envelope/cursor dùng chung cùng kiểm tra. Chưa đạt toàn bộ task. |
| PLAT-008 | Gateway/BFF skeleton và route health check | `DONE` | PLAT-002 | Gateway health + one protected proxy route test PASS | AI | 2026-09-17 | Schema/API đã triển khai; 12 tests/87 assertions + MySQL Gateway integration PASS; xem báo cáo auth 2026-09-17. Không bao gồm Vue/RBAC Admin. |
| PLAT-009 | Chuẩn hóa REST API versioning và OpenAPI location | `IN_PROGRESS` | PLAT-008 | API contract validation PASS | AI | 2026-09-17 | Auth public /api/v1, Identity/Gateway OpenAPI validation PASS; chưa chuẩn hóa toàn bộ service/API và legacy Gateway health /api/health. |
| PLAT-010 | Transactional outbox/inbox abstraction cho service MySQL | `BLOCKED` | PLAT-006 | Idempotency integration test PASS | AI | 2026-09-16 | Có outbox/processed-event model và migration; chưa có transaction publisher/consumer abstraction và idempotency test; phụ thuộc PLAT-006. |
| PLAT-011 | Redis connection/cache/rate-limit abstraction | `BLOCKED` | PLAT-004 | Local connection + rate-limit test PASS | AI | 2026-09-16 | Có PHP Redis extension/config; chưa có kết nối local và rate-limit abstraction/test được kiểm chứng. |
| PLAT-012 | MinIO private object upload/download abstraction | `BLOCKED` | PLAT-004 | Authorization + signed URL test PASS | AI | 2026-09-16 | Có filesystem config và Compose MinIO hoãn; chưa có endpoint local/adapter private upload-download cùng authorization/signed URL tests. |
| PLAT-013 | Correlation ID, structured log và error tracking interface | `READY` | PLAT-002 | Correlation appears in API log/test | AI | 2026-09-16 | X-Request-Id middleware đã smoke PASS; còn structured request log/error tracking interface và kiểm tra log. |
| PLAT-014 | CI baseline: lint, unit test, migration test | `BLOCKED` | PLAT-002, PLAT-006 | CI workflow green on clean checkout | AI | 2026-09-16 | Chưa có Git/remote/CI workflow được xác nhận; migration test phụ thuộc PLAT-006. Build local không phải CI green. |
| PLAT-015 | Hướng dẫn chạy local trực tiếp và bootstrap seed | `IN_PROGRESS` | PLAT-002, PLAT-004 | Chạy từ checkout sạch bằng môi trường local; seed có hướng dẫn và bằng chứng | AI | 2026-09-16 | Có hướng dẫn local và runner kiểm chứng migration/seed Identity PASS. Chưa nghiệm thu clean checkout/toàn bộ dependency P0; không tuyên bố hoàn thành toàn bộ. |
| PLAT-016 | Kiểm chứng Docker hạ tầng và profile Neo4j sau local | `DEFERRED` | PLAT-004, PLAT-005 | Compose hợp lệ; health/kết nối đạt; dữ liệu và cổng tách local | — | 2026-09-16 | Chờ local đạt; Compose hiện có nhưng chưa kiểm chứng runtime |
| PLAT-017 | Đóng gói ứng dụng bằng Dockerfile và Compose theo phạm vi đã mở | `DEFERRED` | PLAT-002, PLAT-015, PLAT-016 | Build image theo lockfile; app config/network đúng; không đóng gói secret | — | 2026-09-16 | Compose hiện mới có hạ tầng; chỉ AI có Dockerfile riêng |
| PLAT-018 | Kiểm tra Docker build/run và hoàn thiện hướng dẫn cách 2 | `DEFERRED` | PLAT-017 | Health + migration trên DB riêng + E2E tương đương local; ghi lệnh và kết quả | — | 2026-09-16 | Không dùng để chặn phát triển local |

## Nhật ký thực hiện

Thêm một dòng sau mỗi lần làm; ghi ID để liên kết đúng công việc.

| Ngày | ID | Người làm | Đã thực hiện | File / API / migration | Lệnh kiểm tra và kết quả | Vướng mắc / bước tiếp theo |
|---|---|---|---|---|---|---|
| 2026-09-16 | PLAT-004, PLAT-005, PLAT-006, PLAT-015, PLAT-016–018 | AI | Cập nhật theo yêu cầu chủ dự án: local trực tiếp trước, Docker sau | Baseline, LOCAL_DEVELOPMENT, DOCKER_DEVELOPMENT | Kiểm tra tài liệu và liên kết; chưa kiểm chứng chạy ứng dụng | Giữ NEEDS_REVIEW cho các task local; Docker DEFERRED |
| 2026-09-16 | PLAT-001–015 | AI | Đối soát source và local; sửa Gateway startup/dependencies, mẫu env, ignore; bổ sung doctor/smoke | [Báo cáo kiểm tra](../../Base_du_an/FOUNDATION_AUDIT_2026-09-16.md) | Web/Gateway build PASS; 3 HTTP smoke PASS; 196 PHP syntax PASS; MySQL auth BLOCKED | Chờ chủ dự án báo cập nhật .env; sau đó kiểm tra migration trên DB riêng |
| 2026-09-16 | PLAT-004/006/015; ID-DB-001/002/003/004/011 | AI | Kiểm chứng Identity migration/seed, sửa seeder và khắc phục lỗi schema connection của script | [Báo cáo Identity](../../Base_du_an/IDENTITY_DATABASE_VERIFICATION_2026-09-16.md) | 16 migrations + repeated seed/constraints PASS; migrate:status 16 Ran | identity_db có schema trống và ledger, chưa seed user; tiếp tục schema/auth |
| 2026-09-17 | Auth/session + PLAT-008 | AI | Thêm migration auth, token hash/revocation, password change và protected proxy | [Báo cáo auth](../../Base_du_an/AUTH_SESSION_IMPLEMENTATION_2026-09-17.md) | 12 tests/87 assertions; MySQL HTTP integration và cleanup PASS; 2 contracts PASS | Còn Admin unlock/RBAC/Audit; Vue auth được cập nhật cùng ngày trong nhóm WEB-ID; Foundation chưa hoàn tất |
