# Ví dụ gọi API mới

Base local theo `run_all.js`: `http://127.0.0.1:8000/api/v1`. Dùng `Authorization: Bearer <access_token>` từ Identity. UUID trong ví dụ phải là tài nguyên thật cùng tổ chức; Gateway tự dựng context, client không gửi header danh tính nội bộ.

## Tích lũy và duyệt thưởng Tri thức

Admin có permission `learning.rewards.manage` tạo rule:

```http
POST /api/v1/admin/learning/reward-rules
Content-Type: application/json

{
  "code": "answer-upvote",
  "trigger_event_type": "learning.answer.upvoted",
  "points_delta": 0,
  "coins_delta": 2,
  "daily_limit": 50,
  "status": "active"
}
```

Các event type còn có `learning.question.upvoted`, `learning.document.upvoted`. `topic_id` là bộ lọc tùy chọn. Sửa rule bằng `PUT /admin/learning/reward-rules/{ruleId}` với toàn bộ các trường trên; code/trigger không đổi để giữ danh tính cơ chế. Đặt `status=disabled` để dừng ghi nhận tương tác mới.

User vote answer qua `PUT /learning/answers/{answerId}/votes` với `{"value":1}`; `0` bỏ vote, `-1` downvote. User xem `GET /learning/rewards/wallet`, `/accruals?status=pending` và `/ledger`. Pending chưa tăng số dư. Bỏ like/downvote hoặc xóa/ẩn nội dung hủy khoản pending; khoản đã duyệt không bị sửa.

Khi kỳ đã kết thúc, Admin lập draft:

```http
POST /api/v1/admin/learning/reward-settlements
Content-Type: application/json

{"period_type":"month","period_start":"2026-09-01"}
```

Có thể dùng `week` với ngày thứ Hai. API hiểu ngày theo Việt Nam; kỳ đang mở trả 422. Xem `GET /admin/learning/reward-settlements/{id}` để biết tổng theo người. Duyệt `POST /admin/learning/reward-settlements/{id}/approve` hoặc hủy draft bằng `/cancel`. Duyệt kiểm tra lại source và vote hiện tại, cộng số dư/ledger trong transaction; gọi lặp không trả thêm tiền/điểm. Không có API đổi coin sang tiền thật.

## Chat

```http
POST /api/v1/chat/conversations
Content-Type: application/json

{"type":"direct","member_ids":["<recipient-user-uuid>"]}
```

Group dùng `type=group`, thêm `title` và danh sách member. Người tạo tự là owner. Gửi:

```http
POST /api/v1/chat/conversations/{conversationId}/messages
Content-Type: application/json

{"client_message_id":"<new-uuid>","body_markdown":"Xin chào"}
```

Giữ nguyên `client_message_id` khi retry. Cùng ID nhưng nội dung khác trả 409. Lịch sử qua `GET .../messages?cursor=<meta.next_cursor>`; đọc tới snapshot hiện tại bằng `PUT .../read`.

Upload file: `POST .../attachments`, multipart field `file`. File pending không thể gắn vào message. Scanner được cấu hình phải báo clean trước; sau đó gửi `attachments:["<attachment-id>"]` thay hoặc kèm body. File đã gắn không được gắn lại vào một message mới. Tải qua `GET .../attachments/{attachmentId}/download`; service kiểm tra membership và trả URL hết hạn sau 5 phút.

Owner group thêm thành viên bằng `POST .../members` với `{"user_id":"<uuid>"}`, bỏ thành viên bằng `DELETE .../members/{userId}`, đổi tên bằng `PATCH /chat/conversations/{id}` với `{"title":"Tên mới"}`, chuyển chủ bằng `PUT .../owner` với `{"user_id":"<active-member-uuid>"}`.

## Notification và thiết bị

`GET /notifications`, `GET /notifications/unread-count`, `PUT /notifications/{id}/read`, `PUT /notifications/read-all`.

```http
POST /api/v1/devices
Content-Type: application/json

{"platform":"android","push_token":"<FCM registration token>","device_label":"Điện thoại của tôi"}
```

Token được mã hóa, không trả trong response/list. Thu hồi bằng `DELETE /devices/{id}`. Tùy chọn dùng `PUT /notification-preferences/{type}` với `{"in_app_enabled":true,"push_enabled":false}`. Loại gồm `comment`, `answer`, `mention`, `document_approved`, `chat_message`, `announcement`; email luôn tắt.

Admin có permission broadcast tạo `POST /admin/broadcasts` với `title`, `body`, `recipient_user_ids` (1–100 user), kiểm tra bằng `GET /admin/broadcasts/{id}`, rồi `POST /admin/broadcasts/{id}/publish`. Publish lại không sinh thông báo trùng.

## Social

Các endpoint mới ở `/social`: `PUT/DELETE /users/{userId}/follow`, `GET /following`, `PUT/DELETE /posts/{id}/pin` (permission riêng), `PUT /posts/{id}/acknowledgement` cho announcement.

Tạo event bằng `POST /social/posts`:

```json
{
  "channel_id":"<channel-uuid>",
  "post_type":"event",
  "body_markdown":"Buổi chia sẻ nội bộ",
  "event":{
    "starts_at":"2027-01-10T09:00:00+07:00",
    "ends_at":"2027-01-10T11:00:00+07:00",
    "rsvp_enabled":true,
    "capacity":30
  }
}
```

Xem `GET /social/posts/{id}/event`, RSVP bằng `PUT /social/posts/{id}/rsvp` với `{"status":"going"}` (`interested`/`declined` cũng được), hủy qua DELETE. Hết chỗ hoặc đã đóng đăng ký trả 409.

File Social dùng `POST /social/posts/{id}/attachments` multipart field `file`; chỉ tác giả post được upload/remove. Listing/download chỉ có file sạch và vẫn kiểm tra quyền xem post.

## AI base

```http
POST /api/v1/ai/suggestions
Content-Type: application/json

{"source_type":"question","source_id":"<question-uuid>","task":"summary"}
```

`source_type=document` chỉ lấy title/summary của tài liệu published được phép xem; chưa trích xuất nội dung file. `task=concepts` tạo đề xuất concept. Kết quả `requires_human_review=true` và không tự ghi vào Graph. `GET /ai/search?q=...` là keyword search, không phải hybrid search. `GET /ai/status` trả trạng thái cấu hình, không lộ secret.

## Internal APIs

Không gọi các route internal từ Web/Mobile và không đặt service key trong client. Scanner gọi trực tiếp service tương ứng: Social/Chat `PUT /api/v1/internal/attachments/{id}/scan`; Learning `PUT /api/v1/internal/document-files/{id}/scan`; body `{"sha256":"<64 hex>","status":"clean"}` hoặc `infected`, header `X-Scanner-Key` riêng từng service.

Identity `POST /api/v1/internal/users/resolve` yêu cầu `X-Service-Name` + `X-Service-Key`, body `organization_id` + `user_ids`. Notification `POST /api/v1/internal/notification-events` yêu cầu key producer; body được định nghĩa bởi `NotificationEventController`, gồm event UUID, org, loại, recipients, title/body và source references. RabbitMQ consumer dùng cùng ingestion service để chống trùng.
