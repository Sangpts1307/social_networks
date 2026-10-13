# Social API dùng chung cho Web và Mobile

Cập nhật 13/10/2026. Hợp đồng công khai: [gateway-social-v1.yaml](../../../contracts/openapi/gateway-social-v1.yaml). Có thể import trực tiếp vào Postman để tạo collection hoặc dùng công cụ sinh client từ OpenAPI. Hợp đồng nội bộ [social-v1.yaml](../../../contracts/openapi/social-v1.yaml) chỉ dành cho Gateway.

## Cách gọi

Base URL local: `http://127.0.0.1:8080/api/v1/social`. Web, Android và client khác dùng cùng URL, dữ liệu JSON và Bearer token từ API đăng nhập Identity. Không cần Laravel session cookie hoặc CSRF token cho các endpoint này. Browser cần origin trong `ALLOWED_ORIGINS` của Gateway; Android native không chịu CORS của trình duyệt. Khi gọi từ emulator/thiết bị, thay loopback bằng địa chỉ máy chạy Gateway có thể truy cập từ thiết bị.

```http
Authorization: Bearer <access_token>
Accept: application/json
Content-Type: application/json
```

Client không gửi khóa nội bộ, user ID của người đang thao tác, organization ID hay permission. Gateway xác minh phiên qua Identity ở mỗi request, bỏ qua header danh tính do client tự khai và truyền actor đã xác minh sang Social. Tài khoản phải đổi mật khẩu lần đầu sẽ nhận `403 PASSWORD_CHANGE_REQUIRED`.

## API đã có

Tất cả path dưới đây nối sau base URL.

| Method | Path | Chức năng |
|---|---|---|
| GET / POST | `/channels` | Danh sách kênh nhìn thấy / tạo kênh |
| PUT / DELETE | `/channels/{id}/membership` | Tham gia kênh mở / rời kênh |
| GET / POST | `/posts` | Bảng tin / tạo bài thường, tin nội bộ hoặc thông báo |
| GET / PATCH / DELETE | `/posts/{id}` | Chi tiết / sửa hoặc publish nháp / xóa mềm bài của mình |
| GET / POST | `/posts/{id}/comments` | Bình luận và reply / tạo bình luận |
| PATCH / DELETE | `/comments/{id}` | Sửa / xóa mềm bình luận của mình |
| PUT / DELETE | `/posts/{id}/reaction` | Đặt hoặc đổi / bỏ reaction |
| PUT / DELETE | `/posts/{id}/save` | Lưu / bỏ lưu riêng tư |
| POST | `/posts/{id}/reports` | Báo cáo bài vi phạm |
| GET | `/admin/reports` | Danh sách báo cáo trong scope được phép |
| POST | `/admin/posts/{id}/moderation` | Ẩn, gỡ, khôi phục, khóa/mở bình luận |

## Ví dụ tạo bài và đọc bảng tin

```http
POST /api/v1/social/posts
Idempotency-Key: 9f9171c9-2303-4e16-97bf-1f6a441bd57d
```

```json
{
  "channel_id": "60000000-0000-4000-8000-000000000001",
  "body_markdown": "Chia sẻ kinh nghiệm triển khai API",
  "post_type": "update",
  "publication_status": "published"
}
```

Trả `201`, body `{ "data": { ... } }`, có `Location` trỏ tới tài nguyên công khai. Có thể tạo nháp bằng `publication_status: "draft"`, sau đó `PATCH /posts/{id}` với `{"publication_status":"published"}`. Nháp chỉ được trả khi tạo/sửa, không nằm trong feed hoặc public detail. Chưa có API danh sách nháp.

Đọc bảng tin: `GET /posts?limit=20`. Lọc theo `channel_id`, `author_user_id`, `post_type`; xem bài đã lưu bằng `saved=1`. Response có `data` và:

```json
{
  "meta": {
    "next_cursor": "<opaque cursor hoặc null>",
    "has_more": true,
    "limit": 20
  }
}
```

Để tải tiếp, giữ nguyên filter và gửi `cursor` bằng query encoder của client. Không giải mã hay tự dựng cursor; cursor bị ràng buộc với endpoint, actor, tổ chức và filter. Đổi filter thì bắt đầu lại không cursor. Giới hạn 1–100, mặc định 20. Feed sắp `published_at DESC, id DESC`; sửa nội dung không đẩy bài cũ lên đầu. Comments dùng cùng dạng phân trang, sắp thời gian tạo giảm dần; danh sách phẳng có `parent_comment_id` để UI ghép reply, chỉ cho reply một cấp. Khi parent bị xóa/ẩn, reply không còn được trả.

Các ID là UUID; thời gian trả ISO 8601 UTC có `Z`; count là số và `is_saved` là boolean. `author_user_id` là tham chiếu Identity, không có join database trực tiếp hoặc tên/avatar giả. Phần bổ sung hồ sơ công khai cần contract Identity riêng.

## Tương tác và retry

- `PUT /posts/{id}/reaction`: `{"reaction_type":"like"}`; hỗ trợ `like`, `love`, `sad`, `angry`. Gọi lại cùng trạng thái không tăng số reaction. DELETE bỏ reaction.
- `PUT /posts/{id}/save`: không cần body. Gọi lại không thêm bản ghi; chỉ caller xem được danh sách lưu của mình. Không trả save count hay danh sách người lưu.
- Tạo comment: `{"body_markdown":"Nội dung"}`; reply thêm `parent_comment_id` của comment cấp gốc trên cùng bài.
- Report: `{"reason_code":"spam","detail":"Lý do"}`. Lần đầu `201`; nếu caller đã có report đang mở trên bài thì `200` với cùng receipt.
- Mỗi lệnh POST nên có một `Idempotency-Key` duy nhất. Khi timeout, gửi lại cùng key và cùng JSON bytes; thành công trước đó được replay với `Idempotency-Replayed: true`. Đổi payload nhưng giữ key nhận `409`. Chỉ lưu kết quả thành công; request lỗi có thể sửa và gửi lại. Replay vẫn kiểm tra quyền/membership hiện tại. Response replay là ảnh chụp thành công ban đầu, không phải nội dung hiện tại; đọc lại detail nếu cần trạng thái mới. Key hiện chưa tự hết hạn; dùng key mới cho thao tác mới. Cần chính sách dọn key trước phát hành dài hạn.

## Quyền và vòng đời

Đọc kênh `organization` cho user cùng tổ chức; kênh `members` chỉ cho thành viên active. Tạo bài, bình luận và reaction cần membership active, kể cả kênh toàn tổ chức. Kênh open cho phép self-join bằng ID; managed không cho tự gia nhập; owner không được tự rời. Chưa có API mời/thêm người khác vào managed channel hoặc quản lý department membership tự động.

Tạo kênh cần `social.channels.manage`; news cần `social.news.publish`; announcement cần `social.announcement.publish`; moderation cần `social.moderation.manage`. News/announcement phải có title. Seed RBAC hiện cấp các quyền này cho super_admin; cấp thêm qua Identity theo nhu cầu, Social không suy diễn quyền từ tên role. Moderator vẫn cần quyền đọc kênh và không được vượt tổ chức.

Bài thường publish ngay nếu không yêu cầu draft. Moderation chỉ xử lý bài đã publish; restore không biến draft thành bài công khai. Mọi thay đổi nghiệp vụ ghi outbox cùng transaction; moderation có lịch sử audit. Outbox chưa có relay/consumer gửi Notification/AI. Counter API đọc bảng nguồn, không sử dụng `post_stats` chưa có worker rebuild.

Nội dung Markdown là dữ liệu người dùng: backend bỏ HTML tags; web/mobile vẫn phải dùng renderer an toàn, tắt raw HTML và chặn URL/script không an toàn. `body_plaintext` dùng khi chỉ cần hiển thị text.

## Lỗi và giới hạn

Lỗi có `message`, `code`, `errors`. Validation theo field trả `422`; sai/thu hồi phiên `401`; thiếu quyền `403`; tài nguyên không tồn tại hoặc ngoài scope `404`; xung đột trạng thái/key `409`; quá tần suất `429`; upstream lỗi `502`; thiếu cấu hình khóa nội bộ `503`.

Giới hạn mặc định 120 request/phút cho mỗi tổ chức + user. `Retry-After` được chuyển qua Gateway để client lùi thời điểm thử lại. Local dùng cache file; khi chạy nhiều replica, cấu hình cache dùng chung. Không tự retry vô hạn lỗi 4xx. Các response Social có `Cache-Control: no-store`. Gateway nhận JSON tối đa 1 MB để hỗ trợ body tiếng Việt tới 50.000 ký tự; Social vẫn kiểm tra giới hạn từng trường.

## Chạy local và kiểm chứng

1. Social và Gateway cần cùng `SOCIAL_GATEWAY_KEY` ngẫu nhiên tối thiểu 32 ký tự trong cấu hình server; không đặt khóa này vào client. Giữ `SOCIAL_SERVICE_URL` ở Gateway trỏ `http://127.0.0.1:8082`.
2. Cấu hình MySQL `social_db` cho service và `APP_KEY` Laravel. Kiểm tra đúng database trước khi chạy migration mới bằng `php artisan migrate` tại Social; không chạy migrate:fresh trên dữ liệu hiện có.
3. Seed `RbacSeeder` tại Identity để đăng ký bốn permission Social; seed không xóa role/permission hoặc sửa credential. Admin có thể cấp role/permission qua API hiện hữu.
4. Nếu cần dữ liệu demo, SocialDemoSeeder tạo kênh general và owner membership cho tài khoản demo. Hệ thống không tự thêm user bất kỳ vào kênh managed.
5. Chạy Social ở 8082, Gateway ở 8080 và Identity ở 8081 theo hướng dẫn local. Gateway đã có controller/proxy Social cụ thể cho từng route.
6. Bộ kiểm thử: `php artisan test` tại Social; `npm run typecheck`, `npm run build`, `npm run check:contracts` tại Gateway; `python3 scripts/test-social-local.py` tại root source. Integration runner tạo MySQL/account thử riêng, từ chối dùng lại khi tên đã tồn tại và dọn sau khi chạy; không nhắm vào DB nghiệp vụ.

Bằng chứng ngày 13/10: 13 Feature tests/137 assertions trên SQLite; cùng suite và HTTP integration trên MySQL riêng; retry đồng thời và reaction uniqueness; migration/seed lặp; Gateway login thật, identity-header spoofing denial, cursor, tương tác, moderation và revoked token. Chưa nối Vue/Android hoặc browser E2E trong đợt backend này.

Phạm vi tiếp theo: attachment có storage/scan/download quyền; event/RSVP; follow cần xác minh người được follow thuộc tổ chức qua contract Identity; managed membership và profile enrichment; moderation comment; Web/Admin UI và Android. Không trả dữ liệu giả cho các chức năng chưa có.
