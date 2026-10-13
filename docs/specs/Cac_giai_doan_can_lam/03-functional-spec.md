# Functional Specification

## Scope and Status

This catalog expresses **only functions documented by existing artifacts**. It is not proof of live behavior. No backend, API, database, job or automated test implementation was found in `social_networks/`.

- `VERIFIED_PARTIAL` below means a static HTML reference exists, **not** that the workflow works.
- `DOCUMENTED_NOT_IMPLEMENTED` means source documentation/design describes it but no implementation was found.
- Every unspecified field is `TODO / SPEC_GAP`; an implementer must resolve it before coding instead of inventing behavior.

## Requirement Catalog

| ID | Feature | Domain | Status |
| --- | --- | --- | --- |
| FR-AUTH-001 | Đăng nhập doanh nghiệp | Identity | `VERIFIED_PARTIAL` (static UI only) |
| FR-AUTH-002 | Đăng ký và xác thực email | Identity | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-PROFILE-001 | Cập nhật hồ sơ và avatar của chính mình | Identity | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-HOME-001 | Trang chào mừng/chọn workspace | Web | `VERIFIED_PARTIAL` (static UI only) |
| FR-SOCIAL-001 | Xem và phân trang bảng tin | Social | `VERIFIED_PARTIAL` — Social core API/Gateway PASS 2026-10-13; UI và mở rộng còn lại |
| FR-SOCIAL-002 | Tạo bài viết hoặc sự kiện có ảnh | Social | `VERIFIED_PARTIAL` — Social core API/Gateway PASS 2026-10-13; UI và mở rộng còn lại |
| FR-SOCIAL-003 | Bình luận bài viết | Social | `VERIFIED_PARTIAL` — Social core API/Gateway PASS 2026-10-13; UI và mở rộng còn lại |
| FR-SOCIAL-004 | Lưu bài viết và quản lý danh sách đã lưu | Social | `VERIFIED_PARTIAL` — Social core API/Gateway PASS 2026-10-13; UI và mở rộng còn lại |
| FR-SOCIAL-005 | Duyệt/xử lý bài viết bị report hoặc AI gắn cờ | Social | `VERIFIED_PARTIAL` — Social core API/Gateway PASS 2026-10-13; UI và mở rộng còn lại |
| FR-NOTIF-001 | Xem thông báo gần nhất | Notification | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-CHAT-001 | Nhắn tin trực tiếp và trạng thái đã đọc | Chat | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-LEARN-001 | Duyệt danh sách câu hỏi tri thức | Learning | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-LEARN-002 | Tạo câu hỏi theo chủ đề/chuyên mục | Learning | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-LEARN-003 | Xem chi tiết câu hỏi, câu trả lời và vote | Learning | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-LEARN-004 | Hồ sơ học tập, câu hỏi và nội dung đã lưu | Learning | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-DOC-001 | Thư viện, tải lên và tải xuống tài liệu | Learning | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-GAME-001 | Thống kê xu/điểm và reputation | Learning/Social | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-AI-001 | Trích xuất tri thức và curation từ tài liệu | AI Knowledge | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-AI-002 | Hybrid Search, Expert Finder, gợi ý học tập | AI Knowledge | `DOCUMENTED_NOT_IMPLEMENTED` |
| FR-MOBILE-001 | Mobile Newsfeed và Messenger | Mobile | `DOCUMENTED_NOT_IMPLEMENTED` |

## Identity

### FR-AUTH-001 — Đăng nhập doanh nghiệp

| Field | Specification |
| --- | --- |
| Description / Actors | Nhân viên đăng nhập bằng email doanh nghiệp và mật khẩu. Actor: người dùng chưa xác thực. |
| Preconditions / Trigger | Có một account hợp lệ. Trigger: gửi form login. Tồn tại static form tại `02-login.html`. |
| Main Flow | (1) Nhập email, mật khẩu, tùy chọn “Ghi nhớ đăng nhập”. (2) Validate required. (3) Xác thực account. (4) Trả session/token. (5) Điều hướng Homepage. |
| Alternative Flows | Missing email/password; credentials sai; account không hợp lệ/bị khóa; login lần đầu hoặc thiết bị khác. Các response chính xác chưa có. |
| Business Rules | Workbook yêu cầu thông báo required: “Vui lòng nhập tài khoản/ mật khẩu của bạn”; login thành công dẫn Homepage; lần đầu/thiết bị khác gửi mail. |
| Input / Output | Input: email, password, `remember` (UI). Output: authenticated state và điều hướng dự kiến; token/session schema `UNKNOWN`. |
| Validation / Permissions | Required; email format/policy, password policy, account status và authorization mapping là `TODO / SPEC_GAP`. Public endpoint dự kiến, không có route. |
| Errors | Không có error contract. Không được suy diễn HTTP status/message ngoài required copy đã nêu. |
| Side effects / Notifications / Async | Email alert được tài liệu yêu cầu cho lần đầu/thiết bị mới; event/job/provider/điều kiện nhận diện device `UNKNOWN`. |
| Data / Dependencies | Documented Identity ownership, Gateway. User/account, token/session only conceptual. |
| Implementation Status / Evidence | `VERIFIED_PARTIAL`: UI fields and demo-only submit animation in `design/exports/html-tailwind/02-login.html`; functional requirement in workbook “Tổng quan” rows 4–9. No route/service/model exists. |
| Open Questions | OQ-001, OQ-002, OQ-003, OQ-004. |

### FR-AUTH-002 — Đăng ký và xác thực email

| Field | Specification |
| --- | --- |
| Description / Actors | Người chưa có tài khoản đăng ký bằng email/mật khẩu doanh nghiệp. |
| Preconditions / Trigger | Email phải thuộc thành viên công ty; danh sách nguồn xác thực email không được định nghĩa. Trigger: gửi form register (UI/route `NOT_FOUND`). |
| Main Flow | (1) Nhập email/mật khẩu. (2) Kiểm tra eligibility email. (3) Gửi mã qua email. (4) Nhập mã. (5) Hiển thị đăng ký thành công. (6) Chuyển Login. |
| Alternative Flows | Email không thuộc công ty, email đã tồn tại, mã sai/hết hạn, resend/rate limit are `TODO / SPEC_GAP`. |
| Business Rules | Sau khi xác minh mã thành công hiển thị “Đăng ký tài khoản thành công”, sau đó bắt buộc login. |
| Input / Output / Validation | Email, password, verification code; field length, password policy, code lifetime and output schema are unknown. |
| Permissions / Errors | Public flow; error contract `UNKNOWN`. |
| Side effects / Notifications / Async | Gửi email verification; provider, queue/retry/audit event unknown. |
| Data / Dependencies | Identity database; mail provider; Gateway. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`. Workbook rows 10–13. No UI, route, mail config or implementation. |
| Open Questions | OQ-001, OQ-002, OQ-005. |

### FR-PROFILE-001 — Cập nhật hồ sơ và avatar của chính mình

| Field | Specification |
| --- | --- |
| Description / Actors | Người dùng đã xác thực chỉnh sửa thông tin cá nhân, bao gồm avatar. |
| Preconditions / Trigger | Actor owns target profile. Trigger/page/API `NOT_FOUND`. |
| Main Flow | (1) Mở My Profile. (2) Sửa allowed fields. (3) Chọn avatar. (4) Validate. (5) Lưu và hiển thị profile mới. |
| Alternative Flows | File không hợp lệ, upload thất bại, concurrent update, profile field authorization: `TODO / SPEC_GAP`. |
| Business Rules | Avatar chỉ `jpg`, `jpeg`, `png` theo workbook. File-size limit cho avatar chưa có. |
| Input / Output / Validation | Profile fields are unknown; avatar MIME/extension policy documented. Response schema unknown. |
| Permissions / Errors | Only self-service is documented; admin/HR profile-edit authority unknown. Error contract unknown. |
| Side effects / Notifications / Async | Cross-service `user.profile.updated` is an architecture example, not an implemented/approved FR event. Actual propagation is `UNKNOWN`. |
| Data / Dependencies | Identity ownership; object storage is documented target. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook rows 25–27; profile link appears in welcome/social design text. |
| Open Questions | OQ-006, OQ-007. |

## Home and Social

### FR-HOME-001 — Trang chào mừng/chọn workspace

| Field | Specification |
| --- | --- |
| Description / Actors | Người dùng đã đăng nhập xem trang chính và chọn Social hoặc Learning workspace. |
| Preconditions / Trigger | Authenticated state is assumed by UI but no implementation exists. Trigger: login success or direct navigation. |
| Main Flow | Header hiển thị app/avatar/account action; body hiển thị two workspace entries; footer chào người dùng. Static welcome mock còn hiển thị course progress, unread notification và pending message summary. |
| Alternative Flows / Validation | Loading, empty, unauthorized, responsive navigation and actual destination routes are unknown. |
| Business Rules | Workbook yêu cầu header account/logout, 2 buttons (Social and Learning), footer greeting/time/orientation. |
| Input / Output / Permissions | No direct input except navigation; personalized data contract and permissions unknown. |
| Side effects / Notifications / Async | None proven. |
| Data / Dependencies | Gateway; Identity; Social/Learning/Notification/Chat summaries are inferred UI dependencies only. |
| Implementation Status / Evidence | `VERIFIED_PARTIAL`: `01-welcome.html` is a static mock. Workbook rows 15–22. No Vue/router/API. |
| Open Questions | OQ-008. |

### FR-SOCIAL-001 — Xem và phân trang bảng tin

| Field | Specification |
| --- | --- |
| Description / Actors | Nhân viên xem feed nội bộ; UI design also names search, channels, event list, people suggestion and saved posts. |
| Preconditions / Trigger | Authenticated user; target Social page is a placeholder. Trigger: open Social workspace or load more. |
| Main Flow | (1) Load feed. (2) Hiển thị tối đa 6 posts per load. (3) Người dùng yêu cầu load thêm. (4) Hiển thị posts theo rule ordering. |
| Alternative Flows | Empty feed, deleted/hidden post, cursor exhaustion, failed load: `TODO / SPEC_GAP`. |
| Business Rules | Workbook says 6 feed entries per load; post content preview maximum 150 characters with “Xem thêm”. Ordering is not specified. |
| Input / Output / Validation | Filter/search parameters and post DTO unknown. |
| Permissions / Errors | Audience/department/channel visibility and error contract unknown. |
| Side effects / Notifications / Async | None proven. |
| Data / Dependencies | Social Service documented owner; Profile/Notification/Identity read model may be needed but integration not specified. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook rows 29–50; `03-social-feed.html` placeholder; social README. |
| Open Questions | OQ-009, OQ-010, OQ-011. |

### FR-SOCIAL-002 — Tạo bài viết hoặc sự kiện có ảnh

| Field | Specification |
| --- | --- |
| Description / Actors | Nhân viên tạo post hoặc event post, optionally with image. |
| Preconditions / Trigger | Authenticated user opens composer. Composer UI/API `NOT_FOUND`. |
| Main Flow | (1) Chọn post hoặc event. (2) Nhập content. (3) Chọn image optional. (4) Validate. (5) Save/publish. (6) Feed reflects post. |
| Alternative Flows | Upload fail, invalid format/size, draft, scheduling, event date/time, moderation hold and rollback are unknown. |
| Business Rules | Image extensions `jpg`, `jpeg`, `png`; max size <= 2 MB. Event is named but event fields/lifecycle are unspecified. |
| Input / Output / Validation | Content rules and media metadata schema unknown; file policy above is documented. |
| Permissions / Errors | Default publishing audience, department/channel audience, error contract and moderation authority unknown. |
| Side effects / Notifications / Async | Potential feed, notification and AI processing are documented ideas, not current behavior. Event contracts/jobs unknown. |
| Data / Dependencies | Social ownership; object storage and AI/Notification may be target dependencies. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook rows 43–48; social README. |
| Open Questions | OQ-009, OQ-012, OQ-013. |

### FR-SOCIAL-003 — Bình luận bài viết

| Field | Specification |
| --- | --- |
| Description / Actors | Authenticated employee reads and writes comments under a visible post. |
| Preconditions / Trigger | Post exists and actor can view it; composer/UI/API unknown. |
| Main Flow | (1) Load comments. (2) Show 6 per load, newest-to-oldest. (3) Submit comment. (4) Validate. (5) Persist/display. |
| Alternative Flows | Post removed, permission revoked, duplicate submission, pagination cursor, edit/delete/comment reply unknown. |
| Business Rules | Comment content maximum 255 characters; list is newest-to-oldest; 6 comments per load. |
| Input / Output / Validation | Comment text; UTF-8/grapheme counting and response schema unknown. |
| Permissions / Errors | Visibility/moderation and error contract unknown. |
| Side effects / Notifications / Async | Owner notification/realtime/AI scan are not specified as confirmed functional behavior. |
| Data / Dependencies | Social Service; optional Notification/realtime. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook rows 45–48; social README. |
| Open Questions | OQ-010, OQ-014. |

### FR-SOCIAL-004 — Lưu bài viết và xem danh sách đã lưu

| Field | Specification |
| --- | --- |
| Description / Actors | Authenticated user saves a visible post and opens saved-post list. |
| Preconditions / Trigger | Actor can view post. Trigger: save action/sidebar “Bài viết đã lưu”. |
| Main Flow | (1) Select Save. (2) Persist user-post relation. (3) Open saved list. (4) Display accessible saved posts. |
| Alternative Flows / Validation | Idempotency, unsave, deleted source post and sort order are unknown. |
| Business Rules | Workbook states saved-post sidebar points to posts saved from each post. |
| Input / Output / Permissions / Errors | Post identifier inferred; DTO, authorization rule and errors unknown. |
| Side effects / Notifications / Async | None documented. |
| Data / Dependencies | Social Service owns feature by domain inference; table model unknown. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook row 38. |
| Open Questions | OQ-015. |

### FR-SOCIAL-005 — Duyệt/xử lý bài viết bị report hoặc AI gắn cờ

| Field | Specification |
| --- | --- |
| Description / Actors | Administrator reviews unusual posts, can hide or delete. |
| Preconditions / Trigger | Actor is “tài khoản quản trị”; exact role/permission not defined. Trigger: open approval area. |
| Main Flow | (1) List flagged posts. (2) Inspect reason. (3) Hide or delete. (4) Record result. |
| Alternative Flows | Appeal, restore, AI confidence threshold, notification to author, report abuse and audit trail are unknown. |
| Business Rules | Candidate posts are reported by users or scanned by AI for violent language/offensive images. Admin may hide or delete. |
| Input / Output / Validation | Moderation action and reason inferred; state machine and response unknown. |
| Permissions / Errors | Only an administrator is documented; RBAC mapping/error contract unknown. |
| Side effects / Notifications / Async | AI scanning is a requirement/design idea; actual AI job/event and notifications unknown. |
| Data / Dependencies | Social; Identity RBAC; possible AI/Notification. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook rows 40–42. |
| Open Questions | OQ-016, OQ-017. |

## Notification and Chat

### FR-NOTIF-001 — Xem thông báo gần nhất

| Field | Specification |
| --- | --- |
| Description / Actors | Authenticated user opens notification bell/dropdown. |
| Preconditions / Trigger | User has a notification feed. Trigger: click bell. |
| Main Flow | Load newest-to-oldest; show 6 notifications each load; scroll/load more. |
| Alternative Flows / Validation | Mark read, action routing, empty state, pagination protocol and duplicate delivery are unknown. |
| Business Rules | 6 items per load; newest first. |
| Input / Output / Permissions / Errors | Notification schema and authorization unknown; only own notifications are inferred. |
| Side effects / Notifications / Async | This is a consumption flow. Production/distribution mechanisms unknown. |
| Data / Dependencies | Notification Service documented owner; welcome mock shows an unread indicator but no functional API. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook rows 31–33; `01-welcome.html`; notification README. |
| Open Questions | OQ-018. |

### FR-CHAT-001 — Nhắn tin trực tiếp và trạng thái đã đọc

| Field | Specification |
| --- | --- |
| Description / Actors | Users exchange direct messages in realtime; unread messages displayed bold/read state cleared when opened. |
| Preconditions / Trigger | Workbook says participants must be friends. Friendship model, conversation creation, UI/API and realtime channel are unknown. |
| Main Flow | (1) Select eligible user/conversation. (2) Load history. (3) Send message. (4) Receive realtime update. (5) Show unread count/latest time. (6) Opening chat marks messages read. |
| Alternative Flows | Offline recipient, delivery failure, group chat, attachment, history pagination/retention, message edit/delete, block/unfriend behavior unknown. |
| Business Rules | Direct chat only with friends; unread messages bold; clicking chat switches unread to read; show latest-message time. Workbook says load all past messages, which needs scalability decision. |
| Input / Output / Validation | Message payload/limit/content policy unknown. |
| Permissions / Errors | Conversation membership implied; enforcement/error contract unknown. |
| Side effects / Notifications / Async | Realtime required by requirement; push notification integration/event semantics unknown. |
| Data / Dependencies | Chat Service owns message truth in documented MongoDB; Gateway/realtime/Notification. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook rows 53–60; chat README; `05-messenger.html` placeholder. |
| Open Questions | OQ-019, OQ-020, OQ-021. |

## Learning and Knowledge Sharing

### FR-LEARN-001 — Duyệt danh sách câu hỏi tri thức

| Field | Specification |
| --- | --- |
| Description / Actors | Employee browses company knowledge questions. |
| Preconditions / Trigger | Authenticated user opens Edu Home/Q&A list. |
| Main Flow | Show questions in creation-date order; load 6 more per request; each shows title, author, time, content, answer count and vote count; title opens detail. |
| Alternative Flows | Sort direction, empty state, filtering/search, deleted/moderated content and pagination protocol unknown. |
| Business Rules | Page header includes Home/Library/Profile/Search/Avatar; sidebar includes Ask Question, My Questions, Saved Questions, Statistics. Search documented for title/author. |
| Input / Output / Validation | Search inputs shown only conceptually; question list DTO unknown. |
| Permissions / Errors | Internal-company audience inferred; department visibility/error contract unknown. |
| Side effects / Notifications / Async | None proven. |
| Data / Dependencies | Learning Service; Identity profile summary and potential AI Search are not defined integrations. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook rows 69–85; learning README; `08-learning-home.html` and `09-qa-list.html` placeholders. |
| Open Questions | OQ-022, OQ-023. |

### FR-LEARN-002 — Tạo câu hỏi theo chủ đề/chuyên mục

| Field | Specification |
| --- | --- |
| Description / Actors | Authenticated employee chooses topic/category and writes a question. |
| Preconditions / Trigger | Actor enters Ask Question. A topic/category taxonomy must exist; its ownership/governance is unknown. |
| Main Flow | (1) Select topic and category. (2) Move to form. (3) Enter title and body. (4) Submit. (5) Question appears in list/detail. |
| Alternative Flows | Invalid/missing selection, duplicate/related question suggestion, drafts, attachment, moderation and anonymous question are unknown. |
| Business Rules | Workbook gives examples: Kỹ thuật -> Backend/Frontend/Tester/QA; Marketing -> Quảng cáo/Content; Kế toán, CSKH, Nhân sự. Right side shows selected topic/category. |
| Input / Output / Validation | Title/body requiredness/length/sanitization and result schema unknown. |
| Permissions / Errors | Authenticated employee is implied; category-specific permission/error contract unknown. |
| Side effects / Notifications / Async | Expert notification/AI duplicate detection are only proposed ideas, not accepted behavior. |
| Data / Dependencies | Learning; possible AI/Notification later. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook rows 87–98. |
| Open Questions | OQ-022, OQ-024, OQ-025. |

### FR-LEARN-003 — Chi tiết câu hỏi, câu trả lời và vote

| Field | Specification |
| --- | --- |
| Description / Actors | Employee views question content, vote count and answers; related author questions visible. |
| Preconditions / Trigger | User can view question. Trigger: click question title. |
| Main Flow | Load topic/category, author summary, vote count, title/body, answers, and other questions by author. |
| Alternative Flows | Create/edit/delete answer, accepted answer, vote toggling, comment semantics, pagination and error behavior are unknown. |
| Business Rules | Detail view has left author/topic/category, center vote/title/content/answers, right other questions by author; workbook says comment list newest-to-oldest but does not identify whether this means answers or separate comments. |
| Input / Output / Validation | Answer/vote payloads and validation unknown. |
| Permissions / Errors | Visibility, answer rights, vote rights, author ownership and error contract unknown. |
| Side effects / Notifications / Async | Reputational points/expert invitations may be related but not specified workflow. |
| Data / Dependencies | Learning Service; potential Social reputation is an unresolved boundary. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook rows 100–105. |
| Open Questions | OQ-026, OQ-027, OQ-028. |

### FR-LEARN-004 — Hồ sơ học tập, câu hỏi và nội dung đã lưu

| Field | Specification |
| --- | --- |
| Description / Actors | User views own education profile, own questions, accumulated coins and saved questions/content. |
| Preconditions / Trigger | Authenticated user opens Edu Profile or sidebar items. |
| Main Flow | Display basic info, post/question count, coin balance and own questions; saved-question action replaces central content with saved items. |
| Alternative Flows / Validation | Definition of “post” in Edu profile, public/private profile, pagination and unsave unknown. |
| Business Rules | Workbook requires name, number of posts, accumulated coins and own questions. |
| Input / Output / Permissions / Errors | Read-oriented; DTO, access policy and errors unknown. |
| Side effects / Notifications / Async | None documented. |
| Data / Dependencies | Learning and identity profile; point source ownership unresolved. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook rows 77–80 and 107–110. |
| Open Questions | OQ-028, OQ-029. |

### FR-DOC-001 — Thư viện, tải lên và tải xuống tài liệu

| Field | Specification |
| --- | --- |
| Description / Actors | Employee browses discipline-relevant documents; permitted actor uploads a document with title/category and downloads a selected document. |
| Preconditions / Trigger | User opens Library. Upload authority, storage provider and document availability policy unknown. |
| Main Flow | (1) Browse/filter/search documents. (2) Optionally upload category, title and file. (3) Validate file. (4) Persist metadata/store binary. (5) Display document card. (6) Download selected file. |
| Alternative Flows | Virus scan, duplicate file, large file, preview/loading page, versioning, delete/revoke, failed download, processing status and retention unknown. |
| Business Rules | Upload accepts `docx` or `pdf`; side menu follows work discipline; card displays name, topic-category, creator, date, vote count. Clicking document downloads it; workbook also says a new “loading” screen, so behavior conflicts. |
| Input / Output / Validation | Title, topic, category, attachment; max size/MIME content validation/response schema unknown. |
| Permissions / Errors | Upload/download/department authorization and errors unknown. |
| Side effects / Notifications / Async | Document ingestion to AI/KG is a documented objective, but trigger, curation and user-visible state are not defined. |
| Data / Dependencies | Learning metadata; object storage; AI Knowledge target pipeline; Identity/department. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook rows 112–119; learning and AI READMEs; `Mô tả đề tài.docx`. |
| Open Questions | OQ-030, OQ-031, OQ-032. |

### FR-GAME-001 — Thống kê xu/điểm và reputation

| Field | Specification |
| --- | --- |
| Description / Actors | User sees coin/point statistics; administrator may see other people’s points. Social proposal also names upvote-driven reputation. |
| Preconditions / Trigger | User opens Statistics. Role model and point ledger do not exist. |
| Main Flow | Display coin balance/conversion/montly statistics. Admin view exposes other-user point tracking. |
| Alternative Flows | Earning/reversal/expiry/conversion/payout/refund and audit adjustments are unknown. |
| Business Rules | Workbook says “kiểm soát số xu, quy đổi và tính toán số tiền, thống kê theo tháng”; Social README assigns upvote/reputation to Social. |
| Input / Output / Validation / Permissions / Errors | All data model, formulas, RBAC and response rules are `TODO / SPEC_GAP`. |
| Side effects / Notifications / Async | Recalculation/eventing unknown. |
| Data / Dependencies | Learning vs Social ownership is a boundary conflict requiring decision. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: workbook rows 121–124; `services/social-service/README.md`. |
| Open Questions | OQ-029, OQ-033. |

## AI and Mobile

### FR-AI-001 — Trích xuất tri thức và curation từ tài liệu

| Field | Specification |
| --- | --- |
| Description / Actors | AI processes internal documents/content into proposed knowledge nodes/relations; an administrator/expert reviews before graph persistence. |
| Preconditions / Trigger | A source document/content and permission-safe access exist. Trigger candidates (upload/new post/event) are described but not approved contractually. |
| Main Flow | Proposed flow: extract text -> normalize/chunk -> extract tags/entities/relations -> create curation proposal -> human approve/edit -> persist graph. |
| Alternative Flows | OCR failures, unsafe/unsupported content, duplicate node, low confidence, model failure, retry, human rejection, graph rollback, reprocess and deletion sync unknown. |
| Business Rules | Human curation before official Neo4j writes is documented. The proposal documents Vietnamese NLP and keyword/LLM options, but none is selected/implemented. |
| Input / Output / Validation | Accepted source types include document/post conceptually; canonical extraction payload/node relation schema/confidence threshold unknown. |
| Permissions / Errors | Curator role, source authorization propagation and operator errors unknown. |
| Side effects / Notifications / Async | Async pipeline is intended; RabbitMQ/document events/job lifecycle `NOT_FOUND`. |
| Data / Dependencies | AI Knowledge MySQL + Neo4j target; Learning/Social source; storage; optional LLM/embedding. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: AI README; `Mô tả đề tài.docx` section 2.2.1; `knowledge_graph_detail.md`. Standalone mock is not implementation. |
| Open Questions | OQ-031 through OQ-039. |

### FR-AI-002 — Hybrid Search, Expert Finder, gợi ý học tập

| Field | Specification |
| --- | --- |
| Description / Actors | Employee searches knowledge, receives semantically/graph-related results, expert candidates and learning recommendations. |
| Preconditions / Trigger | Curated/indexed knowledge graph and access-aware source corpus exist; neither exists. Trigger: search/question/learning activity. |
| Main Flow | Target concept: obtain query -> vector/semantic and graph retrieval -> combine/rank results -> show documents/Q&A/experts; recommendation derives from graph and learning activity. |
| Alternative Flows | No result, stale index, unavailable graph/provider, access-filtered result, low confidence and feedback loop unknown. |
| Business Rules | Documents describe Hybrid Search as Vector Search + Graph Search and Expert Finder based on reputation; ranking/formula/consent not defined. |
| Input / Output / Validation | Query/filters/result schema/citations and evaluation measures unknown. |
| Permissions / Errors | Must enforce source-document authorization, but mechanism not designed. |
| Side effects / Notifications / Async | Expert Finder may notify an expert; trigger/opt-in/rate limiting unknown. |
| Data / Dependencies | AI Knowledge, Neo4j, AI metadata/chunks, Learning/Social reputation, Notification. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: AI README; proposal documents; `14-knowledge-graph-explorer.html` and `15-hybrid-search.html` are placeholders. |
| Open Questions | OQ-034 through OQ-040. |

### FR-MOBILE-001 — Mobile Newsfeed và Messenger

| Field | Specification |
| --- | --- |
| Description / Actors | Native Android employee client initially exposes Newsfeed home and Messenger; receives push when implemented. |
| Preconditions / Trigger | Installed authenticated mobile app; there is no app source. |
| Main Flow | Target client calls Gateway, loads feed/chat and receives mobile push. Detailed screens, offline behavior and API contract are unknown. |
| Alternative Flows / Validation / Permissions / Errors | All are `TODO / SPEC_GAP`. |
| Business Rules | Workbook names two main mobile screens; architecture says client calls Gateway only. |
| Side effects / Notifications / Async | Push via Firebase is a proposal/documented option, not integrated. |
| Data / Dependencies | Gateway, Social, Chat, Notification. |
| Implementation Status / Evidence | `DOCUMENTED_NOT_IMPLEMENTED`: `apps/mobile/README.md`; workbook rows 126–129. |
| Open Questions | OQ-041, OQ-042. |

## Cross-cutting Functional Gaps

No source verifies account lifecycle, RBAC matrix, department visibility, audit trail, pagination convention, error contract, event contract, notification taxonomy, file scanning, data-retention or accessibility requirements. These gaps must be closed through the change workflow before a feature is coded.
