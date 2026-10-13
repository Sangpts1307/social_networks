# Frontend Specification

## Current Frontend State

There is no Vue 3 project, `package.json`, TypeScript, router, state store, API client, component source, test or build configuration. `apps/web/` contains only a README. Therefore, all functional frontend behavior is `DOCUMENTED_NOT_IMPLEMENTED`.

Two static HTML mockups contain markup/style/scripts but no application integration:

| Reference | Verified visual content | Functional limitation |
| --- | --- | --- |
| `01-welcome.html` | Header with account/avatar/notification controls; Social and Learning workspace cards; course progress, notification/message summary. | No router, data fetch, auth state or API calls. |
| `02-login.html` | Enterprise email/password form, remember-me, forgot-password link, responsive visual layout. | Script prevents form submission and simulates success with `setTimeout`; no authentication. |

The remaining design export files are placeholders with no screen content: Social Feed, Messenger, Profile, Admin Social, Learning Home, Q&A List, Knowledge Graph Explorer and Hybrid Search.

## Page Map

| Page / intended route | Source reference | Intended module | Required backend feature | Current status |
| --- | --- | --- | --- | --- |
| Welcome/Home | `01-welcome.html`; workbook rows 15–22 | Web | Authenticated account + Social/Learning/Notification/Chat summaries | `VERIFIED_PARTIAL` static only |
| Login | `02-login.html`; workbook rows 4–9 | Identity | FR-AUTH-001 | `VERIFIED_PARTIAL` static only |
| Register | workbook rows 10–13 | Identity | FR-AUTH-002 | `DOCUMENTED_NOT_IMPLEMENTED` |
| My Profile | workbook rows 25–27 | Identity | FR-PROFILE-001 | `DOCUMENTED_NOT_IMPLEMENTED` |
| Social Feed | `03-social-feed.html` placeholder; workbook rows 29–50 | Social | FR-SOCIAL-001 to 005 | `DOCUMENTED_NOT_IMPLEMENTED` |
| Messenger | `05-messenger.html` placeholder; workbook rows 53–60 | Chat | FR-CHAT-001 | `DOCUMENTED_NOT_IMPLEMENTED` |
| User Profile | `06-profile.html` placeholder; workbook rows 62–67 | Identity/Social | No distinct complete FR; profile visibility is a gap | `DOCUMENTED_NOT_IMPLEMENTED` |
| Social moderation | `07-admin-social.html` placeholder; workbook rows 40–42 | Social | FR-SOCIAL-005 | `DOCUMENTED_NOT_IMPLEMENTED` |
| Learning Home | `08-learning-home.html` placeholder; workbook rows 69–85 | Learning | FR-LEARN-001 | `DOCUMENTED_NOT_IMPLEMENTED` |
| Q&A List | `09-qa-list.html` placeholder | Learning | FR-LEARN-001 | `DOCUMENTED_NOT_IMPLEMENTED` |
| Create Question | workbook rows 87–98 | Learning | FR-LEARN-002 | `DOCUMENTED_NOT_IMPLEMENTED` |
| Question Detail | workbook rows 100–105 | Learning | FR-LEARN-003 | `DOCUMENTED_NOT_IMPLEMENTED` |
| Edu Profile/Statistics | workbook rows 107–124 | Learning | FR-LEARN-004, FR-GAME-001 | `DOCUMENTED_NOT_IMPLEMENTED` |
| Edu Library | workbook rows 112–119 | Learning | FR-DOC-001 | `DOCUMENTED_NOT_IMPLEMENTED` |
| Knowledge Graph Explorer | `14-knowledge-graph-explorer.html` placeholder | AI Knowledge | FR-AI-001/002 | `DOCUMENTED_NOT_IMPLEMENTED` |
| Hybrid Search | `15-hybrid-search.html` placeholder | AI Knowledge | FR-AI-002 | `DOCUMENTED_NOT_IMPLEMENTED` |

## Static Design Evidence

### Login visual requirements found

- Vietnamese locale and “NexaHub/NovaTech” branding.
- Inputs: enterprise email, password, remember-me; forgot-password link.
- Responsive two-panel layout on desktop and logo-focused mobile layout.
- Security copy says only company accounts can access.

These are UI/reference requirements only. The following are **not implemented**: client-side validation behavior, disabled/loading/error success states against real responses, accessibility semantics/keyboard test, navigation, token storage and password visibility behavior.

### Welcome visual requirements found

- Workspace selection for internal Social and Learning/Knowledge.
- Examples of feed, chat realtime, event, colleagues, courses, Q&A, library and Knowledge Graph.
- Header account/notification affordances and summaries.

The displayed employee name, company, progress, counts and messages are hard-coded mock content and must not become seed/business truth.

## Frontend Integration Rules

| Rule | Status / evidence |
| --- | --- |
| Web uses Vue 3 + TypeScript + TailwindCSS | `DOCUMENTED_NOT_IMPLEMENTED`; `apps/web/README.md` |
| Web only calls API Gateway | `DOCUMENTED_NOT_IMPLEMENTED`; `README.md`, architecture decision |
| Stitch -> static HTML -> design tokens/components -> Vue module -> Gateway is desired flow | `DOCUMENTED_NOT_IMPLEMENTED`; `design/stitch/project-link.md` |
| No framework source should be created before actual component implementation begins | Architectural base-stage rule; `Cấu trúc cuối cùng.md` section 9 |

## Required Frontend Contract per Page

Before a screen is implemented, its spec must record:

1. Page route and entry/exit navigation.
2. Actor, route guard and resource-level permission.
3. Exact Gateway API record(s); never call a domain service directly.
4. Data fields, loading/empty/error/unauthorized states.
5. Form validation and server-error mapping.
6. Pagination/query/filter/sort behavior where applicable.
7. Realtime event behavior, fallback refresh and deduplication where applicable.
8. Responsive, keyboard and screen-reader acceptance criteria.
9. UI, integration and end-to-end test cases.

## Frontend Risks

| Gap | Severity | Recommendation |
| --- | --- | --- |
| HTML design references are disconnected from Vue source | HIGH | Treat them as visual acceptance references, then create implementation-specific component contracts. |
| Page designs are mostly placeholders | HIGH | Obtain/export final screens before estimating frontend work. |
| No design token/component library specification | MEDIUM | Extract approved colors, typography and component behavior from a finalized design source. |
| No API contract | CRITICAL | Do not bind frontend to invented JSON; create Gateway contracts first. |
| No explicit accessibility or mobile responsive acceptance criteria | HIGH | Define them per page before implementation. |

## Evidence

- `apps/web/README.md`
- `design/stitch/project-link.md`
- `design/exports/html-tailwind/*.html`
- `ananylics/Social Networks.xlsx`, “Tổng quan”
- `Cấu trúc cuối cùng.md`, sections 3 and 9
