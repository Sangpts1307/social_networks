# 10 — Quyết định database cần chốt trước khi triển khai

> **Trạng thái: REVIEW REQUIRED.** Các bảng trong bộ tài liệu đã được thiết kế để không bế tắc, nhưng các mục dưới đây ảnh hưởng trực tiếp schema, quyền truy cập, migration và chi phí vận hành. Không nên để AI tự suy đoán chúng khi bắt đầu code.

## 1. Quyết định bắt buộc

| ID | Quyết định | Các lựa chọn / khuyến nghị | Ảnh hưởng |
|---|---|---|---|
| DB-01 | Mô hình tenant | Khuyến nghị giữ `organization_id` từ đầu, MVP chạy một tổ chức | hầu hết bảng, unique key, authorization, search |
| DB-02 | Cây phòng ban | adjacency list `parent_id` (MVP) hay closure table/materialized path? Khuyến nghị adjacency list + sort order | `org_units`, import, quyền theo phòng ban |
| DB-03 | Danh tính đăng nhập | Phase hiện tại dùng employee code/email + password trong `users`. Nếu sau này thêm một external provider thì dùng các cột nullable trên `users`, không tạo bảng identity riêng. | `users`, `user_sessions` |
| DB-04 | Role nguồn chính | RBAC global, role scoped theo org unit/channel/topic hay kết hợp policy? | `roles`, assignment scope, admin UI matrix |
| DB-05 | Social channel permission | open, department, invited, private; có cross-department không? | `social_channels`, `channel_members`, post visibility |
| DB-06 | Quan hệ “follow” và “friend” | UI có follow; Chat UI nhắc contacts. Khuyến nghị follow một chiều trước, direct chat không cần friend relation | `user_follows`, contact picker, privacy |
| DB-07 | Comment model | chỉ 1 cấp reply hay tree vô hạn? Khuyến nghị tối đa 1 cấp ở MVP | `post_comments`, `qa_comments`, query/index |
| DB-08 | Reward owner | Khuyến nghị Learning sở hữu append-only ledger/wallet; Social chỉ emit qualifying event | wallet, leaderboard, refund/reversal |
| DB-09 | Coin có giá trị ngoài hệ thống? | Nếu đổi tiền/quà/vật phẩm, cần ledger, fraud, finance/legal review; nếu gamification chỉ cần points | payment-like requirements, audit |
| DB-10 | Document access | public nội bộ, department, named user, role, confidential; policy precedence? | `document_access_rules`, search/AI filtering |
| DB-11 | Tài liệu và file | size/type/quota, virus scan, preview, watermark, retention, version policy | object storage, `document_files`, operations |
| DB-12 | Chat retention & access | direct/group/project group, edit/delete, attachment, admin/legal access, retention | Mongo schema, backups, notification |
| DB-13 | AI source eligibility | Khuyến nghị index document/Q&A published được phép; **không index chat mặc định** | ingestion, graph, vector retention |
| DB-14 | AI vendor/model & region | UI là mockup, không phải quyết định kỹ thuật. Chọn provider/model/hosting sau privacy/cost assessment | model profile, egress, DPA, secrets |
| DB-15 | Semantic search stack | vector DB nào, lexical engine nào, Vietnamese evaluation corpus? | embedding refs, filters, operations |
| DB-16 | Graph stewardship | relation do AI đề xuất cần human review nào trước khi public? | Neo4j state, curation tables, audit |
| DB-17 | Audit/retention/legal hold | retention từng nhóm, export, purge approvals, who can inspect | all services, backups, delete workflow |
| DB-18 | Analytics | operational read model hay warehouse/BI riêng; KPI canonical definitions | events, snapshots, admin dashboard |

## 2. Những gì UI xác nhận, và những gì UI không xác nhận

UI mới tại `design/exports/html-tailwind` và `design/exports/admin` là bằng chứng tốt cho entities/field/flow: account provisioning/import, org unit/role, post/report, chat, Q&A/vote/bounty, document version/access, course/progress, leaderboard, hybrid search/graph, audit và system settings. Phạm vi được chủ dự án chốt đã loại cơ chế activation riêng và post voting khỏi database design.

Nhưng UI **không** xác nhận: DBMS/version, multi-tenancy, data volume, SLA/RPO/RTO, canonical permission policy, retention, identity provider, service boundary triển khai, message broker, object storage, search/vector provider, AI vendor, hay việc chat được phép cho AI đọc. Các mục này được đánh PROPOSED trong bộ tài liệu, không được diễn dịch thành “đã có trong code”.

## 3. Lộ trình chốt tối thiểu

### Bước A — chốt product và quyền (trước ERD vật lý)

Chốt DB-01 đến DB-12 cùng Product Owner, HR/Admin, Security. Kết quả phải là policy ngắn: ai thấy gì, ai thao tác gì, retention gì, trạng thái content/user nào.

### Bước B — chốt kiến trúc và dữ liệu AI

Chốt DB-13 đến DB-16 cùng Tech Lead/Security/Data owner. Lập assessment riêng cho nội dung gửi ra nhà cung cấp AI và khả năng xóa/tái lập index.

### Bước C — chuyển thành implementation artifacts

1. Chọn exact MySQL/MongoDB/Neo4j version, broker, object store, search/vector component.
2. Viết migration per service từ bảng design đã duyệt.
3. Viết OpenAPI/schema event cho các contract ở [07](07-cross-service-data-contracts.md).
4. Seed reference data, test fixture và test negative authorization.
5. Benchmark query/index ở [08](08-logical-erd-and-index-plan.md); chốt SLO/RPO/RTO ở [09](09-data-lifecycle-security-operations.md).

## 4. Phạm vi MVP khuyến nghị để tránh phình schema

Một MVP hợp lý có thể gồm: Identity + org unit/RBAC tối thiểu, channel/feed/post/comment/reaction/save, direct/group chat đơn giản, topic/Q&A/answer/vote/accepted answer, document/category/version/file, notification in-app, points ledger tối thiểu, audit admin core và index AI chỉ cho nội dung knowledge đã publish.

Để sau: hierarchy quyền phức tạp, multi-organization active, bounty đổi giá trị, comment tree vô hạn, course authoring đầy đủ, multi-provider AI, graph relation tự publish, import khối lượng lớn, warehouse/BI tách riêng. Đây là đề xuất giảm rủi ro, không phải cắt bỏ vĩnh viễn.

## 5. Tiêu chí “đủ để code migration”

Chỉ bắt đầu code bảng cụ thể khi đáp ứng tất cả:

- entity có owner service và bounded context;
- mỗi field có định nghĩa, nullability, format, classification và source of truth;
- relation có cardinality, delete/update behavior và index query-driven;
- status transition/unique invariant/idempotency đã rõ;
- quyền đọc/ghi và tenant scope có test cases;
- file, event, derived projection và lifecycle purge được chỉ rõ nếu entity có chúng;
- mọi DB-xx liên quan đã quyết định, hoặc feature bị scope khỏi release.

## 6. Form phản hồi nhanh để tinh chỉnh

Bạn có thể phản hồi theo format sau để tôi chỉnh bộ schema đồng nhất:

```text
DB-01: single tenant / multi tenant ngay từ đầu
DB-03: đăng nhập bằng employee code/email + password
DB-04: các role và phạm vi role là ...
DB-08: points/coin dùng để ...; có/không đổi quà/tiền
DB-10: tài liệu confidential được xem bởi ...
DB-12: chat giữ trong ...; admin có/không được xem ...
DB-13: AI được phép index: ...; không được index: ...
DB-17: yêu cầu retention/audit hiện biết: ...
```

Sau khi bạn chốt, bước hợp lý kế tiếp là sinh **ERD vật lý + migration plan + contract event/API + bộ test database** theo đúng quyết định, thay vì để AI tự chọn trong lúc code.
