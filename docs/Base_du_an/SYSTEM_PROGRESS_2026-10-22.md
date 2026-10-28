# Tiến độ đồ án — cập nhật 22/10/2026

Đồ án đã có nền tảng và API nghiệp vụ ở các service chính, cùng bản demo mobile. Công việc trọng tâm tiếp theo là nối giao diện vào API, hoàn thiện cấu hình chạy thực tế và nghiệm thu các luồng xuyên hệ thống. **Chưa nghiệm thu toàn bộ đồ án; chưa có cơ sở quy đổi thành phần trăm hoàn thành.**

Bản cập nhật này đối chiếu tài liệu API, mã nguồn và ghi nhận kiểm tra hiện có. Kết quả API dưới đây thuộc đợt 21/10; kết quả mobile thuộc ghi nhận 19–21/10. Ngày 22/10 cập nhật tài liệu, không chạy lại toàn bộ các bộ kiểm thử.

## Hiện trạng theo hạng mục

| Hạng mục | Đã có | Còn lại để nghiệm thu |
|---|---|---|
| Phân tích, thiết kế | Baseline, đặc tả, phân chia microservice, thiết kế CSDL và hợp đồng API | Đồng bộ chương báo cáo, Use Case, sơ đồ và ma trận yêu cầu–bằng chứng với triển khai mới; chưa xác nhận báo cáo hoàn chỉnh |
| Identity và quản trị | Auth/session, đổi mật khẩu, quản trị tài khoản, profile, danh bạ; phân quyền theo phạm vi và quyền đọc tài liệu trong source | Rà quyền từng vai trò trên giao diện và kiểm tra xuyên tổ chức; không coi mọi trang Admin đã nối các API mới |
| Social | Backend bài viết/feed/comment/reaction/save/report/moderation; bổ sung follow, pin, event/RSVP, xác nhận thông báo, quản lý kênh và attachment private | Nối Web/Admin/Mobile theo từng luồng; áp dụng migration local còn vướng DB; kiểm tra storage/scanner thật. Story mobile vẫn là demo |
| Tri thức | Tài liệu/version/review/publish/access control; Q&A, vote, best answer, comment, follow/bookmark; API thưởng và duyệt kỳ | Nối đầy đủ UI; nghiệm thu upload → quét → duyệt → công bố → đọc/tải theo quyền, và hỏi/đáp → thưởng → duyệt kỳ |
| Points/coin | Rule do Admin sửa; tích lũy pending; kỳ tuần/tháng; duyệt vào wallet/ledger; hủy pending khi bỏ vote hoặc nội dung bị xóa/ẩn | UI quản trị/thống kê và kiểm thử luồng thật. Chưa có quy đổi tiền, payout hoặc bounty settlement |
| Chat | API direct/group trên MongoDB, lịch sử, gửi chống trùng, read/unread, quản lý nhóm và attachment private | Cấu hình Mongo replica set/indexes cho runtime; nối giao diện, storage và scanner; nghiệm thu nhiều người dùng |
| Notification/realtime | API thông báo/preferences/devices/broadcast; outbox RabbitMQ và consumer; adapter FCM; WebSocket có kiểm tra session/quyền | Sửa kết nối DB local, chạy migration; kiểm chứng broker và FCM thật. WebSocket hiện kiểm tra service mỗi 5 giây, chưa phải fanout quy mô lớn |
| AI và Knowledge Graph | Base OpenAI Responses, cấu hình key/model; summary/concepts theo quyền; tìm kiếm keyword; graph demo | Chưa gọi provider thật; chưa có pipeline ingestion/extraction/embedding, hybrid search hay graph Neo4j được cập nhật từ AI; cần đánh giá chất lượng |
| Web/Admin | Có source giao diện, auth và các luồng tài liệu/quản trị; còn màn dùng mock | Nối các endpoint mới, rà trạng thái lỗi/rỗng/quyền; chạy browser E2E. Không suy ra UI hoàn tất từ việc backend đã có route |
| Mobile | React Native/Expo/TypeScript; demo Social, kênh, tìm kiếm, chat/thông báo local, media và Story; có client Gateway cho một số nghiệp vụ | Chat/thông báo/media/Story chưa tích hợp server đầy đủ; chưa nghiệm thu API thật hay chạy thiết bị native; chưa có APK/IPA được xác nhận |
| Kiểm thử, triển khai, bàn giao | Kiểm thử thành phần, hợp đồng API, hướng dẫn local và ví dụ request | E2E toàn hệ thống, cài từ môi trường sạch, thiết bị mobile, dữ liệu demo, báo cáo kết quả, slide và gói bàn giao |

## Bằng chứng đã ghi nhận

Đợt API bổ sung **77 operation REST qua Gateway**. Tổng kiểm kê hiện có **150 operation Gateway có decorator path**, không tính WebSocket; số lượng route không phải tỷ lệ hoàn thành. Các route health/nội bộ ở service có cách đếm riêng.

| Thành phần | Kết quả đợt API 21/10 |
|---|---|
| Identity | 20 test / 140 assertion đạt |
| Social | 16 test / 179 assertion đạt |
| Learning | 13 test / 168 assertion đạt |
| Notification | 6 test / 63 assertion đạt |
| Chat | 3 test / 42 assertion đạt trên MongoDB replica set kiểm thử riêng |
| AI | 4 test đạt, provider giả lập |
| WebSocket | 1 test đạt với socket thật và domain client giả lập |
| Gateway/hợp đồng | Build đạt; 8 hợp đồng OpenAPI hợp lệ |

Tổng **63 test đạt**, gồm 592 assertion PHP; kiểm tra cú pháp 298 file PHP. Phần lớn test Laravel dùng SQLite/storage giả lập; Chat dùng MongoDB kiểm thử riêng. Kết quả này không thay cho nghiệm thu hạ tầng thật.

Mobile ghi nhận sau đồng bộ ngày 21/10: typecheck/lint đạt, Expo Doctor 21/21, export bundle Android/iOS/web đạt. Các luồng demo đã được kiểm tra browser theo [README mobile](../../apps/mobile/README.md); export bundle không phải bản cài native. Cảnh báo dependency mobile được ghi riêng tại đó, chưa được giải quyết bởi đợt cập nhật dependency backend.

## Các quy tắc đã chốt

- Social và Tri thức là hai kênh nghiệp vụ riêng; chỉ tương tác Tri thức thuộc cơ chế thưởng học tập.
- Admin cấu hình points/coin theo cơ chế, ví dụ 2 coin/upvote, có giới hạn theo topic/ngày. Mức thưởng được lưu tại thời điểm tương tác.
- Điểm mới chờ duyệt theo kỳ tuần hoặc tháng; chỉ sau khi Admin duyệt mới cộng ví. Lịch sử đã duyệt giữ nguyên, không cộng lại khi duyệt lại.
- Bỏ vote hoặc xóa/ẩn nội dung trước khi duyệt sẽ loại khoản pending, kể cả thay đổi sau khi lập draft kỳ; xóa/ẩn question ảnh hưởng khoản pending của answer bên dưới.
- Course/Lesson tiếp tục hoãn theo yêu cầu chủ dự án.
- AI đã làm base, mặc định model cấu hình trong môi trường; cần thêm key để gọi thật. Không gửi chat/private draft; phần AI/KG nâng cao vẫn cần triển khai riêng.

## Vướng mắc thực tế

1. MySQL local của Social và Notification từ chối tài khoản `app` tại `127.0.0.1:3306`; migration mới của hai service chưa áp dụng được. Identity đã thêm permission; Learning đã áp dụng ba migration ngày 21/10.
2. Chat runtime cần MongoDB replica set và indexes. Test thành công trên replica set riêng không chứng minh cấu hình runtime dự án đã hoàn tất.
3. Cần cấu hình MinIO/S3 và scanner thật. Hiện có API callback quét đã xác thực; chưa có antivirus worker để kết luận tệp sạch.
4. Chưa kiểm chứng RabbitMQ publish/consume trên broker thật; chưa gọi OpenAI hay gửi FCM bằng credentials thật.
5. UI còn demo/mock hoặc chưa gọi API mới. Chưa có bằng chứng E2E Web/Admin/Mobile đầy đủ, native device và cài mới toàn hệ thống.

## Thứ tự công việc tiếp theo

| Ưu tiên | Công việc | Điều kiện hoàn tất |
|---|---|---|
| 1 | Hoàn thiện cấu hình DB, migration, Mongo, storage và worker local | Các service khởi động, đọc/ghi DB; file được scan; event publish/consume có bằng chứng |
| 2 | Nối Web/Admin vào API Social, Tri thức, thưởng, Chat/Notification | Thao tác lưu thật; tải lại vẫn đúng; kiểm tra quyền và lỗi bằng tài khoản riêng |
| 3 | Nối Mobile vào cùng API và kiểm tra native | Login/feed/tương tác/chat/thông báo theo phạm vi MVP trên thiết bị; phân biệt rõ tính năng demo |
| 4 | Hoàn thiện AI/KG thuộc đầu ra đồ án | Có dữ liệu và nguồn/version thật; quy trình duyệt, truy vấn có quyền và bộ đánh giá chất lượng |
| 5 | Nghiệm thu và hoàn thiện báo cáo song song | Ma trận yêu cầu–bằng chứng, E2E, cài sạch, hướng dẫn, bản demo, báo cáo và slide |

Thời gian chính thức: **05/10/2026–18/01/2027**. Mốc 03/01/2027 trong kế hoạch cũ là mục tiêu nội bộ đề xuất, chưa phải cam kết mới. Các mốc 18/10 và 22/10 của bản kế hoạch cũ chưa đủ bằng chứng để tự đánh dấu hoàn thành; cần đối soát hồ sơ báo cáo riêng.

## Tài liệu chi tiết

- [Coverage API, cấu hình và giới hạn kiểm chứng](../api/API_COVERAGE_2026-10-21.md)
- [Ví dụ request và luồng duyệt thưởng](../api/REQUEST_EXAMPLES.md)
- [Kiểm kê route](../api/ROUTE_INVENTORY.md)
- [Ví dụ phân quyền theo phạm vi](../security/SCOPED_RBAC_EXAMPLES.md)
- [Tracker và nhật ký tiến độ](IMPLEMENTATION_PROGRESS.md)
- [Các phase và trạng thái từng task](../specs/README.md)

Các báo cáo ngày 13/10 và 14/10 được giữ làm lịch sử. Bản tổng hợp này không tự chuyển task phase sang DONE khi chưa đủ tiêu chí nghiệm thu của task đó.
