# Open Questions

## Decision Rules

These questions block only the affected scope. They are not hidden assumptions. A project owner/authorized architect must record the chosen option in the relevant FR/API/ADR before code treats it as a requirement.

## OQ-001

**Question:** Accounts are self-registered, pre-provisioned by HR/admin, SSO-provisioned, or a hybrid?

**Why it matters:** Determines registration, identity source and offboarding.

**Current evidence:** Workbook describes self-registration with company email; architecture names Identity owner.

**Possible options:** self-registration allowlist; admin/HR provision; enterprise SSO; hybrid.

**Affected components:** Identity, Gateway, Web/Mobile. **Priority:** CRITICAL.

## OQ-002

**Question:** What password policy, lockout/reset/MFA and verification-code lifecycle apply?

**Why it matters:** Authentication security cannot be implemented safely otherwise.

**Current evidence:** Login/register names password only. **Possible options:** organization policy to approve. **Affected components:** Identity, Gateway. **Priority:** CRITICAL.

## OQ-003

**Question:** Is authentication JWT with refresh tokens, server session, or another protocol? How are revoke/logout handled?

**Why it matters:** Gateway/service validation and client storage depend on it.

**Current evidence:** Architecture prefers asymmetric JWT, not an approved concrete flow. **Possible options:** asymmetric JWT + rotating refresh; server session; SSO/OIDC. **Affected components:** Identity, Gateway, clients. **Priority:** CRITICAL.

## OQ-004

**Question:** What qualifies as first login/new device and what email alert content/retention is required?

**Why it matters:** Workbook requires an email alert but no reliable detection behavior.

**Current evidence:** Workbook rows 8–9. **Possible options:** device fingerprint/session metadata; explicit verified device; omit until policy approved. **Affected components:** Identity, notification/email. **Priority:** HIGH.

## OQ-005

**Question:** Which email provider/template/retry and verification-code rate limit are approved?

**Why it matters:** Registration/login alerts require a secure delivery contract.

**Current evidence:** No mail config. **Possible options:** approved internal SMTP; transaction provider; deferred feature. **Affected components:** Identity, Infrastructure. **Priority:** HIGH.

## OQ-006

**Question:** Which profile fields exist, who may edit each one, and which are visible company-wide?

**Why it matters:** Controls personal data and Social/Learning profile views.

**Current evidence:** Workbook names generic personal profile/position/address. **Possible options:** self-edit subset with HR-owned fields; all self-edit; admin override. **Affected components:** Identity, Social, Learning, clients. **Priority:** CRITICAL.

## OQ-007

**Question:** What avatar size/MIME scan/storage/deletion policy applies?

**Why it matters:** Extension-only validation is unsafe.

**Current evidence:** Workbook permits jpg/jpeg/png only. **Possible options:** private object storage + scan; approved provider avatar; no custom upload initially. **Affected components:** Identity, storage, clients. **Priority:** HIGH.

## OQ-008

**Question:** Which real summaries must Home display and which service owns each aggregation?

**Why it matters:** Welcome mock hard-codes social, learning, message and notification data.

**Current evidence:** `01-welcome.html`; workbook Home description. **Possible options:** minimal navigation only; Gateway aggregation; independent client calls to Gateway endpoints. **Affected components:** Gateway, Web, Social, Learning, Chat, Notification. **Priority:** MEDIUM.

## OQ-009

**Question:** What Social content audience/visibility model applies (company, department, channel/group, private)?

**Why it matters:** Governs feed, post, comments, search and AI access.

**Current evidence:** Internal network goal; no audience schema. **Possible options:** company-wide only MVP; department/channel ACL; richer audience rules. **Affected components:** Social, Identity, AI, clients. **Priority:** CRITICAL.

## OQ-010

**Question:** What cursor/sort/filter contract applies to feed, comments, notifications and Q&A lists?

**Why it matters:** “Six per load” is UI behavior, not a safe API pagination design.

**Current evidence:** Workbook specifies six items and some ordering. **Possible options:** cursor pagination; offset only for small admin lists. **Affected components:** Social, Learning, Notification, Gateway, clients. **Priority:** HIGH.

## OQ-011

**Question:** How are feed/event ordering, editing, deletion, retention and visibility changes defined?

**Why it matters:** Core post lifecycle is absent.

**Current evidence:** Workbook names post/event only. **Possible options:** publish-only MVP; editable with audit; scheduled event state model. **Affected components:** Social, Notification, AI. **Priority:** HIGH.

## OQ-012

**Question:** Which post media types, count, file handling and event fields are supported beyond image extension/2 MB?

**Why it matters:** Proposal names videos but workbook limits images; event data is unspecified.

**Current evidence:** Proposal/workbook conflict. **Possible options:** image-only MVP; document/video later; defined event start/end/attendance. **Affected components:** Social, storage, clients. **Priority:** HIGH.

## OQ-013

**Question:** Are reactions, upvotes, shares, follows, channels/groups and friend relations in scope for first Social release, and who owns each?

**Why it matters:** Repository/service docs and requirements mention overlapping concepts with no lifecycle.

**Current evidence:** Social README/workbook/pasted scope. **Possible options:** post/comment only MVP; add selected social graphs incrementally. **Affected components:** Social, Chat, Notification, AI. **Priority:** HIGH.

## OQ-014

**Question:** Can comments be edited/deleted/replied to, and what notification/realtime/moderation behavior applies?

**Why it matters:** Only length/order/load count is specified.

**Current evidence:** Workbook rows 45–48. **Possible options:** immutable comments MVP; ownership edit window; threaded replies later. **Affected components:** Social, Notification, realtime. **Priority:** HIGH.

## OQ-015

**Question:** What saved-post behavior applies: idempotent save, unsave, ordering, deleted source handling and private scope?

**Why it matters:** Only a sidebar destination is documented.

**Current evidence:** Workbook row 38. **Possible options:** simple private bookmark; folders/tags later. **Affected components:** Social, clients. **Priority:** MEDIUM.

## OQ-016

**Question:** What moderation state machine, authority, reason codes, audit, appeal and restore policy applies?

**Why it matters:** Hide/delete alone is insufficient for enterprise content governance.

**Current evidence:** Workbook rows 40–42. **Possible options:** manual moderation MVP; queued review; policy-based workflow. **Affected components:** Social, Identity, Notification. **Priority:** HIGH.

## OQ-017

**Question:** Is AI content scanning in scope now? If yes, which policy/model/human review/false-positive treatment is approved?

**Why it matters:** Moderation of language/images is named without an AI security/governance design.

**Current evidence:** Workbook row 42. **Possible options:** manual reports only MVP; AI advisory queue; provider-specific moderation. **Affected components:** Social, AI, moderation. **Priority:** HIGH.

## OQ-018

**Question:** What notification types, read state, deep links, preferences and retention are required?

**Why it matters:** A bell/dropdown has no durable product contract.

**Current evidence:** Workbook rows 31–33; Notification README. **Possible options:** in-app only MVP; in-app + email; in-app + mobile push. **Affected components:** Notification, Gateway, clients. **Priority:** HIGH.

## OQ-019

**Question:** What is the friendship/eligibility relationship for direct chat, and is group chat required?

**Why it matters:** Workbook limits chat to friends; proposal also names groups.

**Current evidence:** Workbook rows 53–56; project proposal. **Possible options:** direct internal chat without friend gate; explicit friend relation; group chat deferred. **Affected components:** Social, Chat, Identity. **Priority:** HIGH.

## OQ-020

**Question:** Which realtime transport is selected: managed Pusher or Gateway WebSocket, and how is client subscription authorized?

**Why it matters:** Architecture intentionally leaves choice open; both differ in operations/security.

**Current evidence:** Architecture decision section 8. **Possible options:** Pusher first; Gateway WebSocket; no realtime MVP with polling. **Affected components:** Gateway, Chat, Notification, Web/Mobile, Infra. **Priority:** HIGH.

## OQ-021

**Question:** What message retention, cursor pagination, attachment, edit/delete and delivery/read-state semantics apply?

**Why it matters:** “Load all history” is unsafe/unbounded.

**Current evidence:** Workbook rows 57–60; Chat README. **Possible options:** cursor-paged immutable text MVP; policy-defined retention; richer messaging later. **Affected components:** Chat, clients, storage. **Priority:** HIGH.

## OQ-022

**Question:** Who governs Learning topics/categories and which taxonomy is canonical?

**Why it matters:** Question and document classification depend on a controlled vocabulary.

**Current evidence:** Workbook gives examples only. **Possible options:** administrator-managed taxonomy; department-managed taxonomy; fixed MVP list. **Affected components:** Learning, Identity, AI. **Priority:** HIGH.

## OQ-023

**Question:** What sort/filter/search and visibility behavior applies to Q&A lists?

**Why it matters:** Workbook describes creation order and title/author search only incompletely.

**Current evidence:** Workbook rows 75, 83–85. **Possible options:** newest-first + cursor; relevance search later; department filters. **Affected components:** Learning, Gateway, Web. **Priority:** MEDIUM.

## OQ-024

**Question:** What title/body/attachment validation, edit/delete and publishing/moderation workflow applies to questions?

**Why it matters:** Create Question fields lack acceptance rules.

**Current evidence:** Workbook rows 87–98. **Possible options:** required plain text MVP; drafts; moderated publishing. **Affected components:** Learning, clients. **Priority:** HIGH.

## OQ-025

**Question:** Should duplicate/related-question AI suggestions be released, and what is the fallback without AI?

**Why it matters:** AI assistance can change user flow and reveal protected content.

**Current evidence:** Proposal motivation; no explicit feature contract. **Possible options:** no suggestion MVP; keyword-only; access-filtered AI suggestion. **Affected components:** Learning, AI. **Priority:** MEDIUM.

## OQ-026

**Question:** Are Q&A answers distinct from comments, and what is the accepted-answer lifecycle?

**Why it matters:** The detail screen names answers while its note refers to comments.

**Current evidence:** Workbook rows 100–105. **Possible options:** answers only; answers plus threaded comments; accepted-answer workflow. **Affected components:** Learning, clients. **Priority:** HIGH.

## OQ-027

**Question:** What vote semantics apply to questions/answers/documents: one vote, reversible vote, downvote, reputation effect and abuse protection?

**Why it matters:** Counts are shown but no transaction or anti-abuse rule exists.

**Current evidence:** Workbook rows 84, 102, 117; Social README. **Possible options:** one reversible upvote MVP; weighted role vote; no vote initially. **Affected components:** Learning, Social, points. **Priority:** HIGH.

## OQ-028

**Question:** Which education-profile data is public, what does “số bài viết” mean, and how are saved questions represented?

**Why it matters:** Profile views risk conflating Social, Learning and private data.

**Current evidence:** Workbook rows 77–80, 107–110. **Possible options:** learning-only profile; composite read model; self-only details. **Affected components:** Learning, Identity, clients. **Priority:** MEDIUM.

## OQ-029

**Question:** Which service owns coins, reputation and monetary conversion, and what auditable ledger/formula governs them?

**Why it matters:** Workbook assigns Edu statistics while Social owns reputation/upvote.

**Current evidence:** Workbook rows 121–124; Social README. **Possible options:** Social owns reputation and Learning projects read model; Learning owns learning coins; one dedicated future ledger. **Affected components:** Social, Learning, AI, Notification. **Priority:** CRITICAL.

## OQ-030

**Question:** Who can upload/view/download each document and what file version, size, scan and retention policy applies?

**Why it matters:** Internal documents are security-sensitive.

**Current evidence:** Workbook rows 112–119; architecture object-storage rule. **Possible options:** department ACL + private storage; company-wide MVP; admin-only upload. **Affected components:** Learning, Identity, Storage, AI. **Priority:** CRITICAL.

## OQ-031

**Question:** Is a document click a direct download, a preview/loading page, or both?

**Why it matters:** The workbook states both incompatible outcomes.

**Current evidence:** Workbook rows 118–119. **Possible options:** authorized download MVP; in-browser preview; detail page then download. **Affected components:** Learning, Web/Mobile, Storage. **Priority:** MEDIUM.

## OQ-032

**Question:** Which document changes trigger AI ingestion/re-ingestion and how is permission revoked/deletion propagated?

**Why it matters:** KG/search must never retain unauthorized/stale content.

**Current evidence:** AI/document proposals; no event contract. **Possible options:** upload/version/delete events; explicit manual curation trigger; no automatic ingestion MVP. **Affected components:** Learning, AI, RabbitMQ, Neo4j. **Priority:** CRITICAL.

## OQ-033

**Question:** What exact source and calculation turn Social upvotes into topical expertise/reputation?

**Why it matters:** Expert Finder fairness and point consistency depend on a stable score.

**Current evidence:** Social README and `knowledge_graph_detail.md` propose reputation score. **Possible options:** simple aggregate; decayed weighted score; manual expert designation. **Affected components:** Social, AI, Learning. **Priority:** HIGH.

## OQ-034

**Question:** What model/provider/region/data-retention terms are allowed for embeddings and LLM extraction?

**Why it matters:** Internal knowledge may not be sent to uncontrolled external services.

**Current evidence:** Older documents list many alternatives; no decision/config. **Possible options:** approved managed provider; self-hosted model; no LLM MVP. **Affected components:** AI, Security, Infrastructure. **Priority:** CRITICAL.

## OQ-035

**Question:** What vector retrieval technology is selected while preserving the priority MySQL + Neo4j decision?

**Why it matters:** Older pgvector/Chroma/Algolia suggestions conflict with current architecture.

**Current evidence:** Architecture decision rejects pgvector; no replacement is selected. **Possible options:** approved MySQL-compatible retrieval; Neo4j capability if evaluated; changed ADR. **Affected components:** AI, Infra. **Priority:** HIGH.

## OQ-036

**Question:** What curation roles, confidence thresholds, proposal states, audit record and rollback rights apply?

**Why it matters:** Human curation is a core documented safeguard.

**Current evidence:** Project proposal requires approve/edit before official graph write. **Possible options:** single admin reviewer MVP; domain experts; two-stage approval. **Affected components:** AI, Identity, Learning, Neo4j. **Priority:** CRITICAL.

## OQ-037

**Question:** What canonical Knowledge Graph ontology and source-ID/provenance rules apply?

**Why it matters:** Graph data cannot be safely queried or deleted without it.

**Current evidence:** Proposals name people/expertise/knowledge only; demo uses mock strings. **Possible options:** minimal versioned ontology; broader enterprise ontology after pilot. **Affected components:** AI, Neo4j, Learning, Social. **Priority:** CRITICAL.

## OQ-038

**Question:** What AI quality/evaluation metric, acceptance threshold, feedback loop and cost/timeout budget apply?

**Why it matters:** “Smart” output cannot be released without measurable quality and failure limits.

**Current evidence:** No evaluation source. **Possible options:** curated golden set and manual review; offline-only experiment; defer recommendations. **Affected components:** AI, Operations. **Priority:** HIGH.

## OQ-039

**Question:** How do source updates, deletion, user offboarding and access revocation remove chunks/index/graph relations?

**Why it matters:** Stale graph/retrieval may disclose revoked content.

**Current evidence:** No lifecycle/event design. **Possible options:** event-driven tombstone/reindex; periodic reconciliation; no AI indexing of restricted content initially. **Affected components:** Identity, Learning, Social, AI, RabbitMQ, Neo4j. **Priority:** CRITICAL.

## OQ-040

**Question:** What hybrid ranking, source citations, authorization filtering and expert-notification consent/throttle policy apply?

**Why it matters:** Search and Expert Finder must be relevant, explainable and non-leaky/non-spammy.

**Current evidence:** AI proposal names both features but no algorithm/UX. **Possible options:** access-filtered retrieval with source links; manual expert directory; delayed push invitation. **Affected components:** AI, Notification, Web/Mobile. **Priority:** HIGH.

## OQ-041

**Question:** Is Android mobile limited to authentication/feed/chat initially, or must it cover Learning and other domains too?

**Why it matters:** Scope changes app architecture/release sequencing substantially.

**Current evidence:** Workbook names feed and messenger as two main screens; architecture names full mobile client. **Possible options:** two-screen MVP; Social+Chat+notifications; parity later. **Affected components:** Mobile, Gateway, all domain services. **Priority:** MEDIUM.

## OQ-042

**Question:** What mobile push/offline/security/release signing policy applies?

**Why it matters:** Device data/token handling and production distribution cannot be assumed.

**Current evidence:** Mobile README mentions FCM only “when implemented”; no mobile project/config. **Possible options:** FCM with approved Firebase project; no push MVP; managed device policy. **Affected components:** Mobile, Notification, Identity, Infrastructure. **Priority:** HIGH.
