# 09 — Vòng đời dữ liệu, bảo mật và vận hành database

> **Trạng thái: PROPOSED.** Đây là baseline để database không chỉ chạy được mà còn kiểm soát PII, quyền truy cập, xóa dữ liệu, backup và phục hồi được.

## 1. Phân loại dữ liệu

| Nhóm | Ví dụ | Nơi lưu chính | Yêu cầu tối thiểu |
|---|---|---|---|
| Public nội bộ | bài/kênh/tài liệu đã publish nội bộ | Social/Learning | tenant isolation, moderation và audit |
| Personal data | họ tên, email, điện thoại, ngày sinh, địa chỉ, avatar | Identity | least privilege, mã hóa khi phù hợp, log truy cập nhạy cảm |
| Credential/secret | password hash, session token hash, webhook/API secret | Identity/secret manager | tuyệt đối không plaintext; không log/event/analytics |
| Restricted knowledge | tài liệu confidential, draft, quyền truy cập | Learning + object storage | policy check server-side và audit access |
| Private communication | tin nhắn/direct conversation | Chat | mặc định không index AI, hạn chế admin/support access |
| AI-derived data | chunk, embedding reference, graph relation/proposal, prompt trace | AI/Knowledge | provenance, access scope, delete propagation |
| Operational/audit | login, import, moderation, admin action, delivery attempt | owner service/audit projection | append-only/restricted; retention được phê duyệt |

UI quản trị đề cập quét nội dung nhạy cảm và audit; các tính năng này không cho phép lưu hoặc phát tán dữ liệu nhạy cảm sang log/event.

## 2. Quy tắc bảo mật ở schema và application layer

- Tất cả bảng thuộc nghiệp vụ tổ chức chứa `organization_id`, trừ global catalog đã được phê duyệt. Query repository phải scope tenant trước khi tìm theo `id`.
- Mọi nội dung có access rule có `status`, `visibility`/`confidentiality` và policy kiểm tra tại server. Frontend không phải security boundary.
- Password dùng Argon2id hoặc bcrypt theo cấu hình security đã chốt; chỉ lưu hash và metadata cần thiết trực tiếp trên `users`. Phase hiện tại không có bảng activation riêng.
- Refresh/session token lưu hash hoặc opaque token reference; revoke theo session/device. Không lưu access token JWT thô nếu không bắt buộc.
- Kết nối DB dùng TLS; backup/object storage mã hóa at rest; secret DB nằm trong secret manager, không trong repository/migration/UI config.
- Trường PII mật độ cao cần xét application-level encryption/search tokenization tùy yêu cầu truy vấn. Không tự mã hóa mọi cột rồi làm mất khả năng vận hành/khôi phục mà không có threat model.
- `metadata_json` chỉ chứa metadata có allow-list. Không dùng JSON để lách classification/schema/permission.
- Audit log chứa ID/mã hành động và payload redacted. Không ghi password, token, nội dung tài liệu đầy đủ hay raw chat.

## 3. Vòng đời và trạng thái xóa

### 3.1. Soft delete có kiểm soát

Áp dụng soft delete (`deleted_at`, `deleted_by`, `delete_reason`) cho resource người dùng có khả năng restore: post, comment, answer, document và account profile theo chính sách. Query mặc định phải loại bản ghi `deleted_at IS NOT NULL` bằng repository scope, không dựa vào việc developer nhớ thêm điều kiện.

Không dùng soft delete như retention vô hạn. Sau thời gian retention/phê duyệt, purge job phải:

1. lập danh sách exact IDs và lý do;
2. xóa/ẩn file binary trên object storage;
3. phát tombstone event để xóa projection, vector và graph;
4. lưu audit record không mang nội dung gốc;
5. có metric số record thành công/thất bại và reconciliation.

### 3.2. Version và bất biến

- `document_versions`, `reward_ledger_entries`, `audit_events`, `outbox_events` là append-oriented: không sửa lịch sử tùy tiện. Sửa bằng event/version/reversal có lý do.
- Tài liệu bị thu hồi vẫn giữ version metadata cần audit, nhưng content/file/read permission theo policy.
- Counters, ranking và AI index là dữ liệu dẫn xuất; có thể rebuild và không dùng làm bằng chứng duy nhất.

### 3.3. Deactivate user khác delete user

Khóa/nghỉ việc là `deactivated`, không delete cascade nội dung. Các reference `author_user_id` giữ nguyên để audit. Việc xóa/ẩn định danh cần một policy HR/pháp lý riêng và phải lan sang avatar, search projection, AI graph, notification preference và file riêng tư nếu có.

## 4. Retention cần được phê duyệt

Không tự bịa số năm/tháng retention. Bảng dưới là nơi cần chủ hệ thống/HR/Legal/Security quyết định rồi đưa vào `organization_settings` hoặc policy configuration có version.

| Nhóm dữ liệu | Chủ sở hữu duyệt | Câu hỏi phải chốt |
|---|---|---|
| audit/login/security events | Security/Legal | giữ bao lâu, ai được xem, có export evidence không? |
| chat/message/attachment | Business + Legal | có backup/archive, ai được xóa, eDiscovery không? |
| document version và file | Knowledge owner | giữ draft/obsolete bao lâu, legal hold? |
| notification delivery | Product/Ops | cần debug delivery bao lâu, có xóa body/excerpt sớm không? |
| AI prompts/chunks/embeddings | Security + Data owner | có thể chứa PII/knowledge nào, cách delete downstream? |
| import row/error file | HR/Admin | xóa raw import thế nào sau khi xử lý? |
| analytics/audit projection | Data governance | aggregate có được giữ lâu hơn raw event không? |

## 5. Backup, restore và disaster recovery

Mục tiêu RPO/RTO chưa được nguồn hiện có chốt; cần ghi thành SLO per tier trước deploy. Baseline đề xuất:

1. backup tự động, mã hóa, tách account/region hoặc fault domain phù hợp; kiểm tra retention backup;
2. point-in-time recovery cho MySQL nếu workload yêu cầu; snapshot/backup consistency cho MongoDB và Neo4j;
3. object storage versioning/lifecycle tương thích với document version and purge policy;
4. restore drill định kỳ vào môi trường cô lập: khôi phục DB, file object và index/graph từ source; đo RPO/RTO thực;
5. backup không phải cách né purge policy: dữ liệu trong backup hết retention cũng phải được quản trị.

Recovery order gợi ý: Identity/organization metadata → source databases Social/Learning/Chat → object files → Notification state → rebuild projections/search/vector/Neo4j from events/source snapshots. Không coi vector/graph là bản sao duy nhất của document/Q&A.

## 6. Quyền vận hành và audit

| Vai trò vận hành | Quyền DB nên có | Không nên có mặc định |
|---|---|---|
| App runtime | CRUD giới hạn schema/service, không DDL | superuser, cross-service DB access |
| Migration runner | DDL theo pipeline, giới hạn thời gian | credential production dùng từ máy cá nhân |
| Read-only support | read model/PII đã mask theo ticket | đọc password/session/raw chat tùy ý |
| DBA/SRE | backup/restore/monitoring theo break-glass | sửa record nghiệp vụ không audit |
| Security auditor | audit/security logs có redaction | modify data, secret access |

Admin UI có thể quản lý user, role, content và audit; điều đó không đồng nghĩa mọi Admin role được truy cập database trực tiếp. Thao tác sensitive (export data, change role, unblock user, purge, promote document) phải tạo audit event gồm actor, target, before/after redacted, reason, request/trace ID và outcome.

## 7. Migration, seed và rollback

- Migration phải idempotent ở mức pipeline, có tên/thứ tự không đổi, reviewed và test trên bản sao/ephemeral DB.
- Dùng expand/contract: thêm cột nullable/index → deploy code tương thích hai schema → backfill theo batch → enforce constraint → bỏ cột cũ ở release sau. Không rename/drop trực tiếp trên bảng lớn.
- Tách data migration khỏi schema migration khi nặng; có checkpoint, rate limit, metric và resume.
- `down` migration không phải luôn an toàn sau khi data shape đã đổi. Kế hoạch rollback application và forward-fix phải ghi rõ.
- Seed chỉ gồm lookup/demo an toàn, không chứa credential thật, PII thật hay binary copyrighted.

## 8. Monitoring database và data quality

Theo dõi tối thiểu: connection pool, query latency/error, slow query, lock/deadlock, CPU/I/O/storage, replication/PITR health, backup success/failure, Mongo document/index size, Neo4j query/memory, broker outbox backlog, consumer lag, failed purge/index delete.

Data quality jobs cần kiểm tra: orphan external references sau event lag, unique invariant violations, `wallet balance` so với ledger sum, post/Q&A counters so với aggregate thật, document current version/file, search/vector/graph version so với source version, tombstone đã propagate, tenant scope anomalies.

## 9. Checklist release database

- [ ] Schema owner, data classification và retention đã ghi cho từng bảng mới.
- [ ] Mọi user-visible/business query đã có index được chứng minh bằng explain/benchmark.
- [ ] Unique constraints bảo vệ các thao tác idempotent/race-prone.
- [ ] Tenant/access filter có test negative cross-organization.
- [ ] Migration/backfill/rollback và backup restore impact đã review.
- [ ] Event payload không có secret/PII quá mức; có consumer idempotency.
- [ ] File/object, search/vector/graph có lifecycle xóa đồng bộ với source.
- [ ] Audit/metrics/alert phù hợp cho thao tác quản trị và pipeline nền.

Xem [00 — Tổng quan](00-database-overview.md), [07 — Hợp đồng liên service](07-cross-service-data-contracts.md) và [10 — Quyết định mở](10-open-decisions.md).
