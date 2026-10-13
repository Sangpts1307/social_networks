# 11 — Ma trận giao diện/chức năng → dữ liệu

> **Mục đích:** kiểm tra nhanh rằng thiết kế database bao phủ màn hình mới và yêu cầu dự án, đồng thời chỉ ra phần UI chưa đủ để quyết định schema.  
> **Lưu ý:** “Bao phủ” nghĩa là có thiết kế PROPOSED cho dữ liệu cần thiết, không có nghĩa chức năng đã được code.

## 1. Identity và quản trị

| UI/chức năng quan sát được | Owner | Bảng/collection chính | Điểm cần chốt |
|---|---|---|---|
| Welcome, login, đăng ký corporate email/mã nhân viên/mật khẩu | Identity | `users`, `user_profiles`, `organization_email_domains`, `user_sessions` | Password hash và login metadata nằm trong `users`; không có bảng credential, identity hay activation riêng. |
| Cập nhật profile, avatar, phone, birth date, address, gender | Identity + object storage | `user_profiles`; metadata avatar (cần thêm field/object policy nếu triển khai) | retention/visibility của PII, file scan/avatar storage |
| Quản lý user, lock sau sai mật khẩu, bulk action | Identity | `users`, `user_sessions`, `user_import_jobs`, `user_import_rows`, `audit_events` | lockout threshold/window, approval khi unblock/export |
| Import XLSX/CSV, provisioning | Identity | `user_import_jobs`, `user_import_rows`, `users`, `org_unit_memberships` | template, duplicate rule, error retention/raw file handling |
| Phòng ban/cây team | Identity | `organizations`, `org_units`, `org_unit_memberships` | một membership primary hay nhiều; quyền kế thừa theo cây? |
| Role/permission matrix | Identity | `roles`, `permissions`, `role_permissions`, `user_role_assignments` | role global hay scope theo phòng ban/channel/topic? |
| Audit log quản trị | owner service + Identity read projection | `audit_events` và outbox/event của từng service | central projection chỉ đọc, retention/legal hold |
| System settings | Identity/config owner | `organization_settings` | field nào là config có version/approval thay vì JSON tùy ý |

## 2. Inside / Social

| UI/chức năng quan sát được | Owner | Bảng chính | Điểm cần chốt |
|---|---|---|---|
| Feed, kênh công ty/phòng ban/dự án | Social | `social_channels`, `channel_members`, `posts`, `post_stats` | visibility/membership rule và kênh org-unit tự đồng bộ thế nào |
| Post, ảnh/video/file/link, draft/schedule | Social + object storage | `posts`, `post_attachments`, `outbox_events` | rich-text format, file quota/type/scan, scheduled publish worker |
| News, announcement, event | Social | `posts`, `social_categories`, `social_post_categories`, `post_events`, `announcement_receipts` | announcement bắt buộc acknowledgment hay chỉ seen? |
| Like/reaction/comment/save/pin | Social | `post_reactions`, `post_comments`, `post_saves`, `post_pins`, `post_stats` | giới hạn depth comment, exact reaction vocabulary, pin scope |
| Social profile, follower/following | Social + Identity profile | `user_follows` + identity directory projection | follow không đồng nghĩa friend/direct chat permission |
| Report/risk/moderation queue | Social | `post_reports`, `moderation_actions`, post/comment status | AI flag vs human decision, SLA/reason code, escalation |

## 3. Base Square / Learning

| UI/chức năng quan sát được | Owner | Bảng chính | Điểm cần chốt |
|---|---|---|---|
| Topic/category/SME | Learning | `knowledge_topics`, `knowledge_categories`, `topic_experts` | taxonomy owner, hierarchy depth, expert assignment policy |
| Ask/list/detail Q&A | Learning | `questions`, `answers`, `qa_votes`, `question_follows`, `question_bookmarks`, `question_assignments` | downvote có hay không, anonymous question có hay không, comments distinct from answers? |
| Best answer/resolved | Learning | `questions.accepted_answer_id`, `answers.is_best_answer`, `reward_ledger_entries` | ai/author/admin nào được accept; reversal khi answer bị remove |
| Bounty | Learning | `question_bounties`, `reward_wallets`, `reward_ledger_entries` | coin có giá trị tài chính hay chỉ gamification; expiry/refund |
| Document library/detail | Learning + object storage | `documents`, `document_versions`, `document_files`, `document_access_rules`, `document_access_events` | access precedence, types/size/quota, preview/virus scan |
| Document review/version/follow/vote | Learning | `document_reviews`, `document_versions`, `document_follows`, `document_votes` | approval workflow và version retention |
| Chuyển best answer thành document | Learning | `documents.source_type/source_id`, version/file/content fields | copy/snapshot hay reference source; owner/reviewer/permission kế thừa |
| Course/lesson/progress | Learning | `courses`, `course_modules`, `lessons`, `course_enrollments`, `lesson_progress` | UI bằng chứng hiện còn mỏng; scope ngoài MVP nếu chưa có flow |
| Leaderboard points/coins | Learning | `reward_rules`, `reward_ledger_entries`, `reward_wallets`, `leaderboard_snapshots` | rule/rank/period/anti-abuse; owner ledger chỉ một service |

## 4. Messenger, notification, AI/Knowledge

| UI/chức năng quan sát được | Owner | Bảng/collection chính | Điểm cần chốt |
|---|---|---|---|
| Direct/group/project chat, unread/read, attachment | Chat + object storage | `conversations`, `conversation_members`, `messages`, `chat_read_models` | direct-chat identity, member roles, edit/delete, retention/attachment policy |
| Realtime incoming message & alerts | Chat/Notification | Chat outbox → `notifications`, `notification_deliveries`, `user_devices`, preferences | delivery channels, quiet hours, duplicate suppression |
| Notification inbox/broadcast/email/push | Notification | `notification_templates`, `notifications`, `broadcasts`, `broadcast_targets`, `broadcast_recipient_runs` | audience query snapshot, recipient privacy, delivery retry/retention |
| Hybrid search filters/results | AI/Knowledge | `ai_source_snapshots`, `ai_content_chunks`, `embedding_index_references`, external lexical/vector index | Vietnamese relevance evaluation, permission filter at query time |
| Knowledge graph Explorer | AI/Knowledge + Neo4j | Neo4j nodes/relations, `knowledge_*_proposals`, `curation_reviews`, provenance | AI proposal vs curated fact, write rights, deletion propagation |
| Expert Finder | AI/Knowledge + Learning | `topic_experts`, `expertise_projections`, graph `EXPERTISE_IN` | explicit SME vs algorithmic ranking and explanation/opt-out |
| AI risk scan | AI/Knowledge + source owner | `content_risk_assessments`, source moderation/report tables | model/provider/data egress, threshold/human review, raw content retention |

## 5. Coverage gaps phát hiện từ UI

Những item sau được thấy hoặc ám chỉ trong mockup nhưng chưa đủ chi tiết để chốt migration; chúng được cố ý ghi là decision thay vì bịa field:

1. **Avatar**: Identity có profile nhưng cần quyết định cột `avatar_object_key`, thumbnail, scan/delete lifecycle và quyền xem.
2. **Contacts/friend graph**: Chat cần người nhận direct chat, còn Social chỉ xác nhận follow. Chưa có bằng chứng cho friend request/block list; không thêm `friendships` nếu chưa chốt.
3. **Event RSVP**: UI có action event nhưng chưa mô tả attendance flow. `post_events` có metadata; thêm `event_rsvps` khi flow được duyệt.
4. **Search engine/vector store**: UI chỉ hiển thị behavior, không xác nhận vendor/schema/vector dimension/region.
5. **Reporting/analytics**: Admin dashboard có metric nhưng không cho biết formula/latency. Nên dùng read model/snapshot trước, không thêm warehouse table transaction tùy tiện.
6. **Course authoring và assessment**: có learning concept nhưng UI hiện thiên về Q&A/document; quiz/certificate/submission chưa đủ evidence.
7. **Task/project**: chat UI nhắc project group nhưng không xác nhận một Project domain độc lập. Chỉ dùng conversation metadata cho đến khi có module quản lý project.

## 6. Kết luận coverage

Core data model đã bao phủ các module nhìn thấy: account/admin, Inside, Base Square, chat, notification và AI/Knowledge. Các khoảng trống không phải thiếu bảng một cách ngẫu nhiên; chúng là product/security decisions cần chốt ở [10 — Quyết định mở](10-open-decisions.md). Khi một flow được duyệt, bổ sung bảng nhỏ có owner rõ thay vì nới rộng JSON hay tạo shared database.
