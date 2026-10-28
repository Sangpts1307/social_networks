# Tổng hợp hoạt động và tiến độ hệ thống — 14/10/2026

> Bản lịch sử tại ngày ghi trên tiêu đề. Xem [tiến độ hiện tại 22/10/2026](SYSTEM_PROGRESS_2026-10-22.md).

## Phạm vi xác minh

Đối chiếu source, routes, giao diện và tài liệu tiến độ. Chạy lại Social: 13 tests/137 assertions PASS; Learning: 5 tests/79 assertions PASS. Đây là kiểm thử thành phần trên môi trường test; không thay cho nghiệm thu trình duyệt, storage thật hoặc toàn hệ thống. Bằng chứng MySQL/Gateway Social ngày 13/10 được kế thừa từ API client guide, chưa chạy lại hôm nay. Admin Identity có bằng chứng nghiệm thu trong tracker ngày 27/09, chưa chạy lại hôm nay.

## Luồng hoạt động

1. **Tài khoản:** quản trị quản lý người dùng/phòng ban/role → người dùng đăng nhập → Identity cấp token phiên → đổi mật khẩu nếu bắt buộc → Web gọi Gateway. Gateway xác minh phiên và chuyển danh tính/quyền đã xác minh tới service; không tin user/organization tự khai từ client. Có logout, logout-all, lock/unlock và audit.
2. **Social:** đăng nhập → xem kênh được phép → tham gia kênh mở → tạo bài/nháp → publish → đọc feed phân trang → comment/reply/reaction/save → report → moderator xử lý bằng API. Backend đã có; Web vẫn dùng mock và Admin Social là placeholder. Attachment, event/RSVP, follow, managed membership và moderation comment còn thiếu.
3. **Hỏi đáp:** đăng nhập → lấy taxonomy → tạo/xem câu hỏi → trả lời → vote → hệ thống suy ra best-answer theo thứ tự vote và tie-break. LearningView đã gọi API cho các thao tác chính; KnowledgeQaView còn dùng mock. Chưa nghiệm thu luồng đầy đủ trên trình duyệt.
4. **Tài liệu:** quản trị taxonomy/metadata → upload file/phiên bản → approve hoặc reject → publish → người có quyền xem/đọc/tải → archive/restore khi cần. Backend có lifecycle, access rules và outbox; Admin có trang/client Learning. Cần xác minh tích hợp UI, storage, scan và download thật; một số tab thư viện trên Web vẫn dùng mẫu.
5. **Tri thức/AI đích:** nguồn tri thức được công bố/cập nhật → outbox → relay/consumer → graph có provenance/version → reviewer duyệt trích xuất → tìm kiếm/hỏi đáp AI có dẫn nguồn. Hiện mới có một phần nguồn dữ liệu/outbox và graph fixture; chưa có chuỗi xử lý thật nối xuyên suốt.

## Mức đáp ứng

| Nhóm yêu cầu | Kết quả hiện có | Đánh giá |
|---|---|---|
| Phân tích và thiết kế | Baseline, functional specs, user flows, database/ERD, OpenAPI, ADR | Có nền tảng; cần cập nhật thành chương báo cáo thống nhất |
| Xác thực và phân quyền | Identity auth/session, đổi mật khẩu, revoke, RBAC, admin users/org/roles/audit | Đã triển khai; bằng chứng cũ, cần E2E hiện tại |
| Mạng xã hội | Kênh, self-membership, bài text/feed/cursor, comments/replies, reactions, saves, reports, post moderation | Backend core đã kiểm thử; chưa hoàn thành sản phẩm Web/Mobile |
| Hỏi đáp | Question/answer/vote/best-answer, scope tổ chức, API và một phần Web thật | MVP backend có; UI/E2E chưa đóng |
| Kho tài liệu | Taxonomy, upload/version/review/lifecycle/access rules/read/download/outbox | Có source và test; chưa nghiệm thu hạ tầng/file thật |
| Knowledge Graph và AI | FastAPI, graph demo fixture, thiết kế pipeline | Chưa đáp ứng KG/AI thực tế |
| Chat và Notification | Routes hiện chỉ health, có khung service | Chưa có nghiệp vụ sử dụng được |
| Android | Chỉ README trong apps/mobile | Chưa triển khai ứng dụng |
| Course/reward/search/gợi ý | Có một phần khung/schema/UI hoặc API course cơ bản | Không coi là chức năng hoàn chỉnh |
| Kiểm thử và bàn giao | Test thành phần, scripts tích hợp, hướng dẫn local | Chưa có nghiệm thu toàn hệ thống/clean install/mobile |

## Việc đã hoàn thành có thể khẳng định

- Đã xây nền tảng kiến trúc, xác thực/phân quyền và quản trị Identity theo bằng chứng tracker.
- Đã triển khai backend Social core dùng chung cho Web/Mobile; chạy lại 13 tests/137 assertions PASS ngày 14/10.
- Đã triển khai các API Q&A và vòng đời tài liệu của Learning; chạy lại 5 tests/79 assertions PASS ngày 14/10.
- Chưa đủ bằng chứng đóng toàn bộ P0/P1/P2 hoặc tuyên bố đồ án hoàn thành. Không quy đổi task DONE thành phần trăm tổng thể.

## Ưu tiên tiếp theo

1. Nghiệm thu browser thật cho login/đổi mật khẩu, hỏi đáp và upload → duyệt → publish → đọc/tải; kiểm tra quyền, token bị thu hồi và khác tổ chức.
2. Nối Social Web và Admin moderation vào API thật; bổ sung các chức năng Social bắt buộc còn thiếu.
3. Xây Android MVP theo cùng contract API; chốt màn hình nghiệm thu.
4. Hoàn thiện storage/scan/download, event relay và consumer idempotent; thay graph fixture bằng Neo4j thật có nguồn và phiên bản.
5. Tích hợp AI có dẫn nguồn, search/gợi ý và bộ dữ liệu đánh giá; chốt phạm vi chat với GVHD.
6. Viết báo cáo và lưu bằng chứng theo từng mốc, sau đó E2E, clean install, dữ liệu demo và bàn giao.

Mốc chính thức theo đối soát phiếu giao nhiệm vụ: 05/10/2026–18/01/2027. Mục tiêu nội bộ đề xuất: 03/01/2027. Chưa có cơ sở xác nhận tỷ lệ hoàn thành hoặc cam kết đạt mốc nội bộ. Báo cáo này cập nhật tiến độ, không tự chuyển task phase sang DONE.

## Nguồn

- `services/identity-service/routes/api.php`
- `services/social-service/routes/api.php`, `docs/specs/phase-1-social/02-api-client-guide.md`
- `services/learning-service/routes/api.php`, các test Feature của Learning
- `apps/web/src/views/LearningView.vue`, `apps/web/src/views/modules/BaseInsideView.vue`, `apps/web/src/views/modules/KnowledgeQaView.vue`
- `services/admin-service/app/Filament/Pages/PostModeration.php`
- `services/ai-knowledge-service/app/services/demo_graph.py`
- `services/chat-service/routes/api.php`, `services/notification-service/routes/api.php`, `apps/mobile/README.md`
- `docs/Base_du_an/THESIS_PROGRESS_2026-10-13.md`, tracker Admin ngày 27/09
