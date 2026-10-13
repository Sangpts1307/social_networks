# Security Specification

## Scope and Current State

No executable security control can be verified because there is no application code, environment configuration, dependency manifest, route/middleware, infrastructure or automated test in `social_networks/`. The intended architectural rules are valuable constraints but are not live controls.

| Control area | Current state | Risk | Evidence | Recommendation |
| --- | --- | --- | --- | --- |
| Authentication | `DOCUMENTED_NOT_IMPLEMENTED`: Identity is intended token issuer; JWT asymmetric signing is preferred. | CRITICAL — no implementation or token/session lifecycle. | Architecture decision section 7; Identity README. | Approve token/session/refresh/revocation/device policy and test it in Identity + Gateway. |
| Authorization/RBAC | `DOCUMENTED_NOT_IMPLEMENTED`: Identity is source of roles/permissions; owning service also checks resource business permission. | CRITICAL — no role matrix/policy exists. | Architecture decision section 7. | Define actor-role-permission-resource matrix before any endpoint. |
| Gateway boundary | `DOCUMENTED_NOT_IMPLEMENTED`: client only calls Gateway; Gateway verifies tokens/rate limits. | CRITICAL — no proxy/middleware/config. | README; Gateway README; architecture decision. | Enforce no direct service public ingress and test edge routing/auth/rate limit. |
| Service-to-service auth | `DOCUMENTED_NOT_IMPLEMENTED` requirement. | HIGH — internal calls could be spoofed. | Architecture decision section 6. | Define credential/mTLS/token scheme, rotation and least privilege. |
| Passwords | `UNKNOWN` — no code/policy. | CRITICAL. | No source; workbook only mentions password. | Define hashing, reset, MFA decision, lockout, breach protection and audit policy. |
| CSRF/CORS/session | `UNKNOWN`. | HIGH. | No web/backend config. | Choose auth transport before configuring CSRF/cookie/CORS policies. |
| Input validation | `UNKNOWN`. | CRITICAL — forms/uploads/AI input unprotected. | No request validators; workbook features. | Define server-side schemas for every API; frontend validation is secondary. |
| SQL/NoSQL injection | `UNKNOWN`. | HIGH. | No queries/models. | Use parameterized ORM/query patterns, schema validation and security tests. |
| XSS/content sanitization | `UNKNOWN`. | HIGH — post/comment/Q&A content is user generated. | Social/Learning requirements. | Define allowed markup/plain-text policy and safe renderer. |
| File upload | `DOCUMENTED_NOT_IMPLEMENTED` constraints only. | CRITICAL — internal documents/media risk malware/data leak. | Workbook: image formats/2 MB; docx/pdf. | Validate actual MIME/content, size, scan, store private object key, authorize every download, log access. |
| Object storage | `DOCUMENTED_NOT_IMPLEMENTED`. | HIGH. | Architecture decision section 8. | Private bucket, short-lived access, encryption, lifecycle, deletion/versioning policy. |
| Secrets/API keys | `UNKNOWN`. | CRITICAL. | No `.env.example`/deployment config. | Use secret manager/environment injection; never commit keys; rotate and scan CI. |
| Rate limiting | `DOCUMENTED_NOT_IMPLEMENTED` at Gateway. | HIGH. | Gateway README; architecture decision. | Define per-route/client/user policy, abuse response and tests. |
| CORS | `UNKNOWN`. | HIGH. | No config. | Allow explicit first-party origins only; environment-specific config. |
| Audit logging | `UNKNOWN`. | HIGH for admin, auth, curation and points. | No schema/logging. | Define immutable audit events with actor/action/resource/outcome/correlation ID. |
| Realtime security | `UNKNOWN`. | HIGH. | No WebSocket/Pusher config. | Authenticate channel subscription, authorize resource membership, rotate credentials, prevent payload leakage. |
| Webhook validation | `UNKNOWN`. | MEDIUM. | No webhook code. | If providers are selected, verify signatures/replay protection. |
| AI prompt injection/data exfiltration | `DOCUMENTED_NOT_IMPLEMENTED` concern. | CRITICAL — documents and queries are untrusted. | AI concept documents; no safeguards. | Apply access-filtered retrieval, content boundaries, output schema, tool allowlist and human curation. |
| Sensitive data/retention | `UNKNOWN`. | CRITICAL. | Internal enterprise knowledge scope. | Classify data, define residency/retention/deletion/export policy and provider agreements. |

## Security Architecture Rules to Preserve

1. Identity alone owns passwords and primary authentication data.
2. Gateway verifies access token before forwarding, but service owners enforce resource/business permission again.
3. Services do not access another service’s database.
4. Correlation IDs are required for requests and must be safe to log.
5. Redis is not a durable source of business truth.
6. Binary file contents stay in object storage; databases hold metadata/object keys.

These are `DOCUMENTED_NOT_IMPLEMENTED` rules from `Cấu trúc cuối cùng.md`, not verified controls.

## Security Gates Before First Implementation

| Gate | Required decision/evidence |
| --- | --- |
| Identity boundary | Auth flow, password policy, token/session/refresh/revocation strategy, gateway/service validation. |
| Access matrix | Roles, department scope, own-vs-any resource actions, admin/curator privileges. |
| Data classification | Personal data, internal document classes, retention/deletion and data export rules. |
| File security | Type/size/virus scanning, private storage, download auth, document processing sandbox. |
| AI data path | Allowed providers/regions, redaction, prompt injection controls, curation/audit/deletion sync. |
| Delivery controls | Secrets, SAST/dependency scan, container/image policy, backup/restore and incident runbook. |

## Evidence

- `Cấu trúc cuối cùng.md`, sections 5–8
- `gateway/api-gateway/README.md`; `services/identity-service/README.md`
- `ananylics/Social Networks.xlsx` (login, upload, moderation requirements)
- `Mô tả đề tài.docx` (internal knowledge and KG intent)
- Repository inventory: no security implementation artifacts found
