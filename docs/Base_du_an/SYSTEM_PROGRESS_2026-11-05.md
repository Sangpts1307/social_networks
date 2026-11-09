# Kiểm tra chức năng và tiến độ — 05/11/2026

## Kết quả thực hiện hôm nay

- Đối chiếu route Social, Gateway, tài liệu coverage và các màn Web. Gateway đã chuyển tiếp `GET /api/v1/social/posts`; Social nay nhận thêm `q` (2–200 ký tự) để tìm trong tiêu đề và nội dung bài viết. Kết quả vẫn chỉ gồm bài đã công bố, chưa bị kiểm duyệt và người gọi có quyền xem. Ký tự `%`, `_`, `!` được tìm như văn bản thường. Bộ lọc nằm trong phạm vi cursor hiện có.
- Bổ sung `GET /api/v1/social/channels/{channelId}`, `GET /api/v1/social/posts/drafts` và `GET /api/v1/social/posts/drafts/{postId}` qua Gateway. Bản nháp chỉ trả về cho tác giả còn quyền truy cập kênh.
- Kiểm thử `SocialApiTest`: **19 test, 214 assertion đạt**. Kiểm thử mới bao gồm tìm kiếm literal, bản nháp, cô lập tổ chức và validation. Gateway build đạt; 8 hợp đồng OpenAPI hợp lệ. Kiểm kê sau bổ sung AI có 159 route Gateway từ decorator.
- `scripts/local-doctor.py` đạt kiểm tra nền tảng ở các Laravel service; script này không kết nối thử database hoặc xác nhận service chạy.

## Tình trạng theo nhóm

| Nhóm | Hiện trạng xác nhận từ mã nguồn | Còn thiếu để chạy hết và nghiệm thu |
|---|---|---|
| Identity/Admin | Có auth, profile, quản trị và phân quyền; đang có thay đổi chưa commit trong các trang Admin và controller Identity | Kiểm thử lại các thay đổi đang làm, rà quyền từng vai trò và dữ liệu thật |
| Social | API bài viết, tương tác, kênh, event, file, tìm kiếm và đọc bản nháp; kiểm thử Social đạt | Nối toàn bộ màn Web/Mobile, áp dụng migration và thử DB/storage/scanner thực |
| Tri thức/Rewards | API tài liệu, hỏi đáp, ví và duyệt thưởng đã có | Nối UI, thử luồng xuyên service và duyệt theo kỳ với dữ liệu thật |
| Chat/Notification | API và nền outbox/consumer có trong source | Mongo replica set/index, RabbitMQ, FCM, Web/Mobile và kiểm thử nhiều người dùng |
| AI/Knowledge Graph | Có AI base, keyword search, graph demo; đã thêm API đề xuất/duyệt concept và đồng bộ Neo4j có đối chiếu nguồn Learning | Migration MySQL AI, thử Neo4j thật; ingestion tự động, embedding, hybrid search, đánh giá chất lượng và provider thật |
| Web/Mobile | Có nhiều màn demo; nhiều view Web vẫn dùng `mock/store` hoặc `mockData` | Chuyển các thao tác sang API, xử lý lỗi/quyền/trạng thái rỗng; nghiệm thu thiết bị native |

## Kết luận tiến độ

Chưa thể kết luận “chạy được hết”. Một số API nền đã đầy đủ theo phạm vi đợt 21/10, nhưng nhiều màn còn mock và các phụ thuộc runtime chưa được xác nhận trong lần kiểm tra này. Không có cơ sở đáng tin cậy để gán phần trăm hoàn thành. Các thay đổi đang có sẵn trong cây mã trước phiên này được giữ nguyên, chưa được tính là đã nghiệm thu.

## Việc tiếp theo theo thứ tự

1. Hoàn tất và kiểm thử các thay đổi Identity/Admin đang dở; rà route theo từng luồng UI thực tế.
2. Chạy migration và smoke test với MySQL, Mongo, MinIO, RabbitMQ; ghi lại lỗi cụ thể thay vì coi `local-doctor` là kiểm chứng DB.
3. Nối các màn Web/Mobile còn mock vào Gateway; kiểm tra login → bài viết/tương tác → chat/thông báo → tài liệu/thưởng bằng tài khoản khác quyền.
4. Triển khai AI/KG theo đầu ra đồ án, rồi kiểm thử E2E và cập nhật báo cáo nghiệm thu.

## Bổ sung AI/KG trong đợt này

Đã thêm các API qua Gateway: `POST/GET /api/v1/ai/curation/concepts`, `GET /api/v1/ai/curation/pending`, `POST /api/v1/ai/curation/concepts/{conceptId}/review`, `POST /api/v1/ai/curation/concepts/{conceptId}/sync`. Đề xuất gắn nguồn Learning và hash nội dung; duyệt/sync kiểm tra lại nguồn. Người duyệt cần `ai.curation.review`. Đồng bộ Neo4j dùng `MERGE` để gọi lại an toàn. Nếu Neo4j lỗi, bản duyệt vẫn lưu và API sync có thể được gọi lại. Cần chạy Alembic migration `20261105_0002` trước khi dùng.

Kiểm thử AI: **5 test đạt** với SQLite và Neo4j giả lập; Alembic SQL sinh thành công. Chưa có bằng chứng gọi MySQL/Neo4j/OpenAI thật. Curation hiện là đề xuất thủ công có duyệt; chưa phải pipeline tự trích xuất hay hybrid search production.

Tham chiếu nền: [báo cáo 22/10](SYSTEM_PROGRESS_2026-10-22.md) và [coverage API 21/10](../api/API_COVERAGE_2026-10-21.md).
