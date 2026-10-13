# Baseline triển khai v0.1 — ĐÃ CHỐT

> **Ngày chốt:** 2026-09-12; cập nhật cách chạy ngày 2026-09-16  
> **Nguồn quyết định:** phản hồi của chủ dự án trong [IMPLEMENTATION_CONFIRMATION.md](IMPLEMENTATION_CONFIRMATION.md).  
> **Hiệu lực:** Đây là baseline bắt buộc cho scaffold, database migration, API, frontend và test cho đến khi chủ dự án yêu cầu thay đổi rõ ràng. Nếu tài liệu đề xuất cũ mâu thuẫn với file này, file này được ưu tiên.

## 1. Phạm vi triển khai hiện tại

### Phase 0 — làm trước

1. Web application: Vue.js cho Web Admin và Web người dùng.
2. Identity Service: login, tài khoản, profile, phòng ban, RBAC và Admin quản lý user/phòng ban.
3. Demo Cây tri thức: có luồng liên kết tối thiểu giữa user, tài liệu và tri thức để kiểm tra kiến trúc graph/search trước.
4. Foundation: Laravel service skeleton, REST API, môi trường local trực tiếp và migration/test/documentation trước; đóng gói Docker sau khi local đạt kiểm tra.

### Chưa cam kết triển khai ngay

Social đầy đủ, Learning Q&A đầy đủ, Notification, Chat và AI production sẽ được mở theo từng module sau Phase 0. Android không làm trong đợt đầu.

Course/Lesson bị hoãn. Coin quy đổi tiền thưởng, bounty settlement, AI provider thật và production deployment cũng bị hoãn cho đến khi có quyết định nghiệp vụ/hạ tầng cụ thể.

## 2. Kiến trúc và công nghệ đã chốt

| Hạng mục | Quyết định |
|---|---|
| Kiến trúc | Các service độc lập: Identity, Social, Learning, Chat, Notification, AI Knowledge; Gateway/BFF là entry point của Web. Chỉ khởi tạo service cần cho phase đang làm. |
| Backend | Laravel cho Identity, Social, Learning, Notification và Chat. |
| Frontend | Vue.js, Web trước. |
| Mobile | Hoãn; sẽ chọn Kotlin, React Native hoặc Flutter sau. |
| API | REST; Chat dùng WebSocket cho realtime. |
| Database | MySQL cho Identity/Social/Learning/Notification/AI metadata; MongoDB cho Chat; Neo4j cho Cây tri thức; Redis chỉ cache/realtime; file binary ở object storage. |
| Dev environment | Hai cách chạy: (1) ứng dụng và dịch vụ dữ liệu chạy trực tiếp trên máy đã cài môi trường, ưu tiên kiểm tra cách này trước; (2) build ứng dụng và chạy bằng Docker sau. Docker không là điều kiện bắt đầu local. Tận dụng môi trường có sẵn, chỉ xử lý thành phần thiếu sau khi kiểm tra. |
| Deploy | Local/dev trước; production sau trên AWS hoặc server riêng. Domain/SSL chưa chốt. |

## 3. Identity, account và quyền

| Chủ đề | Quyết định đã chốt |
|---|---|
| Tạo account | Chỉ Admin import/tạo account. Không tự đăng ký công khai. Admin cấp mật khẩu ban đầu; user buộc đổi mật khẩu ở lần đăng nhập đầu. |
| Login | Chỉ bằng corporate email + password. Không dùng mã nhân viên để đăng nhập trong baseline này. |
| Credential schema | Password hash, password version, password-change state, failed-login count và lock state nằm trong `identity_db.users`. `user_profiles` chỉ giữ profile/PII. Không có bảng credential/identity/activation riêng. |
| OTP | Không có OTP, activation challenge hay self-registration verification flow. |
| Lock account | Sau 10 lần đăng nhập sai liên tiếp, account bị khóa cho đến khi Admin mở khóa. |
| Org structure | Một user có một phòng ban chính và có thể thuộc thêm nhiều team; phòng ban là cây cha-con. |
| Roles | Tạo sẵn: `super_admin`, `hr_manager`, `team_leader`, `qa_expert`, `member`. `super_admin` có toàn quyền; `hr_manager` có quyền liên quan nhân sự. Permission chi tiết của các role còn lại được khai báo rõ ở module tương ứng, không suy ra chỉ từ tên role. |
| Audit | Chỉ `super_admin` được xem audit log trong baseline. |

### Default an toàn cho PII

Chủ dự án chưa chỉ định đối tượng xem phone, date of birth và address. Trước khi có thay đổi, API chỉ cho **chính chủ profile** và `super_admin` xem các trường PII này; public/internal profile chỉ trả display name, job title, org unit và avatar. Đây là default bảo mật, không phải mở rộng tính năng.

## 4. Social / Inside — phạm vi khi mở module

| Hạng mục | Quyết định đã chốt |
|---|---|
| Channel | Có channel toàn công ty, phòng ban, dự án/custom; view/post theo membership. |
| Post | Có post text, attachment ảnh/file, news, announcement và event. Không có poll/vote trong Social. |
| Event | Có RSVP. Khi bắt đầu module, thêm `event_rsvps` thuộc Social; không tái sử dụng bảng poll. |
| Interaction | Có comment và reply comment, reaction, save post, follow user. Reaction gồm `like`, `love`, `sad`, `angry`. |
| Moderation | Có report/moderation. Post thường publish ngay; news/announcement dùng permission đặc biệt; xóa/ẩn là soft delete có audit; attachment scan trước khi public. |
| Quyền đặc biệt | Chỉ role được cấp permission mới publish announcement/news, pin hoặc moderation; không hard-code theo tên role. |

## 5. Learning / Base Square — phạm vi khi mở module

| Hạng mục | Quyết định đã chốt |
|---|---|
| Q&A | Có topic/category, question, answer, comment, upvote và downvote. Không có anonymous question. |
| Best answer | Không chọn thủ công. UI/API tự xác định best answer bằng điểm `upvote - downvote` cao nhất trong các answer đang published; nếu hòa, ưu tiên answer tạo sớm hơn, sau đó UUID nhỏ hơn để kết quả ổn định. Đây là giá trị dẫn xuất, không là quyền moderation riêng. |
| Document | Chỉ `super_admin`, `hr_manager`, `team_leader` được upload trong baseline. `super_admin` duyệt publish. Tất cả user đã đăng nhập được xem tài liệu published; chưa cần ACL theo org-unit/role/user cho phase đầu. |
| File | Lưu binary local object storage trong phase dev; type/size quota sẽ cấu hình khi code module file. File phải qua scan trước publish. |
| Course/Lesson | Hoãn; chỉ demo Cây tri thức trước. |
| Leaderboard | Có nhưng làm sau core Q&A/document. |
| Coin và bounty | Coin được định hướng quy đổi thành tiền thưởng và Q&A có bounty, nhưng **không triển khai payout, redemption hoặc settlement ở phase hiện tại**. Chỉ thiết kế/migrate ledger khi rule cộng-trừ, approval, chống gian lận và quy trình quy đổi được chốt. |

## 6. Chat và Notification — phạm vi khi mở module

| Hạng mục | Quyết định đã chốt |
|---|---|
| Chat | Có direct chat, group chat, text, attachment, unread/read và lưu lịch sử bền vững trong `chat_db` MongoDB. |
| Chat ngoài scope | Không edit/delete message ở baseline; không call/video. Attachment type/size được cấu hình khi làm upload. |
| Notification | Có in-app và push; không gửi email. Push tích hợp FCM bằng key chủ dự án đã có khi module được triển khai. |
| Notification events | Comment, answer, mention, tài liệu được duyệt, chat message và company announcement. Phải chống gửi trùng bằng event id/idempotency key. |

## 7. AI, Search và Cây tri thức

| Hạng mục | Quyết định đã chốt |
|---|---|
| Demo sớm | Làm demo Cây tri thức trong Phase 0 để thể hiện liên kết user, tài liệu và concept/tri thức. |
| AI production | Chưa chốt provider, model, region, budget hoặc dữ liệu được gửi ra bên thứ ba. Không tích hợp LLM/embedding provider thật cho đến khi các mục này được xác nhận. |
| Dữ liệu được index khi AI production mở | Chỉ Q&A và tài liệu đã publish, sau khi kiểm tra quyền truy cập. |
| Dữ liệu bị cấm index | Chat, draft và dữ liệu restricted/confidential. |
| Moderation AI | AI chỉ tạo đề xuất/risk assessment; không tự xóa/ẩn nội dung. |

Demo graph có thể dùng data seed/deterministic mapping từ user, document và concept để chứng minh luồng; không được mô tả là kết quả AI production.

## 8. Vòng đời dữ liệu và nghiệm thu

| Hạng mục | Quyết định đã chốt |
|---|---|
| Xóa dữ liệu | Tài liệu/resource hỗ trợ soft delete; `super_admin` có thể khôi phục. Không hard delete mặc định. |
| Chat history | Lưu trong database bền vững; retention cụ thể để sau. |
| Export | Chưa làm export user/document/audit trong baseline. |
| Done criteria | Mỗi module có migration, API documentation, unit/integration test, UI test luồng chính và authorization negative test. Ghi bằng chứng local trước; Docker build/run có task và bằng chứng riêng. Module chạy local không đồng nghĩa đã nghiệm thu Docker. |

## 9. Quy tắc thay đổi baseline

1. Không tự thêm module, table hay external provider ngoài baseline này.
2. Khi chủ dự án yêu cầu thay đổi trong lúc code, cập nhật file này trước hoặc cùng PR/change set; nêu rõ ảnh hưởng database/API/test.
3. Các mục “hoãn” không phải bị loại bỏ vĩnh viễn; chúng cần một quyết định mới trước khi code.
4. Nếu chưa có quy tắc chi tiết, dùng default an toàn ít quyền nhất, có cấu hình để thay đổi sau, và ghi nhận rõ trong implementation note.

## 10. Tài liệu liên quan cần tuân thủ

- [Database design](../database/README.md)
- [Identity database](../database/01-identity-service-mysql.md)
- [Social database](../database/02-social-service-mysql.md)
- [Learning database](../database/03-learning-service-mysql.md)
- [AI/Knowledge database](../database/06-ai-knowledge-mysql-neo4j.md)
- [Hợp đồng liên service](../database/07-cross-service-data-contracts.md)
- [Project specification](../specs/PROJECT_SPEC.md)

## 11. Thứ tự kiểm chứng môi trường (2026-09-16)

1. Kiểm kê runtime và dịch vụ local đã cài; kiểm tra cấu hình host/port thực tế.
2. Chạy Web, Gateway và service cần cho task trực tiếp trên máy; không yêu cầu bật tất cả module.
3. Kiểm tra kết nối, migration trên database kiểm thử riêng, health và luồng nghiệp vụ local. Không reset/xóa dữ liệu hiện có.
4. Sau khi local đạt, build image và kiểm tra Docker với cấu hình/volume riêng. Compose chỉ chạy hạ tầng chưa đủ chứng minh ứng dụng đã được đóng gói.
5. Ghi kết quả hai cách chạy riêng; không đổi task sang DONE nếu còn thiếu tiêu chí của chính task đó.

Hướng dẫn: [Local](LOCAL_DEVELOPMENT.md), [Docker](DOCKER_DEVELOPMENT.md).

## 12. Ghi chú triển khai auth local — 2026-09-17

Theo yêu cầu triển khai auth/session và protected Gateway, default kỹ thuật local hiện dùng opaque Bearer session (hash trong DB), introspection qua Identity, service key riêng Gateway → Identity và thời hạn 480 phút. JWT vẫn là hướng ưu tiên kiến trúc về sau, chưa được triển khai. Password policy và các giới hạn được ghi tại [ADR-002](../specs/adr/ADR-002-authentication-strategy.md); đây không phải phê duyệt production. Admin unlock/RBAC còn task riêng.
