# Rà soát và bổ sung API — 2026-10-21

## Kết luận rà soát

API trước thay đổi chưa đủ. Identity, Social và Learning đã có nghiệp vụ nhưng còn thiếu thao tác; Chat và Notification chỉ có health; AI chỉ có demo graph. Tài liệu `06-api-spec.md` trước đó ghi 0 endpoint đã lỗi thời.

Đã bổ sung **77 REST operation qua Gateway**, đồng thời bổ sung nghiệp vụ vào các service sở hữu dữ liệu. Gateway hiện có **150 operation được kiểm kê từ các decorator có path** (không tính WebSocket). Chi tiết có thể tái sinh tại [ROUTE_INVENTORY.md](ROUTE_INVENTORY.md), dữ liệu máy đọc tại [route-inventory.json](route-inventory.json). Số route không thay thế nghiệm thu tích hợp.

## Phạm vi theo quyết định chủ dự án

- Course/Lesson tiếp tục hoãn; giữ nguyên các route demo đã tồn tại, không mở rộng LMS.
- Social và Tri thức là hai phần riêng. Chỉ tương tác trong Tri thức được thưởng; không cộng thưởng cho like Social.
- Admin cấu hình mức points/coin theo upvote câu hỏi, câu trả lời, tài liệu; có thể giới hạn theo topic và số lượt nhận/ngày.
- Khoản mới ghi `reward_awards.status=pending`, chưa tăng ví. Mức thưởng được chụp tại thời điểm tương tác; sửa rule chỉ ảnh hưởng tương tác sau đó.
- Lập kỳ tuần (thứ Hai đến thứ Hai kế tiếp) hoặc tháng (ngày 1 đến ngày 1 tháng sau), theo `Asia/Ho_Chi_Minh`, chỉ khi kỳ đã đóng. Admin xem tổng theo người, duyệt hoặc hủy draft. Duyệt là transaction: ledger + wallet + trạng thái khoản thưởng cùng commit. Duyệt lại không tăng số dư. Các kỳ tuần/tháng chồng nhau không lấy lại khoản đã gán/đã duyệt.
- Không tự thưởng, một người chỉ có một khoản cho một nội dung trên mỗi rule. Bỏ like/downvote hoặc xóa/ẩn nội dung hủy khoản pending; xóa/ẩn question hủy cả khoản pending của answer bên dưới. Lúc duyệt kỳ kiểm tra lại trạng thái nội dung và lượt vote hiện tại dưới khóa transaction. Like lại có thể kích hoạt lại khoản chưa từng được duyệt, theo mức rule hiện tại và ngày phát sinh mới; không tạo thưởng lại cho khoản đã duyệt. Mọi chuyển trạng thái có audit riêng. Không có payout, quy đổi tiền, redemption hay bounty settlement.
- AI là base tích hợp provider, không mô tả là AI production đầy đủ. Model mặc định `gpt-5-mini`, cấu hình qua môi trường. Có thể đổi model; adapter hiện dùng Responses API, không giả định mọi provider có cùng giao thức.

## Các nhóm đã bổ sung

| Nhóm | API/nghiệp vụ mới |
|---|---|
| Identity | Tạo tài khoản Admin, gán role member/phòng ban chính, bắt buộc đổi mật khẩu; sửa thông tin tài khoản; reset mật khẩu và thu hồi session; đọc/sửa profile riêng; danh bạ cùng tổ chức không lộ phone/location/email người khác; lookup user nội bộ có key riêng cho từng service. |
| Social | Follow/unfollow và danh sách đang theo dõi; ghim/bỏ ghim có permission; tạo event, xem event và RSVP có khóa chống vượt capacity; xác nhận announcement; quản lý metadata/trạng thái channel và thành viên managed; upload/list/download/remove attachment private; callback scan có key riêng. |
| Tri thức | Sửa/xóa mềm question; follow/bookmark; comment trên question/answer và sửa/xóa comment của mình; vote/follow tài liệu áp dụng cùng ACL đọc; sửa lỗ hổng truy cập answer thuộc question ẩn/restricted. |
| Thưởng | Ví đã duyệt + tổng đang chờ; lịch sử tích lũy; ledger; leaderboard points đã duyệt; CRUD cấu hình rule (tạo/đọc/cập nhật/vô hiệu hóa); lập/xem/duyệt/hủy kỳ tuần/tháng. |
| Chat | Direct/group, tìm lại direct thay vì tạo trùng; lịch sử cursor; gửi text/file với client_message_id chống retry trùng; unread/read; thành viên, đổi tên và chuyển chủ nhóm; file private chờ scan. Tất cả dữ liệu vẫn ở MongoDB. |
| Notification | Danh sách/cursor, unread count, read/read-all, tùy chọn theo loại; đăng ký/liệt kê/thu hồi thiết bị, token mã hóa; Admin tạo/xem/publish broadcast tới danh sách user cụ thể; ingest event nội bộ chống trùng. |
| AI base | Status/configured, gợi ý summary/concepts từ question hoặc metadata document published đã kiểm tra quyền; keyword search qua Learning. Không có API nhận chat/draft để gửi sang provider. |
| Realtime | WebSocket `/api/v1/realtime`: xác thực session, subscribe một conversation được phép; phát tín hiệu thay đổi chat/thông báo; kiểm tra lại session/quyền trong mỗi chu kỳ. |

## Các luồng nền đã có code

- Social/Learning/Chat có `outbox:publish`, gửi RabbitMQ topic exchange, persistent messages, publisher confirms và giữ event chưa publish khi lỗi. Event có HMAC riêng từng producer, consumer xác minh trước khi xử lý.
- Notification có `notifications:consume`, manual ack sau commit; chống trùng event ID, dead-letter dữ liệu không hợp lệ; dependency lỗi thì giữ/requeue. Ánh xạ comment Social, answer/comment Tri thức, duyệt tài liệu, chat message, announcement theo membership của channel.
- Notification có `notifications:deliver`: FCM HTTP v1 qua Google Auth, retry tối đa 5 lần, hủy gửi khi thiết bị đổi chủ/bị thu hồi/tùy chọn push tắt, thu hồi token `UNREGISTERED`. FCM có thể nhận một lần gửi trước khi timeout; client cần deduplicate `notification_id`.
- Callback scan chỉ cập nhật trạng thái từ nguồn scanner đã xác thực và đúng SHA-256; không tự đánh dấu tệp sạch. Cần scanner thực tế gọi callback.

## Cấu hình và áp dụng

Từ root chạy `python3 scripts/configure-api-local.py --write` để bổ sung các key nội bộ còn thiếu vào `.env` hiện có, không in secret hoặc thay key đã cấu hình. Script đã chạy local. Nếu key hai đầu xung đột, script dừng và giữ nguyên.

Identity đã chạy `php artisan db:seed --class=ApiExpansionPermissionSeeder`: chỉ thêm các permission mới và liên kết với `super_admin`, không khôi phục quyền từng bị gỡ khỏi role khác.

Learning đã áp dụng ba migration ngày 2026-10-21 trên DB local. **Social và Notification chưa áp dụng migration local được**: cấu hình MySQL hiện tại bị từ chối tài khoản `app` tại `127.0.0.1:3306`. Không đổi mật khẩu, sửa quyền DB hoặc reset dữ liệu để vượt lỗi này. Sau khi sửa `.env` tương ứng, chạy migration bình thường.

Chat cần MongoDB replica set cho transaction và chạy `php artisan chat:ensure-indexes`; không chuyển sang MySQL. Bộ kiểm thử dùng database `chat_api_test_<uuid>` riêng, tự dọn database đó, không đụng `chat_db`.

File Social/Chat dùng disk `s3` mặc định, cần thông số MinIO/S3 của từng service (`AWS_*`); đã cài adapter như Learning. Tệp chưa scan không có URL tải. Các key callback là `SOCIAL_SCANNER_KEY`, `CHAT_SCANNER_KEY`, `LEARNING_SCANNER_KEY`.

AI thêm `OPENAI_API_KEY` vào `services/ai-knowledge-service/.env`; mặc định model và API base đã có trong code/`.env.example`. `AI_EXTERNAL_PROCESSING_ENABLED=false` có thể tắt gửi ra ngoài. Cần khởi động lại service sau khi đổi môi trường. Không có key thì trả lỗi cấu hình, không giả lập kết quả AI.

FCM cần `FCM_ENABLED=true`, `FCM_PROJECT_ID`, `FCM_SERVICE_ACCOUNT_PATH` trỏ tới file service account ngoài repository. Chưa có credentials nên chưa gửi push thật.

RabbitMQ dùng `RABBITMQ_HOST/PORT/USER/PASSWORD/VHOST`; cùng exchange/queue ở producer và consumer. Chạy command ở đúng service; có thể dùng supervisor/scheduler của môi trường triển khai. Không tự cài một lịch chạy production hoặc tự duyệt thưởng khi chưa được yêu cầu.

## WebSocket

Kết nối `ws://<gateway>/api/v1/realtime` (dùng WSS khi triển khai HTTPS). Trong 5 giây đầu gửi:

```json
{"type":"authenticate","token":"<64-character Identity access token>"}
```

Sau `ready`, gửi `{"type":"subscribe","conversation_id":"<uuid>"}` hoặc `{"type":"unsubscribe"}`. Có `subscription.denied/revoked` khi quyền không hợp lệ. `state.changed` chứa unread count, latest notification ID và last message ID; client tải nội dung qua REST. Đây là cầu nối local kiểm tra service mỗi 5 giây, chưa phải fanout realtime quy mô lớn qua Redis/RabbitMQ. Token không nằm trong URL. Tối đa 2 kết nối/user, 100 kết nối/instance, payload 8 KiB và giới hạn bản tin đầu vào; session bị thu hồi sẽ đóng kết nối.

## Những gì chưa được kết luận hoàn tất

- Chưa nghiệm thu toàn hệ thống đang chạy với DB local Social/Notification do lỗi credentials nêu trên.
- Chưa gọi OpenAI thật, chưa gửi FCM thật, chưa kiểm chứng RabbitMQ publish/consume trên broker thật trong lần thay đổi này.
- Scanner/antivirus chưa được triển khai; có API nhận kết quả, không coi tệp chưa scan là sạch.
- AI ingestion/extraction/embedding/vector search/hybrid ranking, curation lưu trữ và cập nhật Neo4j từ AI chưa thuộc base này. `/ai/search` trả `mode=keyword`, `hybrid_search_ready=false`; graph hiện có vẫn là demo.
- Broadcast Admin hiện nhắm danh sách user cụ thể (tối đa 100), không tự suy ra toàn công ty/phòng ban.
- Mention notification có API ingest, nhưng chưa có quy tắc phân tích/gắn mention trong trình soạn thảo.
- Chưa thay UI Web/Mobile/Admin để gọi mọi endpoint mới; đây là thay đổi backend/API.
- Không bổ sung import/export hàng loạt hoặc custom role vì format/phạm vi role xuyên tổ chức chưa chốt.

## Chính sách đã chốt thêm trong phiên làm việc

- Chủ dự án xác nhận hủy khoản chờ duyệt khi nội dung bị xóa/ẩn hoặc lượt like bị bỏ trước tổng kết. Đã triển khai và kiểm thử cả trường hợp thay đổi sau khi lập draft kỳ.
- Lịch sử đã duyệt luôn giữ nguyên. Bỏ like/xóa nội dung sau duyệt không sửa/xóa ledger cũ.
- Tự lập kỳ theo lịch, audience broadcast toàn công ty/phòng ban và cách gắn mention trong editor có thể bổ sung khi có nhu cầu/quy tắc cụ thể; không có lịch tự duyệt hoặc cơ chế tự suy diễn audience trong bản này.

## Cách dùng

[Xem ví dụ request, luồng tổng kết thưởng và kết nối API](REQUEST_EXAMPLES.md). Các schema bổ sung nằm trong `contracts/openapi/platform-additions-v1.json`; WebSocket mô tả riêng trong tài liệu này.

## Bằng chứng kiểm tra cuối

| Thành phần | Kết quả |
|---|---|
| Identity | 20 test / 140 assertion đạt |
| Social | 16 test / 179 assertion đạt |
| Learning | 13 test / 168 assertion đạt |
| Notification | 6 test / 63 assertion đạt |
| Chat | 3 test / 42 assertion đạt trên MongoDB replica set thật, database kiểm thử riêng |
| AI base | 4 test đạt; provider được mock, không gọi API tính phí |
| WebSocket | 1 test đạt: kiểm tra session, membership, thu hồi subscription và session |
| Gateway | Build TypeScript/NestJS đạt |
| OpenAPI | 8 contract qua Swagger Parser |

Tổng 63 test đạt. PHP syntax đã kiểm tra 298 file. Các package bị báo advisory trong Gateway và 5 backend Laravel đã được cập nhật bản tương thích; audit sau cập nhật không còn advisory đã biết tại thời điểm chạy. Không suy rộng kết quả này sang frontend/mobile/admin hoặc thành chứng nhận bảo mật production.

Chạy test PHP trong đúng thư mục service bằng `php artisan test`; AI dùng `python3 -m unittest discover -s tests -v`; Gateway dùng `npm run build`, `npm run check:contracts`, `node --test tests/realtime.test.cjs`. Chat yêu cầu `CHAT_TEST_MONGODB_URI` trỏ tới replica set kiểm thử; tự tạo và dọn database `chat_api_test_<uuid>`. Container kiểm thử tạm đã được dọn sau khi chạy, không đổi `chat_db` hoặc cấu hình MongoDB dự án.
