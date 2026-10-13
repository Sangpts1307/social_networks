# Database Specification

## Database Overview

### Current implementation

No database schema, migration, ORM model, seed, datasource configuration, connection string or database container exists in `social_networks/`. Therefore:

- **Verified tables/collections: 0**
- **Verified columns/indexes/foreign keys: 0**
- **Verified relations: 0**
- **Verified data-retention/soft-delete policy: 0**

It is not correct to claim “missing foreign keys” or “missing indexes”; their existence is unassessable until a schema exists.

### Detailed target design added after the UI update

The reviewable, **proposed** physical design—tables/collections, fields, keys, indexes, ownership, lifecycle and open decisions—is now maintained in [docs/database/README.md](../database/README.md). It is based on the updated `design/exports/html-tailwind` and `design/exports/admin` screens as well as the evidence in this specification set.

This does not change the facts above: it is not an implemented schema and must not be reported as verified tables/migrations. It becomes the migration input only after its open decisions are approved.

### Documented target data topology

| Owner boundary | Documented primary store | Documented ownership |
| --- | --- | --- |
| Identity | Dedicated MySQL schema/database | Accounts, profiles, departments, roles, permissions, login sessions/tokens |
| Social | Dedicated MySQL schema/database | Posts, media metadata, comments, reactions, upvotes, reputation |
| Learning | Dedicated MySQL schema/database | Courses, lessons, documents, progress, Q&A, FAQ |
| Chat | Dedicated MongoDB database | Conversations, members, messages, read state |
| Notification | Dedicated MySQL schema/database | In-app notifications, push devices, send history |
| AI Knowledge | Dedicated MySQL schema/database + Neo4j | AI jobs, input documents, chunk metadata, suggestions, curation; graph nodes/edges |
| Redis | Non-authoritative ephemeral store | Cache, rate limit, presence, locks and temporary data |
| Object Storage | Non-database binary store | File binary; database should keep only metadata/object key |

**Status for all rows:** `DOCUMENTED_NOT_IMPLEMENTED`.

## Data Ownership Rules

1. A business service owns its own database and migrations.
2. Never query, join or create a foreign key into another service’s database.
3. Cross-service identifiers are UUIDs issued/owned by the source service.
4. Fresh data may be obtained through an authenticated internal REST API. A service may maintain minimal read models from events when needed.
5. A consumer must tolerate eventual consistency and process events idempotently.
6. Shared ORM models or shared migrations are prohibited by the architecture decision.

**Evidence:** `Cấu trúc cuối cùng.md`, sections 4–6.

## Tables and Collections by Domain

No physical tables or collections are verified. The following are **conceptual ownership inventories**, not table designs and not permission to create a schema without an approved feature contract.

### DB-IDENTITY — Identity conceptual data

| Concept | Purpose from evidence | Physical table/collection | Relations / evidence |
| --- | --- | --- | --- |
| Account/user | Enterprise authentication identity | `UNKNOWN` | Owns account per Identity README. |
| Employee profile | Personal/employee profile and avatar metadata | `UNKNOWN` | Owns profile; workbook Profile requirements. |
| Department/organization | Employee organization structure | `UNKNOWN` | Named in Identity README. |
| Role/permission | Authorization source of truth | `UNKNOWN` | Named in architecture decision section 7. |
| Session/token | Authentication lifecycle | `UNKNOWN` | Architecture says Identity issues access token; exact model unknown. |

### DB-SOCIAL — Social conceptual data

| Concept | Purpose from evidence | Physical table/collection | Relations / evidence |
| --- | --- | --- | --- |
| Post/event post | Feed content or documented event content | `UNKNOWN` | Social README; workbook rows 43–44. |
| Media metadata | Metadata for images/files, binary external | `UNKNOWN` | Social README; architecture decision section 8. |
| Comment | Post interaction | `UNKNOWN` | Social README; workbook rows 45–48. |
| Reaction/upvote/reputation | Social interaction and expertise/reputation input | `UNKNOWN` | Social README. |
| Saved post, report, friend/follow | Requirement concepts only | `UNKNOWN` | Workbook rows 35–42, 50; no ownership/schema decision beyond likely Social. |

### DB-LEARNING — Learning conceptual data

| Concept | Purpose from evidence | Physical table/collection | Relations / evidence |
| --- | --- | --- | --- |
| Course/lesson/progress | LMS training and completion | `UNKNOWN` | Learning README; project proposal. |
| Question/answer/FAQ | Knowledge sharing | `UNKNOWN` | Learning README; workbook rows 69–105. |
| Topic/category | Q&A and document classification | `UNKNOWN` | Workbook rows 89–98. |
| Document metadata | Library item and file reference | `UNKNOWN` | Learning README; workbook rows 112–119. |
| Vote/saved question/coin | Learning engagement | `UNKNOWN` | Workbook rows 80, 84, 102, 109, 121–124. |

### DB-CHAT — Chat conceptual data

| Concept | Purpose from evidence | Physical collection | Relations / evidence |
| --- | --- | --- | --- |
| Conversation | Direct/group chat container | `UNKNOWN` | Chat README. |
| Member | Conversation membership | `UNKNOWN` | Chat README. |
| Message | Content and time | `UNKNOWN` | Chat README; workbook rows 54–60. |
| Read state | Read/unread status | `UNKNOWN` | Chat README; workbook rows 57–60. |

### DB-NOTIFICATION — Notification conceptual data

| Concept | Purpose from evidence | Physical table | Relations / evidence |
| --- | --- | --- | --- |
| In-app notification | User-visible notification | `UNKNOWN` | Notification README. |
| Push device | Addressable client device | `UNKNOWN` | Notification README. |
| Delivery history | Delivery attempts/history | `UNKNOWN` | Notification README. |

### DB-AI — AI metadata and Knowledge Graph conceptual data

| Concept | Purpose from evidence | Physical store | Relations / evidence |
| --- | --- | --- | --- |
| AI job/pipeline state | Async processing lifecycle | `UNKNOWN` | AI README. |
| Source/content/document metadata | Source reference for extraction | `UNKNOWN` | AI README; project proposal. |
| Chunk/embedding metadata | Text segmentation/search metadata | `UNKNOWN` | AI README; architecture proposal. |
| Extraction/curation proposal | Human review before graph persistence | `UNKNOWN` | Project proposal. |
| Knowledge graph node/edge | Approved knowledge representation | Neo4j model `UNKNOWN` | AI README; proposal and `knowledge_graph_detail.md`. |
| Expertise score | User topical reputation | `UNKNOWN` | `knowledge_graph_detail.md` proposes `user_expertise`; not current schema. |

## Cross-service References

The architecture explicitly provides an example of a Social-side `author_id` UUID issued by Identity. It does **not** define actual field names, FK columns or read-model tables. Any cross-service key plan must identify:

| Required item | Current status |
| --- | --- |
| Identifier format/version | `TODO / SPEC_GAP` |
| Identity service internal lookup API | `TODO / SPEC_GAP` |
| User profile read-model fields per consumer | `TODO / SPEC_GAP` |
| Event schemas, replay and idempotency keys | `TODO / SPEC_GAP` |
| Deletion/anonymization propagation | `TODO / SPEC_GAP` |

## Database Risks and Required Decisions

| Risk / gap | Severity | Why it matters | Recommendation |
| --- | --- | --- | --- |
| No schema or migration baseline | CRITICAL | Features cannot be implemented or tested deterministically. | Approve bounded data models per first feature, then create only its owning-service migration. |
| Unresolved Identity/department visibility | CRITICAL | Every content/document/search authorization decision depends on it. | Define organization, RBAC and data-access matrix before feature APIs. |
| Learning coins vs Social reputation ownership conflict | HIGH | A shared counter across databases risks invalid ownership. | Select one owner and publish event/read-model responsibilities. |
| Chat “load all history” requirement | HIGH | Unbounded reads conflict with scale. | Approve cursor pagination and retention policy. |
| File security/retention missing | HIGH | Documents can expose internal data and drive AI ingestion. | Define object-key policy, MIME scan, size, access check, versioning/deletion lifecycle. |
| AI graph source-of-truth and deletion sync missing | HIGH | Stale KG can leak deleted/unauthorized information. | Define source IDs, curation states, graph projection rules and deletion propagation. |
| No index/query evidence | MEDIUM | Performance cannot be designed or reviewed. | Write access patterns before table creation and justify indexes in each migration spec. |
| Soft delete/timestamps/enums/JSON conventions absent | MEDIUM | Inconsistent audit and lifecycle behavior likely. | Approve data conventions ADR before initial migration. |

## Evidence

- `Cấu trúc cuối cùng.md`, sections 4–6 and 8
- `services/*/README.md`
- `ananylics/Social Networks.xlsx`, “Tổng quan”
- `Mô tả đề tài.docx`; `knowledge_graph_detail.md`
- Repository inventory: no migration/schema/model/config artifacts
