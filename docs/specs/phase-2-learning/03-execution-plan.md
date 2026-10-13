# P2 — Kế hoạch thực thi Learning → Knowledge Graph → AI-ready

[← Tổng quan Phase 2](README.md) · [Kế hoạch nền tảng](00-ai-knowledge-foundation-plan.md) · [ADR Graph](../adr/ADR-005-knowledge-graph.md)

**Cập nhật:** 2026-09-24  
**Mục tiêu:** hoàn thiện Learning Service thành nguồn tri thức có kiểm soát truy cập và provenance, sau đó dựng graph deterministic có thể tái tạo. Không gọi model/embedding trong đợt này.

## Kết quả đích của đợt làm trước

Một người dùng đã đăng nhập có thể tạo, trả lời và vote Q&A trong tổ chức của mình; xem taxonomy và Knowledge Item đã publish. Khi một nguồn được publish, cập nhật, archive hoặc xóa, Learning phát một event có version. AI Knowledge consumer dựng projection Neo4j từ event đó, có thể chạy lại an toàn và mọi node/relation đều truy ngược được `source_id` và `source_version`.

`Learning MySQL` là source of truth. Neo4j chỉ là read projection có thể rebuild; không chứa bản sao nội dung đầy đủ, thông tin đăng nhập hay PII profile.

## Điều đã đối soát

| Khu vực | Hiện có | Khoảng trống phải xử lý |
|---|---|---|
| Learning read | Taxonomy và document metadata published đã tenant-scope qua Gateway; integration runner đã có | Browser E2E đăng nhập thực; write API cho Q&A; policy mutation taxonomy/document |
| Q&A schema | `questions`, `answers`, `qa_votes` đã có migration | Chưa có API/validation/ownership/cursor/vote; schema có `accepted_answer_id` và `is_accepted`, mâu thuẫn rule best-answer derived |
| Event | `outbox_events`, `processed_events` đã có schema | Chưa có contract, producer, relay/consumer và idempotency test |
| AI Knowledge | FastAPI health + graph demo fixture + Cypher seed | Chưa kết nối/đọc Neo4j thật, chưa projection từ Learning và chưa có Gateway auth |
| Production AI | Chưa có provider/vector/prompt/evaluation | Giữ `BLOCKED`; không làm trước decision gate |

## Trình tự thực thi

| Mốc | Công việc | Hoàn thành khi | Phụ thuộc |
|---|---|---|---|
| R0 — Baseline | Chạy lại isolated integration, bổ sung browser E2E cho Base Square và chốt contract hiện hữu | Login → Base Square → taxonomy → chọn document → xem provenance PASS | Local MySQL, Identity, Gateway, Vue |
| R1 — Q&A MVP | `POST/GET /questions`, detail, `POST/PATCH/DELETE /answers`, vote up/down/remove; scope theo org và ownership | Validation, tenant isolation, cursor, idempotency và best-answer tie-break test PASS | R0 |
| R2 — Source lifecycle | Admin-only taxonomy mutation và document publish/archive/restore metadata workflow; chưa upload binary | Chỉ content published/internal vào nguồn graph; audit + role test PASS | Identity RBAC decision, object storage chỉ khi upload |
| R3 — Event/provenance | Versioned outbox cho `knowledge-item` và Q&A publish/update/archive/delete; relay/consumer idempotent | Event replay không tạo bản sao; tombstone xóa projection tương ứng | R1, R2 |
| R4 — Graph deterministic | Ontology `v1`, Neo4j constraints, projection worker, API graph explorer qua Gateway | Query concept → source → author; provenance và tenant isolation PASS | R3, Neo4j runtime |
| R5 — AI-ready curation | Metadata ingestion, normalized text/chunk/checksum, proposal queue, withdraw/reindex | Không gọi provider; reviewer approve/reject và revoke/delete propagation PASS | R4 |
| R6 — Production AI | Provider, embeddings, hybrid search, expert finder/recommendation | Golden set, citation, authorization filter, cost/timeout/rollback PASS | Quyết định OQ-034/035/038/040 |

## Công việc bắt đầu ngay

1. Hoàn tất R0 bằng một browser E2E có account test cô lập; không dùng dữ liệu production.
2. Viết OpenAPI và Feature tests trước cho R1. Q&A MVP chỉ cho user đã xác thực trong cùng `organization_id`; question và answer là published ngay sau khi validate.
3. Chốt và thực thi rule best-answer: **derived-only** theo tổng vote giảm dần, rồi `created_at` tăng dần, rồi UUID tăng dần. Không expose API để manually accept answer. Các cột legacy `questions.accepted_answer_id`, `answers.is_accepted`, `accepted_at`, `accepted_by_user_id` không được đọc/ghi; chỉ xóa bằng migration sau khi kiểm kê xác nhận không có dữ liệu cần bảo toàn.
4. Sau R1, chuẩn hóa payload event R3 và chỉ then dựng graph. Không nối graph demo fixture vào UI như thể là dữ liệu thật.

## Contract tối thiểu R1

- `POST /api/v1/learning/questions`: `topic_id`, `category_id`, `title`, `body_markdown`; actor là author, category phải thuộc topic cùng tổ chức.
- `GET /api/v1/learning/questions`: cursor-based, filter `topic_id`, `category_id`, `q`; chỉ trả question chưa soft-delete của tổ chức caller.
- `GET /api/v1/learning/questions/{id}`: question, published answers và best-answer derived.
- `POST /api/v1/learning/questions/{id}/answers`; `PATCH`/`DELETE` answer chỉ owner được thao tác.
- `PUT /api/v1/learning/questions/{id}/votes` và `/answers/{id}/votes`: body `value` là `-1`, `0` hoặc `1`; unique vote per actor/target, request lặp phải idempotent.

Gateway là public boundary; Learning chỉ nhận gateway key cùng identity context đã được xác minh. Mọi response phải có scope tổ chức; write endpoint không tin `author_user_id`/`organization_id` từ body.

## Decision gate và giới hạn phạm vi

- Dùng mặc định local MVP đang nêu ở `00-ai-knowledge-foundation-plan.md`: taxonomy do admin quản lý, document `internal`, event khi publish/update/archive/delete, ontology `v1`, one reviewer cho curation.
- Upload file, quét malware, signed URL và fine-grained document ACL chờ OQ-030 và object storage.
- Neo4j chỉ mở ở R4 sau khi chốt OQ-036/037/039 trong ADR; không suy luận AI tự động.
- Provider/model/region, vector store, ranking và evaluation vẫn `BLOCKED` cho đến OQ-034/035/038/040. Không gửi nội dung nội bộ ra bên thứ ba.
- Course, reward/coin, leaderboard, recommendation và Expert Finder không thuộc R0–R5.

## Bằng chứng bắt buộc trước khi chuyển mốc

Mỗi mốc cần có: migration chạy lặp trên DB test cô lập; Feature/contract tests; Gateway integration; browser E2E cho UI có liên quan; test tenant isolation và authorization; test rerun/idempotency (từ R3); cập nhật OpenAPI, task tracker và hướng dẫn local. Không đánh dấu `DONE` dựa trên migration/schema đơn thuần.

## Thứ tự sau khi hoàn tất đợt này

Sau R0–R5 mới quay lại Social/Chat/Notification, rồi mobile/release theo `ROADMAP.md`. Các phần đó sẽ tích hợp vào contract Learning/graph đã ổn định, thay vì cùng lúc thay đổi data source và AI pipeline.
