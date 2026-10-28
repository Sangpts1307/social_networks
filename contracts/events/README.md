# Event Contracts

Đặt schema và version của RabbitMQ events tại đây. Consumer phải xử lý idempotent.


## Delivery implementation — 2026-10-21

Social/Learning/Chat có command `outbox:publish`; Notification có `notifications:consume` và `notifications:deliver`. Publisher chuyển tên event sang hậu tố `.v1`, sử dụng envelope schema, persistent message và publisher confirms. Application header `signature` là HMAC-SHA256 của **nguyên văn JSON body**, dùng key riêng `<PRODUCER>_NOTIFICATION_KEY`; consumer kiểm tra signature trước khi xử lý. Broker dùng xác thực riêng qua `RABBITMQ_*`.

Consumer ánh xạ `social.comment.created.v1`, `social.announcement.published.v1`, `learning.answer.created.v1`, `learning.qa.comment.created.v1`, `learning.document.reviewed.v1` (action=approve), `chat.message.created.v1`. Payload có `organization_id`, `actor_user_id`, `recipient_user_ids`, `source_type/source_id` (Chat dùng `message_id`). Không đưa nội dung chat hoặc tệp vào event thông báo. Consumer manual-ack sau commit; event trùng không sinh thông báo trùng; dữ liệu sai/signature sai đi dead-letter queue. Bản deploy cần chạy các command và cấp credentials; chưa có nghiệm thu broker thật trong lần thay đổi này.
