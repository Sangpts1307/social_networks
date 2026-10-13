# AI Knowledge Service — `ai_knowledge_db` MySQL and Neo4j

## Trách nhiệm và nguyên tắc

AI Knowledge Service sở hữu pipeline metadata, extraction run, curation proposal, risk assessment, search/index metadata, expertise projection và Neo4j graph projection. Nó **không** là source of truth của user, document, post, question hay access permission.

Màn hình mới thể hiện Neo4j Bolt, “AI Vector Embedding Model”, risk detection, Graph Explorer, Hybrid Search và Expert Finder. Đây là evidence cho capability cần thiết, không chốt provider/model/vector store. UI nhắc Gemini và tài liệu cũ nhắc OpenAI/pgvector/Algolia — không value nào được seed vào database hoặc lưu secret.

## Pipeline metadata in MySQL

### `ai_model_profiles` and `prompt_template_versions`

| Table | Essential columns | Rules |
| --- | --- | --- |
| `ai_model_profiles` | `id`, `organization_id`, `purpose`, `provider_code`, `model_name`, `embedding_dimension`, `endpoint_secret_ref`, `data_classification_max`, `timeout_ms`, `max_input_tokens`, `max_output_tokens`, `cost_limit_json`, `status`, `effective_from`, `effective_to`, timestamps | `endpoint_secret_ref` points to a secret manager only. One approved active profile per purpose/version. |
| `prompt_template_versions` | `id`, `organization_id`, `purpose`, `version`, `template_body`, `output_schema_json`, `model_profile_id`, `status`, `approved_by_user_id`, `approved_at`, timestamps | Prompt is versioned and auditable. No raw confidential training/example text in template. |

`purpose` examples: `document_extract`, `entity_relation_extract`, `content_risk_scan`, `embedding`, `query_answer`. Do not expose provider credentials through Admin settings or audit logs.

### `ai_ingestion_jobs`

One job orchestrates processing an immutable source version/reference.

| Column | Type | Null | Default | Constraints / description |
| --- | --- | --- | --- | --- |
| `id` | `CHAR(36)` | No | — | PK. |
| `organization_id` | `CHAR(36)` | No | — | Scope. |
| `source_service` | `VARCHAR(64)` | No | — | `learning-service`, `social-service`, etc. |
| `source_type` / `source_id` | `VARCHAR(64)` / `CHAR(36)` | No | — | Logical source reference, no FK. |
| `source_version` | `VARCHAR(128)` | No | — | Document version ID/content version/checksum. |
| `trigger_event_id` | `CHAR(36)` | Yes | — | Idempotency source. |
| `job_type` | `VARCHAR(64)` | No | — | `document_ingest`, `content_reindex`, `risk_scan`, `delete_projection`. |
| `priority` | `SMALLINT UNSIGNED` | No | `100` | Queue priority. |
| `status` | `VARCHAR(32)` | No | `queued` | `queued`, `running`, `awaiting_curation`, `succeeded`, `failed`, `cancelled`, `superseded`. |
| `attempt_count` / `max_attempts` | `SMALLINT` | No | `0` / `3` | Bounded retry. |
| `requested_by_type` / `requested_by_user_id` | `VARCHAR(32)` / `CHAR(36)` | No/Yes | `event`, `user`, `system`. |
| `correlation_id` | `CHAR(36)` | Yes | — | Trace. |
| `started_at`, `completed_at`, `next_attempt_at` | `DATETIME(6)` | Yes | — | Worker lifecycle. |
| `error_code`, `error_detail_redacted` | `VARCHAR(128)`, `TEXT` | Yes | — | No raw source/private provider response. |
| timestamps | standard | No | — | — |

Indexes: `UNIQUE(source_service,source_type,source_id,source_version,job_type)` for active/current policy, `INDEX(status,priority,next_attempt_at)`, `INDEX(organization_id,created_at DESC)`, `INDEX(correlation_id)`.

### `ai_source_snapshots`

This records what source version was authorized for processing, not a duplicate source-of-truth document.

| Column | Type | Null | Description |
| --- | --- | --- | --- |
| `id`, `ingestion_job_id` | `CHAR(36)` | No | PK/FK job. |
| `source_uri_ref` | `VARCHAR(512)` | No | Authorized internal object/API reference, never public URL. |
| `content_sha256`, `content_language` | `CHAR(64)`, `VARCHAR(16)` | Yes | Reprocess/dedupe metadata. |
| `classification_at_ingest` | `VARCHAR(32)` | No | Source security classification. |
| `access_policy_version` | `VARCHAR(128)` | No | Authorization context version. |
| `extracted_text_encrypted` | `MEDIUMBLOB` | Yes | Only if policy approves retaining raw extract; otherwise null. |
| `redacted_text` | `MEDIUMTEXT` | Yes | Safe indexable copy if allowed. |
| `retention_expires_at`, `purged_at` | `DATETIME(6)` | Yes | Required lifecycle. |
| timestamps | standard | No | — |

## Runs, chunks and vector index references

### `ai_extraction_runs`

| Column | Type | Null | Description |
| --- | --- | --- | --- |
| `id`, `ingestion_job_id` | `CHAR(36)` | No | PK/FK. |
| `run_stage` | `VARCHAR(64)` | No | `extract_text`, `chunk`, `embed`, `entity_relation`, `risk_scan`, `graph_sync`. |
| `model_profile_id`, `prompt_template_version_id` | `CHAR(36)` | Yes | FKs when model/prompt used. |
| `input_hash`, `output_hash` | `CHAR(64)` | Yes | Integrity/debug without raw payload. |
| `status`, `started_at`, `completed_at`, `latency_ms` | typed | No/Yes | Run lifecycle. |
| `input_tokens`, `output_tokens`, `estimated_cost_microunits` | bigint | Yes | Observability/cost; no float. |
| `error_code`, `error_detail_redacted`, `metrics_json` | typed | Yes | Structured failure/quality data. |

Indexes: `(ingestion_job_id,run_stage,created_at)`, `(model_profile_id,created_at DESC)`, `(status,created_at)`.

### `ai_content_chunks`

| Column | Type | Null | Description |
| --- | --- | --- | --- |
| `id`, `organization_id`, `source_snapshot_id` | `CHAR(36)` | No | PK/scope/FK. |
| `chunk_number` | `INT UNSIGNED` | No | Zero-based/order within source. |
| `text_redacted` | `MEDIUMTEXT` | No | Access-approved text used for retrieval; retention applies. |
| `token_count`, `char_count` | `INT UNSIGNED` | No | Chunk metrics. |
| `start_offset`, `end_offset` | `INT UNSIGNED` | Yes | Location in source extract. |
| `language`, `content_hash` | `VARCHAR(16)`, `CHAR(64)` | No | Query/dedupe. |
| `index_status` | `VARCHAR(32)` | No | `pending`, `indexed`, `excluded`, `purged`, `failed`. |
| `created_at`, `purged_at` | `DATETIME(6)` | No/Yes | Lifecycle. |

Unique `(source_snapshot_id,chunk_number)`; index `(organization_id,index_status,created_at)`.

### `embedding_index_references`

MySQL stores metadata/reference, not unbounded 1536-dimensional vectors in a JSON/BLOB column. The actual compatible vector index technology is a pending ADR.

| Column | Type | Null | Description |
| --- | --- | --- | --- |
| `id`, `chunk_id` | `CHAR(36)` | No | PK/FK. |
| `index_provider` / `index_name` | `VARCHAR(64)` / `VARCHAR(128)` | No | Approved vector/index target. |
| `vector_record_id` | `VARCHAR(255)` | No | Record key at index provider. |
| `model_profile_id`, `dimensions` | `CHAR(36)`, `INT UNSIGNED` | No | Model/projection compatibility. |
| `embedding_hash` | `CHAR(64)` | Yes | Dedupe/integrity; not vector itself. |
| `index_status`, `indexed_at`, `deleted_at`, `error_code` | typed | — | Lifecycle. |

Unique `(index_provider,index_name,vector_record_id)`; index `(chunk_id,index_status)`.

## Proposals and curation

### `knowledge_concept_proposals` and `knowledge_relation_proposals`

| Table | Essential columns | Rules |
| --- | --- | --- |
| `knowledge_concept_proposals` | `id`, `organization_id`, `ingestion_job_id`, `chunk_id`, `normalized_name`, `display_name`, `concept_type`, `description`, `confidence_score`, `evidence_json`, `proposal_status`, `canonical_graph_node_id`, timestamps | `concept_type` e.g. `skill`,`technology`,`process`,`policy`,`domain`; proposed only until curation. |
| `knowledge_relation_proposals` | `id`, `organization_id`, `ingestion_job_id`, `source_proposal_id`, `target_proposal_id`, `relation_type`, `confidence_score`, `evidence_json`, `proposal_status`, `canonical_graph_relation_id`, timestamps | Relation type must belong to versioned ontology. |

`proposal_status`: `pending`, `approved`, `rejected`, `merged`, `superseded`, `revoked`. `evidence_json` holds source offsets/chunk IDs, not copied credential or full confidential documents.

### `curation_reviews`

| Column | Type | Null | Description |
| --- | --- | --- |
| `id`, `organization_id` | `CHAR(36)` | No | PK/scope. |
| `subject_type` / `subject_id` | `VARCHAR(64)` / `CHAR(36)` | No | Concept/relation/document-risk proposal. |
| `review_action` | `VARCHAR(32)` | No | `approve`, `edit_and_approve`, `reject`, `merge`, `revoke`. |
| `reviewer_user_id` | `CHAR(36)` | No | Identity user with curator permission. |
| `before_json`, `after_json`, `note` | `JSON`, `JSON`, `TEXT` | Redacted auditable transformation. |
| `reviewed_at` | `DATETIME(6)` | No | — |

Indexes: `(subject_type,subject_id,reviewed_at DESC)`, `(reviewer_user_id,reviewed_at DESC)`.

## AI risk assessment and search/expertise projections

### `content_risk_assessments`

| Column | Type | Null | Description |
| --- | --- | --- |
| `id`, `organization_id` | `CHAR(36)` | No | PK/scope. |
| `source_service`, `source_type`, `source_id`, `source_version` | typed | No | Generic reference to post/comment/question/answer/document. |
| `risk_type` | `VARCHAR(64)` | No | `credential_exposure`, `pii`, `toxicity`, `malware`, etc. |
| `risk_score` | `DECIMAL(5,4)` | No | Normalized model score, not a final decision. |
| `severity` | `VARCHAR(16)` | No | `low`, `medium`, `high`, `critical`. |
| `model_profile_id`, `extraction_run_id` | `CHAR(36)` | Yes | Evidence lineage. |
| `finding_redacted_json` | `JSON` | Yes | Masked finding/snippet; never plaintext credential. |
| `assessment_status` | `VARCHAR(32)` | No | `open`, `sent_to_owner`, `reviewed`, `false_positive`, `resolved`. |
| `created_at`, `resolved_at` | `DATETIME(6)` | No/Yes | — |

Indexes: `(organization_id,assessment_status,severity,created_at DESC)`, `(source_service,source_type,source_id,source_version)`, `(risk_type,created_at DESC)`.

### `expertise_projections`

| Column | Type | Null | Description |
| --- | --- | --- |
| `id`, `organization_id`, `user_id` | `CHAR(36)` | No | Projection identity/user reference. |
| `concept_key`, `graph_node_id` | `VARCHAR(255)`, `VARCHAR(255)` | No/Yes | Canonical concept link. |
| `score`, `confidence` | `DECIMAL(12,4)`, `DECIMAL(5,4)` | No/No | Derived; not wallet balance. |
| `evidence_count`, `best_answer_count` | `INT UNSIGNED` | No | Explainability counts. |
| `calculation_version`, `computed_at`, `valid_until` | typed | No/Yes | Rebuildable projection. |

Unique `(organization_id,user_id,concept_key,calculation_version)`; index `(organization_id,concept_key,score DESC)`.

### `search_query_audits`

Optional, privacy-minimized telemetry for Hybrid Search quality; do not retain raw sensitive queries by default.

| Column | Type | Null | Description |
| --- | --- | --- |
| `id`, `organization_id`, `user_id` | `CHAR(36)` | No/No/Yes | PK/scope/requester. |
| `query_hash`, `query_classification` | `CHAR(64)`, `VARCHAR(32)` | No/Yes | Hash/metadata first. Raw query only if approved with expiry. |
| `filter_json`, `result_count`, `top_result_refs_json` | JSON/numeric | Yes | Safe diagnostics. |
| `retrieval_profile_version`, `latency_ms`, `feedback` | typed | Yes | Evaluation. |
| `created_at`, `expires_at` | datetime | No/Yes | Retention enforcement. |

## Neo4j curated graph

### Node labels and required properties

| Label | Required properties | Optional properties | Source of truth |
| --- | --- | --- | --- |
| `Organization` | `id`, `name` | `status` | Identity projection. |
| `OrgUnit` | `id`, `organizationId`, `name`, `code` | `unitType` | Identity projection. |
| `User` | `id`, `organizationId`, `displayName` | `jobTitle`, `profileVisibility`, `sourceVersion` | Identity projection; no PII beyond approved display. |
| `Concept` | `id`, `organizationId`, `normalizedName`, `displayName`, `conceptType`, `ontologyVersion` | `description`, `status` | Curation-approved AI projection. |
| `Document` | `id`, `organizationId`, `versionId`, `title`, `classification`, `status` | `publishedAt` | Learning projection. |
| `Question` | `id`, `organizationId`, `title`, `status`, `categoryId` | `createdAt` | Learning projection. |
| `Answer` | `id`, `organizationId`, `questionId`, `status` | `isBestAnswer` | Learning projection. |
| `Course` / `Lesson` | `id`, `organizationId`, `title`, `status` | topic/category | Learning projection when implemented. |

Constraints/indexes: unique `(Organization.id)`, `(OrgUnit.id)`, `(User.id)`, `(Concept.id)`, `(Document.id,versionId)`, `(Question.id)`, `(Answer.id)`; indexes on `organizationId`, `normalizedName`, `conceptType` and supported full-text/vector index selected by ADR.

### Allowed relationship types

| Relationship | From -> To | Required properties | Meaning |
| --- | --- | --- | --- |
| `MEMBER_OF` | User -> OrgUnit | `sourceVersion`, `syncedAt` | Organization projection. |
| `AUTHORED` | User -> Document/Question/Answer | `sourceId`, `createdAt` | Authorship. |
| `EXPERTISE_IN` | User -> Concept | `score`, `confidence`, `calculationVersion`, `computedAt` | Derived expertise projection. |
| `HAS_TAG` | Document/Question/Answer/Course -> Concept | `source`, `confidence`, `approvedAt` | Curated tag relationship. |
| `HAS_ACCEPTED_ANSWER` | Question -> Answer | `acceptedAt` | Q&A resolution. |
| `SOLVED_BY` | Question -> User | `answerId`, `acceptedAt` | Expert credit derived from best answer. |
| `RELATED_TO` | Concept -> Concept | `relationType`, `confidence`, `evidenceRefs`, `ontologyVersion` | Curated semantic relation. |
| `DERIVED_FROM` | Document -> Answer/Question | `conversionType`, `approvedAt` | Best answer converted to library knowledge. |
| `RECOMMENDS` | Concept/Document -> Document/Course | `modelVersion`, `score`, `generatedAt`, `expiresAt` | Rebuildable recommendation, never permanent fact. |

Graph queries must enforce organization and source authorization. Neo4j classification property is an optimization/projection only; the caller must not grant access based solely on graph reachability.

## Sync, deletion and security invariants

1. Each source event is consumed idempotently and recorded in `processed_events` before/with projection effect.
2. A source publication/revocation/delete emits projection update; AI chunks, index refs and graph node/relation must purge/restrict accordingly.
3. Curation approval creates canonical graph projection only after reviewer permission and audit record.
4. AI assessment produces a case for source owner; it does not automatically delete content unless an approved policy says so.
5. Vector dimensions/model/index profile must match; never mix embeddings from incompatible profiles silently.
6. Prompt/model/config changes are versioned; secrets stay outside MySQL and audit payloads are redacted.

## Evidence

- `design/exports/html-tailwind/14-knowledge-graph-explorer.html`, `15-hybrid-search.html`
- `design/exports/html-tailwind/07-admin-social.html`
- `design/exports/admin/04-post-moderation.html`, `05-knowledge-qa-admin.html`, `06-document-management.html`, `07-system-audit-logs.html`, `08-system-settings.html`
- `Cấu trúc cuối cùng.md`, sections 4, 6 and 8
