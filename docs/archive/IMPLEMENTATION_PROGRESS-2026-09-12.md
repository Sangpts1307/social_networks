> BẢN LƯU LỊCH SỬ — không cập nhật trạng thái ở đây. Dùng [phase tracker](../specs/README.md).

# IMPLEMENTATION PROGRESS — Social & Learning Platform

> **Trạng thái dự án:** `PLANNED — chưa có source code hoặc migration`  
> **Baseline bắt buộc:** [IMPLEMENTATION_BASELINE.md](../IMPLEMENTATION_BASELINE.md)  
> **Cập nhật gần nhất:** 2026-09-12  
> **Mục tiêu file:** backlog và bằng chứng tiến độ có thể đọc từng phần. Đây **không** thay thế spec chi tiết/database design/API contract.

## 0. Quy ước dùng cho người và AI

### Status chuẩn

| Status | Nghĩa | Khi nào dùng |
|---|---|---|
| `NOT_STARTED` | Chưa bắt đầu | Mặc định cho work item mới |
| `READY` | Đủ quyết định để bắt đầu | Có spec/phụ thuộc cần thiết |
| `IN_PROGRESS` | Đang có người/AI làm | Chỉ tối đa vài task thật sự active |
| `BLOCKED` | Không thể tiếp tục | Ghi blocker cụ thể và owner giải quyết |
| `DONE` | Đã hoàn thành và có evidence | Có code/migration/test/review phù hợp |
| `DEFERRED` | Chủ động hoãn | Ghi lý do và điều kiện mở lại |
| `CANCELLED` | Loại khỏi dự án | Không xóa lịch sử task |

### Cách AI phải dùng file này

1. Đọc **Baseline** trước, rồi chỉ đọc section của service/module đang được giao.
2. Chọn một work item `READY`, đổi sang `IN_PROGRESS` trước khi sửa code.
3. Không tự làm task `DEFERRED`, `BLOCKED` hoặc task có dependency chưa `DONE`.
4. Khi xong, đổi sang `DONE` và cập nhật: file đã sửa, migration/API, lệnh test, kết quả test, thay đổi database/event nếu có.
5. Ý tưởng mới không chèn mơ hồ vào task đang làm: thêm ở **Idea Inbox**, sau đó tách thành work item có ID khi được duyệt.
6. Không đánh dấu `DONE` chỉ vì đã viết code; test/verification là bằng chứng bắt buộc.

### Mẫu cập nhật một work item

```md
| ID | Work item | Status | Depends on | Done evidence |
|---|---|---|---|---|
| ID-API-003 | Login email/password | DONE | ID-DB-003, ID-DB-006 | `services/identity/...`; `POST /auth/login`; `php artisan test --filter=LoginTest` PASS (2026-..); no known issue |
```

Nếu có thay đổi quyết định nghiệp vụ/database/API, ghi thêm một dòng tại **Decision & Change Log** và cập nhật [IMPLEMENTATION_BASELINE.md](../IMPLEMENTATION_BASELINE.md) trước hoặc cùng change set.

---

## 1. Dashboard tiến độ

| Phase | Mục tiêu | Status | Tiêu chí kết thúc |
|---|---|---|---|
| P0 — Foundation & Identity | Web shell, Laravel foundation, Identity/RBAC/Admin và demo Cây tri thức | `READY` | Đăng nhập/role/department/user admin hoạt động; demo graph user-document-concept chạy local; test & Docker guide có evidence |
| P1 — Social | Inside: channel, post, event, interaction, moderation | `NOT_STARTED` | Luồng Social được chốt và tested end-to-end |
| P2 — Learning | Q&A, document library, vote/best answer, notification integration | `NOT_STARTED` | Luồng hỏi → trả lời → vote → document publish chạy end-to-end |
| P3 — Chat & Notification | Direct/group chat, WebSocket, in-app/push | `NOT_STARTED` | Message realtime/read state/push hoạt động và retry an toàn |
| P4 — AI Production | Ingestion/search/embedding provider thật | `DEFERRED` | Chỉ mở khi provider, budget, region và data policy được duyệt |
| P5 — Android | Ứng dụng mobile | `DEFERRED` | Chỉ mở sau Web API ổn định và stack mobile được chọn |

### Current focus

`P0` là phase duy nhất được phép bắt đầu hiện tại. Thứ tự ưu tiên: Foundation → Identity/RBAC/Admin → Demo Cây tri thức → E2E verification.

---

## 2. Cross-project foundation / Gateway / DevOps

**Spec liên quan:** [Baseline](../IMPLEMENTATION_BASELINE.md), [Target Architecture](../specs/15-target-architecture.md), [Deployment Spec](../specs/12-deployment-spec.md), [Testing Spec](../specs/11-testing-spec.md).

| ID | Work item nhỏ | Status | Depends on | Done evidence cần có |
|---|---|---|---|---|
| PLAT-001 | Kiểm kê workspace hiện tại, xác nhận không ghi đè tài sản người dùng | `READY` | — | Inventory + quyết định thư mục source được ghi log |
| PLAT-002 | Tạo cấu trúc source Laravel/Vue theo service boundary đã chốt | `NOT_STARTED` | PLAT-001 | Tree source + README run guide |
| PLAT-003 | Chuẩn hóa `.env.example` và quy tắc không commit secret | `NOT_STARTED` | PLAT-002 | Secret scan/manual review PASS |
| PLAT-004 | Docker Compose local: MySQL, MongoDB, Redis, MinIO | `NOT_STARTED` | PLAT-002 | `docker compose up` health checks PASS |
| PLAT-005 | Thêm Neo4j local profile dành riêng cho graph demo | `NOT_STARTED` | PLAT-004 | Neo4j health check + connection test PASS |
| PLAT-006 | Thiết lập DB migration runner riêng theo từng service | `NOT_STARTED` | PLAT-004 | Không migration cross-service; fresh DB migrate PASS |
| PLAT-007 | Chuẩn hóa UUID, UTC time, error response, pagination cursor | `NOT_STARTED` | PLAT-002 | Shared conventions test/documented |
| PLAT-008 | Gateway/BFF skeleton và route health check | `NOT_STARTED` | PLAT-002 | Gateway health + one protected proxy route test PASS |
| PLAT-009 | Chuẩn hóa REST API versioning và OpenAPI location | `NOT_STARTED` | PLAT-008 | API contract validation PASS |
| PLAT-010 | Transactional outbox/inbox abstraction cho service MySQL | `NOT_STARTED` | PLAT-006 | Idempotency integration test PASS |
| PLAT-011 | Redis connection/cache/rate-limit abstraction | `NOT_STARTED` | PLAT-004 | Local connection + rate-limit test PASS |
| PLAT-012 | MinIO private object upload/download abstraction | `NOT_STARTED` | PLAT-004 | Authorization + signed URL test PASS |
| PLAT-013 | Correlation ID, structured log và error tracking interface | `NOT_STARTED` | PLAT-002 | Correlation appears in API log/test |
| PLAT-014 | CI baseline: lint, unit test, migration test | `NOT_STARTED` | PLAT-002, PLAT-006 | CI workflow green on clean checkout |
| PLAT-015 | Local run guide và bootstrap seed guide | `NOT_STARTED` | PLAT-004, PLAT-014 | Người khác chạy được từ clean clone |

---

## 3. Identity Service — `identity_db` / Laravel / MySQL

**Spec liên quan:** [Identity database](../database/01-identity-service-mysql.md), [Security](../specs/10-security-spec.md), [Baseline §3](../IMPLEMENTATION_BASELINE.md#3-identity-account-và-quyền).

### 3.1. Schema và data access

| ID | Work item nhỏ | Status | Depends on | Done evidence cần có |
|---|---|---|---|---|
| ID-DB-001 | Migration `organizations` | `NOT_STARTED` | PLAT-006 | Fresh migration + unique domain test |
| ID-DB-002 | Migration `organization_email_domains` | `NOT_STARTED` | ID-DB-001 | FK/unique domain tests |
| ID-DB-003 | Migration `users` gồm email login, password hash, failed count, lock state | `NOT_STARTED` | ID-DB-001 | Unique email + no plaintext password test |
| ID-DB-004 | Migration `user_profiles` với PII/ avatar metadata | `NOT_STARTED` | ID-DB-003 | One-profile-per-user test |
| ID-DB-005 | Migration `org_units` tree | `NOT_STARTED` | ID-DB-001 | Parent/cycle validation test |
| ID-DB-006 | Migration `org_unit_memberships` và primary membership rule | `NOT_STARTED` | ID-DB-003, ID-DB-005 | Membership invariant test |
| ID-DB-007 | Migration RBAC: roles, permissions, role_permissions, assignments | `NOT_STARTED` | ID-DB-003 | Unique role/permission assignment test |
| ID-DB-008 | Migration `user_sessions` và `user_devices` | `NOT_STARTED` | ID-DB-003 | Token hash only + revoke test |
| ID-DB-009 | Migration user import jobs/rows | `NOT_STARTED` | ID-DB-003 | Import-row validation test |
| ID-DB-010 | Migration audit projection/outbox/inbox tables | `NOT_STARTED` | ID-DB-003, PLAT-010 | Event idempotency test |
| ID-DB-011 | Seed system roles and permissions | `NOT_STARTED` | ID-DB-007 | Idempotent seed test |

### 3.2. Domain functions và API

| ID | Work item nhỏ | Status | Depends on | Done evidence cần có |
|---|---|---|---|---|
| ID-AUTH-001 | Validate email/password request payload | `NOT_STARTED` | ID-DB-003 | Request validation unit tests |
| ID-AUTH-002 | Authenticate active account bằng email/password | `NOT_STARTED` | ID-AUTH-001, ID-DB-008 | Success/failure integration tests |
| ID-AUTH-003 | Tăng failed-login count cho login sai | `NOT_STARTED` | ID-AUTH-002 | Counter update test |
| ID-AUTH-004 | Khóa account ở lần sai thứ 10 | `NOT_STARTED` | ID-AUTH-003 | 10-attempt integration test |
| ID-AUTH-005 | Chặn login khi account locked/suspended/terminated | `NOT_STARTED` | ID-AUTH-004 | Negative authorization tests |
| ID-AUTH-006 | Admin unlock account và reset failed counter | `NOT_STARTED` | ID-RBAC-002 | Audit + authorization test |
| ID-AUTH-007 | Cấp session/token sau login thành công | `NOT_STARTED` | ID-AUTH-002, ID-DB-008 | Token hash/session test |
| ID-AUTH-008 | Logout/revoke một session | `NOT_STARTED` | ID-AUTH-007 | Revoked token negative test |
| ID-AUTH-009 | Logout all sessions của current user | `NOT_STARTED` | ID-AUTH-007 | Multi-session revoke test |
| ID-AUTH-010 | Force change password ở login đầu tiên | `NOT_STARTED` | ID-AUTH-007 | Protected-route denial/change flow test |
| ID-AUTH-011 | Change password của current user và invalidate prior sessions | `NOT_STARTED` | ID-AUTH-010 | Password-version/session invalidation test |
| ID-ORG-001 | Create organization (bootstrap only) | `NOT_STARTED` | ID-DB-001 | Authorization + unique test |
| ID-ORG-002 | Create org unit | `NOT_STARTED` | ID-DB-005 | Parent/permission test |
| ID-ORG-003 | Update org unit | `NOT_STARTED` | ID-ORG-002 | Audit test |
| ID-ORG-004 | Move org unit within tree without cycle | `NOT_STARTED` | ID-ORG-002 | Cycle rejection test |
| ID-ORG-005 | Archive org unit only after member handling | `NOT_STARTED` | ID-ORG-002 | Negative archive test |
| ID-USER-001 | Admin create employee account | `NOT_STARTED` | ID-DB-003, ID-DB-004, ID-DB-006 | Account/provision/audit integration test |
| ID-USER-002 | Admin import employees from approved CSV/XLSX format | `NOT_STARTED` | ID-DB-009, ID-USER-001 | Mixed valid/invalid row test |
| ID-USER-003 | Assign/replace primary org unit | `NOT_STARTED` | ID-DB-006, ID-USER-001 | Exactly-one-primary test |
| ID-USER-004 | Add/remove secondary team membership | `NOT_STARTED` | ID-USER-003 | Membership history test |
| ID-USER-005 | Suspend/terminate account without deleting records | `NOT_STARTED` | ID-USER-001 | Existing content reference remains test |
| ID-PROFILE-001 | Get current user profile | `NOT_STARTED` | ID-AUTH-007, ID-DB-004 | Owner-only PII test |
| ID-PROFILE-002 | Update allowed current user profile fields | `NOT_STARTED` | ID-PROFILE-001 | Field allow-list test |
| ID-PROFILE-003 | Upload/replace avatar through private object storage | `NOT_STARTED` | ID-PROFILE-002, PLAT-012 | MIME/size/access test |
| ID-PROFILE-004 | Public/internal profile projection excludes sensitive PII | `NOT_STARTED` | ID-PROFILE-001 | Phone/birth/address negative test |
| ID-RBAC-001 | Permission middleware/guard | `NOT_STARTED` | ID-DB-007, ID-AUTH-007 | Deny/allow tests |
| ID-RBAC-002 | Super Admin all-permission resolution | `NOT_STARTED` | ID-RBAC-001 | Permission matrix test |
| ID-RBAC-003 | HR Manager personnel-only permission resolution | `NOT_STARTED` | ID-RBAC-001 | Cannot moderate/social-admin test |
| ID-RBAC-004 | Assign/revoke user role | `NOT_STARTED` | ID-RBAC-001, ID-USER-001 | Audit + effective-date test |
| ID-AUDIT-001 | Emit audit event for sensitive identity actions | `NOT_STARTED` | ID-DB-010 | Redacted audit payload test |
| ID-AUDIT-002 | Super Admin audit log list/filter | `NOT_STARTED` | ID-AUDIT-001, ID-RBAC-002 | Non-super-admin denial test |
| ID-API-001 | Publish OpenAPI auth endpoints | `NOT_STARTED` | ID-AUTH-011 | Contract validation |
| ID-API-002 | Publish OpenAPI admin user endpoints | `NOT_STARTED` | ID-USER-005 | Contract validation |
| ID-API-003 | Publish OpenAPI org/RBAC endpoints | `NOT_STARTED` | ID-RBAC-004 | Contract validation |

### 3.3. Identity Web UI

| ID | Work item nhỏ | Status | Depends on | Done evidence cần có |
|---|---|---|---|---|
| WEB-ID-001 | Vue login page/API state/error UI | `NOT_STARTED` | ID-AUTH-007 | E2E success/lock error test |
| WEB-ID-002 | Force-change-password page | `NOT_STARTED` | ID-AUTH-010 | E2E test |
| WEB-ID-003 | Current profile page and allowed edit form | `NOT_STARTED` | ID-PROFILE-002 | E2E + PII visibility test |
| WEB-ADM-001 | Admin user list/filter/pagination | `NOT_STARTED` | ID-API-002, ID-RBAC-002 | E2E role denial test |
| WEB-ADM-002 | Admin create user form | `NOT_STARTED` | WEB-ADM-001, ID-USER-001 | E2E create test |
| WEB-ADM-003 | Admin import user screen/result rows | `NOT_STARTED` | WEB-ADM-001, ID-USER-002 | E2E mixed-file test |
| WEB-ADM-004 | Admin lock/unlock/suspend action UI | `NOT_STARTED` | WEB-ADM-001, ID-AUTH-006, ID-USER-005 | E2E/audit test |
| WEB-ADM-005 | Department tree screen | `NOT_STARTED` | ID-ORG-004 | E2E tree mutation test |
| WEB-ADM-006 | Role assignment screen | `NOT_STARTED` | ID-RBAC-004 | E2E authorization test |
| WEB-ADM-007 | Audit log screen, Super Admin only | `NOT_STARTED` | ID-AUDIT-002 | E2E denial test |

---

## 4. Learning Service — Cây tri thức demo và future Base Square

**Spec liên quan:** [Learning database](../database/03-learning-service-mysql.md), [AI/Knowledge design](../database/06-ai-knowledge-mysql-neo4j.md), [Baseline §5](../IMPLEMENTATION_BASELINE.md#5-learning--base-square--phạm-vi-khi-mở-module).

### 4.1. P0 — knowledge data needed for graph demo

| ID | Work item nhỏ | Status | Depends on | Done evidence cần có |
|---|---|---|---|---|
| LEARN-DB-001 | Migration `knowledge_topics` | `NOT_STARTED` | PLAT-006 | CRUD migration test |
| LEARN-DB-002 | Migration `knowledge_categories` tree | `NOT_STARTED` | LEARN-DB-001 | Topic/category ownership test |
| LEARN-DB-003 | Migration `knowledge_tags` | `NOT_STARTED` | PLAT-006 | Unique normalized-name test |
| LEARN-DB-004 | Migration minimal `documents` metadata | `NOT_STARTED` | LEARN-DB-001, ID-DB-003 | Published-document scope test |
| LEARN-DB-005 | Migration `document_versions` and `document_files` metadata | `NOT_STARTED` | LEARN-DB-004, PLAT-012 | Immutable-version test |
| LEARN-DB-006 | Seed demo topics/categories/documents with no production PII | `NOT_STARTED` | LEARN-DB-005 | Seed repeatability test |
| LEARN-API-001 | List topic/category tree endpoint | `NOT_STARTED` | LEARN-DB-002 | Contract + tenant scope test |
| LEARN-API-002 | Read published document metadata endpoint | `NOT_STARTED` | LEARN-DB-005 | Authenticated-only test |
| LEARN-WEB-001 | Web knowledge-topic tree view | `NOT_STARTED` | LEARN-API-001 | Browser E2E test |
| LEARN-WEB-002 | Web document detail preview metadata view | `NOT_STARTED` | LEARN-API-002 | Browser E2E test |

### 4.2. P2 — Q&A and document library (not start in P0)

| ID | Work item nhỏ | Status | Depends on | Done evidence cần có |
|---|---|---|---|---|
| LEARN-QA-001 | Migration `questions` without manually accepted-answer field | `DEFERRED` | LEARN-DB-002 | Best answer stays derived |
| LEARN-QA-002 | Migration `answers` | `DEFERRED` | LEARN-QA-001 | Question ownership FK test |
| LEARN-QA-003 | Migration `qa_votes` supporting +1/-1 | `DEFERRED` | LEARN-QA-002 | One vote per user/target test |
| LEARN-QA-004 | Migration Q&A comments | `DEFERRED` | LEARN-QA-002 | Parent target validation test |
| LEARN-QA-005 | Create published non-anonymous question | `DEFERRED` | LEARN-QA-001 | Auth/validation test |
| LEARN-QA-006 | List/filter/paginate questions | `DEFERRED` | LEARN-QA-005 | Cursor test |
| LEARN-QA-007 | Add/edit/soft-delete own answer | `DEFERRED` | LEARN-QA-002 | Ownership test |
| LEARN-QA-008 | Cast/change/remove Q&A vote | `DEFERRED` | LEARN-QA-003 | Idempotency/race test |
| LEARN-QA-009 | Calculate best answer from score, then created time, then UUID | `DEFERRED` | LEARN-QA-007, LEARN-QA-008 | Tie-break test |
| LEARN-QA-010 | Add Q&A comment | `DEFERRED` | LEARN-QA-004 | Authorization test |
| LEARN-DOC-001 | Upload by allowed uploader roles only | `DEFERRED` | LEARN-DB-005, PLAT-012 | Role/MIME/scan test |
| LEARN-DOC-002 | Create immutable document version | `DEFERRED` | LEARN-DOC-001 | Version sequencing test |
| LEARN-DOC-003 | Super Admin approve/publish document | `DEFERRED` | LEARN-DOC-002, ID-RBAC-002 | Role/audit test |
| LEARN-DOC-004 | All authenticated users view published document | `DEFERRED` | LEARN-DOC-003 | Authenticated vs anonymous test |
| LEARN-DOC-005 | Soft-delete/restore document by Super Admin | `DEFERRED` | LEARN-DOC-003 | Restore/audit test |
| LEARN-REWARD-001 | Define monetary coin/bounty policy | `BLOCKED` | Business rule from owner | Approved policy required; no implementation before this |
| LEARN-REWARD-002 | Point leaderboard | `DEFERRED` | LEARN-QA-008 | Reward rules approved |
| LEARN-REWARD-003 | Coin ledger/bounty hold/settlement | `BLOCKED` | LEARN-REWARD-001 | Finance/anti-fraud/reversal tests |
| LEARN-COURSE-001 | Course/lesson authoring | `DEFERRED` | Product flow approval | Separate spec required |

---

## 5. AI Knowledge Service — Neo4j demo then production AI

**Spec liên quan:** [AI/Knowledge database](../database/06-ai-knowledge-mysql-neo4j.md), [AI Knowledge Spec](../specs/08-ai-knowledge-spec.md), [Baseline §7](../IMPLEMENTATION_BASELINE.md#7-ai-search-và-cây-tri-thức).

### 5.1. P0 — deterministic knowledge graph demo

| ID | Work item nhỏ | Status | Depends on | Done evidence cần có |
|---|---|---|---|---|
| AI-GRAPH-001 | Neo4j connection/config health check | `NOT_STARTED` | PLAT-005 | Connection integration test |
| AI-GRAPH-002 | Define graph constraints for `User`, `Document`, `Concept`, `Organization` | `NOT_STARTED` | AI-GRAPH-001 | Constraint migration/re-run test |
| AI-GRAPH-003 | Define allowed demo relations `AUTHORED`, `HAS_TAG`, `EXPERTISE_IN`, `DERIVED_FROM` | `NOT_STARTED` | AI-GRAPH-002 | Invalid relation rejected/tested |
| AI-GRAPH-004 | Project seeded Identity/Learning demo data to Neo4j | `NOT_STARTED` | ID-DB-003, LEARN-DB-006, AI-GRAPH-003 | Repeatable idempotent projection test |
| AI-GRAPH-005 | Store provenance/source IDs for every graph relation | `NOT_STARTED` | AI-GRAPH-004 | Source trace query test |
| AI-GRAPH-006 | Graph query: traverse concept → document → author/expert | `NOT_STARTED` | AI-GRAPH-004 | Cypher/API test |
| AI-GRAPH-007 | REST endpoint for graph explorer payload | `NOT_STARTED` | AI-GRAPH-006, PLAT-009 | Contract/auth test |
| AI-GRAPH-008 | Vue knowledge graph explorer screen | `NOT_STARTED` | AI-GRAPH-007 | Browser E2E demo test |
| AI-GRAPH-009 | Label demo as deterministic graph, not AI inference | `NOT_STARTED` | AI-GRAPH-008 | UI/README verification |

### 5.2. P4 — production AI/search (explicitly blocked/deferred)

| ID | Work item nhỏ | Status | Depends on | Done evidence cần có |
|---|---|---|---|---|
| AI-PROD-001 | Chọn AI provider/model/region/budget | `BLOCKED` | Owner decision | Written approval/data policy |
| AI-PROD-002 | Approve source-content egress policy | `BLOCKED` | AI-PROD-001, security approval | Approved data classification policy |
| AI-PROD-003 | Build ingest job for published Q&A/document only | `DEFERRED` | AI-PROD-002, LEARN-QA-005, LEARN-DOC-003 | Permission-aware ingest test |
| AI-PROD-004 | Chunk content with provenance/version | `DEFERRED` | AI-PROD-003 | Reingest/idempotency test |
| AI-PROD-005 | Generate/store embeddings with deletion propagation | `DEFERRED` | AI-PROD-004 | Source withdraw cleanup test |
| AI-PROD-006 | Hybrid lexical/vector/graph search | `DEFERRED` | AI-PROD-005 | Authorization + relevance test |
| AI-PROD-007 | Risk assessment as moderation proposal only | `DEFERRED` | AI-PROD-002 | No-auto-delete negative test |

---

## 6. Social Service — `social_db` / Laravel / MySQL (P1)

**Spec liên quan:** [Social database](../database/02-social-service-mysql.md), [Baseline §4](../IMPLEMENTATION_BASELINE.md#4-social--inside--phạm-vi-khi-mở-module).

| ID | Work item nhỏ | Status | Depends on | Done evidence cần có |
|---|---|---|---|---|
| SOC-DB-001 | Migration channels and channel memberships | `DEFERRED` | ID-DB-003, ID-DB-006 | Membership unique test |
| SOC-DB-002 | Migration posts and post stats | `DEFERRED` | SOC-DB-001 | Feed cursor index test |
| SOC-DB-003 | Migration post attachment metadata | `DEFERRED` | SOC-DB-002, PLAT-012 | Object key/scan test |
| SOC-DB-004 | Migration post event metadata and `event_rsvps` | `DEFERRED` | SOC-DB-002 | One RSVP/user/event test |
| SOC-DB-005 | Migration comments with one-level reply policy | `DEFERRED` | SOC-DB-002 | Parent-depth validation test |
| SOC-DB-006 | Migration reactions, saves and follows | `DEFERRED` | SOC-DB-002 | Unique interaction tests |
| SOC-DB-007 | Migration social tags | `DEFERRED` | SOC-DB-002 | Unique normalized-tag test |
| SOC-DB-008 | Migration reports, moderation action and outbox | `DEFERRED` | SOC-DB-002, PLAT-010 | Audit/idempotency test |
| SOC-API-001 | Create/update/publish normal text post | `DEFERRED` | SOC-DB-002 | Ownership/visibility test |
| SOC-API-002 | Upload and attach scanned image/file | `DEFERRED` | SOC-DB-003 | Unsafe file denial test |
| SOC-API-003 | List feed with cursor pagination | `DEFERRED` | SOC-DB-002 | Tenant/channel access test |
| SOC-API-004 | Create event and set RSVP | `DEFERRED` | SOC-DB-004 | RSVP/auth test |
| SOC-API-005 | Add comment and one-level reply | `DEFERRED` | SOC-DB-005 | Depth/ownership test |
| SOC-API-006 | Set/change/remove reaction | `DEFERRED` | SOC-DB-006 | One-reaction-per-user test |
| SOC-API-007 | Save/unsave post | `DEFERRED` | SOC-DB-006 | Private-save test |
| SOC-API-008 | Follow/unfollow user | `DEFERRED` | SOC-DB-006 | Self-follow rejection test |
| SOC-API-009 | Report content | `DEFERRED` | SOC-DB-008 | Report/audit test |
| SOC-API-010 | Moderation hide/remove/restore content | `DEFERRED` | SOC-DB-008, ID-RBAC-001 | Permission/audit test |
| SOC-API-011 | Publish news/announcement with permission guard | `DEFERRED` | SOC-API-001, ID-RBAC-001 | Member denial test |
| SOC-WEB-001 | Feed/channel navigation UI | `DEFERRED` | SOC-API-003 | Browser E2E test |
| SOC-WEB-002 | Post composer and attachment UI | `DEFERRED` | SOC-API-002 | Browser E2E test |
| SOC-WEB-003 | Post detail/interactions/comment UI | `DEFERRED` | SOC-API-005, SOC-API-006 | Browser E2E test |
| SOC-WEB-004 | Event RSVP UI | `DEFERRED` | SOC-API-004 | Browser E2E test |
| SOC-ADM-001 | Moderation queue UI | `DEFERRED` | SOC-API-009, SOC-API-010 | Role denial E2E test |

---

## 7. Chat Service — `chat_db` / Laravel / MongoDB (P3)

**Spec liên quan:** [Chat database](../database/04-chat-service-mongodb.md), [Realtime/eventing](../specs/09-realtime-eventing.md), [Baseline §6](../IMPLEMENTATION_BASELINE.md#6-chat-và-notification--phạm-vi-khi-mở-module).

| ID | Work item nhỏ | Status | Depends on | Done evidence cần có |
|---|---|---|---|---|
| CHAT-DB-001 | Mongo connection and collection indexes | `DEFERRED` | PLAT-004 | Index inspection test |
| CHAT-DB-002 | Conversation and member document schema | `DEFERRED` | CHAT-DB-001, ID-DB-003 | Direct-conversation uniqueness test |
| CHAT-DB-003 | Message document schema/cursor index | `DEFERRED` | CHAT-DB-002 | Seek-pagination test |
| CHAT-DB-004 | Read/unread projection schema | `DEFERRED` | CHAT-DB-003 | Concurrent read-state test |
| CHAT-API-001 | Create/reuse direct conversation | `DEFERRED` | CHAT-DB-002 | Same pair returns same conversation test |
| CHAT-API-002 | Create group conversation | `DEFERRED` | CHAT-DB-002 | Member validation test |
| CHAT-API-003 | Add/remove group members by authorized actor | `DEFERRED` | CHAT-API-002 | Permission test |
| CHAT-API-004 | Persist text message idempotently | `DEFERRED` | CHAT-DB-003 | Client-message-ID duplicate test |
| CHAT-API-005 | Attach scanned private file to message | `DEFERRED` | CHAT-API-004, PLAT-012 | MIME/access test |
| CHAT-API-006 | List conversation history by cursor | `DEFERRED` | CHAT-DB-003 | Membership negative test |
| CHAT-API-007 | Mark conversation read | `DEFERRED` | CHAT-DB-004 | Unread counter test |
| CHAT-RT-001 | WebSocket authenticate/connect | `DEFERRED` | CHAT-API-004, ID-AUTH-007 | Invalid token denial test |
| CHAT-RT-002 | Emit persisted message to authorized members | `DEFERRED` | CHAT-RT-001 | No message before persistence test |
| CHAT-WEB-001 | Conversation list/unread UI | `DEFERRED` | CHAT-API-007 | Browser E2E test |
| CHAT-WEB-002 | Message history/send UI | `DEFERRED` | CHAT-API-006, CHAT-RT-002 | Realtime E2E test |

---

## 8. Notification Service — `notification_db` / Laravel / MySQL (P3)

**Spec liên quan:** [Notification database](../database/05-notification-service-mysql.md), [Cross-service contract](../database/07-cross-service-data-contracts.md), [Baseline §6](../IMPLEMENTATION_BASELINE.md#6-chat-và-notification--phạm-vi-khi-mở-module).

| ID | Work item nhỏ | Status | Depends on | Done evidence cần có |
|---|---|---|---|---|
| NOTIF-DB-001 | Migration in-app notifications | `DEFERRED` | PLAT-006, ID-DB-003 | Recipient index/cursor test |
| NOTIF-DB-002 | Migration push devices and delivery attempts | `DEFERRED` | NOTIF-DB-001 | Token uniqueness/retry test |
| NOTIF-DB-003 | Migration processed events/outbox | `DEFERRED` | PLAT-010 | Duplicate event test |
| NOTIF-API-001 | Create in-app notification from domain event | `DEFERRED` | NOTIF-DB-001, NOTIF-DB-003 | Idempotency test |
| NOTIF-API-002 | List/read in-app notifications | `DEFERRED` | NOTIF-DB-001 | Recipient isolation test |
| NOTIF-PUSH-001 | Register/revoke FCM device token | `DEFERRED` | NOTIF-DB-002, ID-AUTH-007 | Owner-only test |
| NOTIF-PUSH-002 | Send/retry FCM notification | `DEFERRED` | NOTIF-PUSH-001 | Provider failure retry test |
| NOTIF-EVENT-001 | Subscribe comment/answer/mention events | `DEFERRED` | NOTIF-API-001, SOC-API-005, LEARN-QA-007 | Duplicate suppression test |
| NOTIF-EVENT-002 | Subscribe document-published/chat-message/announcement events | `DEFERRED` | NOTIF-API-001, CHAT-API-004, SOC-API-011, LEARN-DOC-003 | Targeting test |
| NOTIF-WEB-001 | Notification inbox/read UI | `DEFERRED` | NOTIF-API-002 | Browser E2E test |

---

## 9. Cross-service integration and security verification

| ID | Work item nhỏ | Status | Depends on | Done evidence cần có |
|---|---|---|---|---|
| INT-001 | Identity user-change event contract | `NOT_STARTED` | ID-DB-010, PLAT-009 | Consumer compatibility test |
| INT-002 | Identity directory read projection policy | `NOT_STARTED` | ID-PROFILE-004, INT-001 | No PII leakage test |
| INT-003 | Service-to-service auth strategy | `NOT_STARTED` | PLAT-008 | Unauthorized service denial test |
| INT-004 | Tenant/organization isolation test suite | `NOT_STARTED` | ID-DB-001 | Cross-org negative tests |
| INT-005 | Object-storage authorization test suite | `NOT_STARTED` | PLAT-012 | URL expiry/access denial tests |
| INT-006 | Audit redaction/security-event test suite | `NOT_STARTED` | ID-AUDIT-001 | No password/token/PII raw payload test |
| INT-007 | Fresh environment E2E: admin provision → first login → password change | `NOT_STARTED` | ID-AUTH-011, WEB-ID-002, PLAT-015 | Automated or scripted PASS evidence |
| INT-008 | Fresh environment E2E: seeded knowledge tree → graph explorer | `NOT_STARTED` | AI-GRAPH-009, PLAT-015 | Browser/API PASS evidence |

---

## 10. Idea Inbox — chưa được phép code

Thêm ý tưởng mới ở đây trước. Một idea chỉ được chuyển thành work item sau khi chủ dự án ghi `APPROVED`; khi đó gán ID service và dependency.

| Idea ID | Ý tưởng | Service dự kiến | Trạng thái | Quyết định/ghi chú |
|---|---|---|---|---|
| IDEA-001 | Android app: Kotlin/React Native/Flutter | Mobile | `DEFERRED` | Chọn stack sau khi Web API ổn định |
| IDEA-002 | AI provider, embedding và hybrid search thật | AI Knowledge | `BLOCKED` | Cần provider/model/region/budget/data policy |
| IDEA-003 | Coin quy đổi tiền thưởng và bounty settlement | Learning | `BLOCKED` | Cần finance, anti-fraud, approval/reversal policy |
| IDEA-004 | Email notification | Notification | `DEFERRED` | Baseline hiện tại chỉ in-app + push |
| IDEA-005 | Course/Lesson authoring, quiz, certificate | Learning | `DEFERRED` | Cần product flow riêng |
| IDEA-006 | Fine-grained document ACL theo org-unit/role/user | Learning | `DEFERRED` | MVP: mọi user đã login xem document published |
| IDEA-007 | Chat call/video, edit/delete message | Chat | `DEFERRED` | Ngoài Chat MVP |

---

## 11. Decision & Change Log

Chỉ ghi thay đổi đã được chủ dự án chấp thuận. Không ghi nhật ký tool/terminal nhỏ ở đây.

| Date | ID | Quyết định hoặc thay đổi | Ảnh hưởng | Approved by |
|---|---|---|---|---|
| 2026-09-12 | BASELINE-001 | Chốt Identity credential trong `users`; không OTP/activation; login email, lock sau 10 lần đến Admin unlock | Identity schema/API/test | Chủ dự án |
| 2026-09-12 | BASELINE-002 | Chốt Social không có Poll/Vote; có event RSVP, comment/reply/reaction/save/follow/moderation | Social schema/API | Chủ dự án |
| 2026-09-12 | BASELINE-003 | Chốt Phase 0 là Identity/RBAC/Admin + demo Cây tri thức; Web Vue/Laravel | P0 sequencing | Chủ dự án |
| 2026-09-12 | BASELINE-004 | Coin quy đổi thưởng và AI provider production chưa triển khai | Learning/AI scope | Chủ dự án |

---

## 12. Quick context cho một lần làm việc

Khi giao việc cho AI hoặc developer, chỉ cần nêu ID, ví dụ:

```text
Thực hiện ID-DB-003 và ID-AUTH-001 đến ID-AUTH-004.
Đọc IMPLEMENTATION_BASELINE.md, section Identity trong IMPLEMENTATION_PROGRESS.md,
database/01-identity-service-mysql.md và spec liên quan. Không làm task ngoài các ID này.
```

Khi hoàn thành, AI phải cập nhật đúng row trong file này. Không cần, và không nên, đọc/lặp lại toàn bộ project nếu task chỉ thuộc một service.
