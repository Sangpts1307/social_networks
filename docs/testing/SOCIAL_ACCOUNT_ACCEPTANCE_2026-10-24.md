# Kịch bản kiểm thử tài khoản chung và mạng xã hội nội bộ

Ngày lập: 24/10/2026. Ghi kết quả từng ca là `PASS`, `FAIL`, `BLOCKED` hoặc `DEMO`, kèm người kiểm tra, thời điểm, ảnh màn hình, request ID và dữ liệu phát sinh. Chỉ đánh `PASS` khi thao tác dùng API thật và tải lại vẫn đúng.

## Chuẩn bị

- Chạy Identity, Gateway, Social, Learning, Chat, Notification và các database tương ứng. Áp dụng migration/seed quyền mới trước khi thử. Chat cần MongoDB replica set; thông báo tự động cần broker/consumer; tệp cần object storage và scanner.
- Tạo ít nhất hai tổ chức `O1`, `O2`; ba tài khoản `A` (quản trị/được cấp quyền đăng tin), `B` (member cùng O1), `C` (member O2). Có thêm `D` là member O1 ngoài kênh riêng. Dùng email/mật khẩu riêng, không dùng tài khoản mẫu trên Web.
- Tạo kênh toàn tổ chức và kênh riêng; A, B là thành viên kênh riêng, D không tham gia. Ghi lại ID bài/kênh/hội thoại để đối chiếu API và database.
- Trên Web, đăng nhập ở màn tổng rồi chọn **Mạng xã hội** hoặc **Tri thức**. Đăng nhập một lần dùng chung cùng phiên Identity; hồ sơ mở từ hai nơi phải là cùng tài khoản.
- Cột “UI hiện tại” phản ánh source ngày lập tài liệu; `DEMO` không chứng minh backend hoạt động. Các ca API có thể thử qua Gateway bằng collection/request từ `docs/api/REQUEST_EXAMPLES.md` và OpenAPI.

## A. Tài khoản chung và phân quyền

| ID | Thao tác | Kết quả cần đạt | UI hiện tại |
|---|---|---|---|
| A01 | Đăng nhập A ở màn tổng, vào MXH, về màn tổng, vào Tri thức, mở hồ sơ | Cùng tên, email, user ID và phiên; không phải đăng nhập lại | Có thể thử Web |
| A02 | Sửa giới thiệu/điện thoại/kỹ năng ở hồ sơ chung, tải lại và chuyển MXH ↔ Tri thức | Hồ sơ giữ dữ liệu từ Identity, không có bản sao riêng của MXH | Có thể thử Web |
| A03 | Đăng xuất từ MXH; dùng nút Back hoặc URL `?app=square`/`?app=inside` | Không vào được dữ liệu khi chưa đăng nhập | Có thể thử Web |
| A04 | Đăng nhập tài khoản mới cần đổi mật khẩu lần đầu | Chỉ được đổi mật khẩu; phiên cũ hết hiệu lực; đăng nhập lại bằng mật khẩu mới mới vào được hai ứng dụng | Web có thể thử; mobile còn lỗi luồng sau đổi mật khẩu |
| A05 | Sai mật khẩu nhiều lần, tài khoản suspended, session hết hạn | Phản hồi đúng; không lộ dữ liệu qua MXH/Tri thức hoặc URL trực tiếp | API/Web cần test thật |
| A06 | A gán/bỏ quyền đăng announcement cho B, B đăng nhập lại | Chỉ quyền được cấp mới cho phép đăng loại bài tương ứng; quyền cũ bị thu hồi | API; màn Web kiểm duyệt còn demo |
| A07 | C truy cập bài/hồ sơ/kênh/hội thoại của O1 | Bị chặn, không xuất hiện trong danh sách, kể cả gọi API trực tiếp | API cần test thật |

## B. Bài viết và tương tác Social

| ID | Thao tác | Kết quả cần đạt | UI hiện tại |
|---|---|---|---|
| B01 | B đăng bài thường vào kênh mình tham gia | Trả ID; A/B thấy trong feed; tải lại vẫn còn; C không thấy | Mobile hỗ trợ bài văn bản API; Web chỉ demo |
| B02 | Gửi nội dung rỗng, kênh không tồn tại, hoặc đăng vào kênh riêng không tham gia | Bị từ chối và không sinh bài | API; một phần mobile |
| B03 | B sửa/xóa bài của mình; A thử sửa bài của B khi không có quyền | Chủ bài sửa/xóa được; người không có quyền bị chặn; bài xóa không còn trong feed | Mobile có API cơ bản; Web demo |
| B04 | A/B thả, đổi, bỏ reaction; lưu/bỏ lưu; tải lại | Số đếm và trạng thái theo từng tài khoản chính xác, không cộng trùng | Mobile có API cơ bản; Web demo |
| B05 | B bình luận, A trả lời; khóa bình luận rồi thử gửi tiếp | Có cây bình luận đúng; khi khóa thì không gửi được, kể cả API | Mobile có API bình luận; moderation qua API |
| B06 | B báo cáo bài; A kiểm duyệt ẩn/khôi phục, thử xem bằng B/C | Chỉ người có quyền kiểm duyệt thao tác; bài ẩn không rò qua feed, tìm kiếm hoặc link trực tiếp | API; Web kiểm duyệt demo |
| B07 | Upload ảnh/tệp, quét clean/infected, tải bằng A/B/D/C | Chỉ tệp clean hiện và tải được cho người có quyền; infected/pending không tải được | API/hạ tầng; Web/mobile chưa nối upload thật |

## C. Tin nội bộ, thông báo và sự kiện

| ID | Thao tác | Kết quả cần đạt | UI hiện tại |
|---|---|---|---|
| C01 | B đăng `news`/`announcement` khi chưa có quyền | Trả 403, không sinh bài | API |
| C02 | A đăng `news` có tiêu đề, nội dung và kênh hợp lệ | B thấy sau tải lại; loại bài và tác giả đúng; C không thấy | API; Web chưa có form thật |
| C03 | A đăng `announcement`, B xác nhận đã đọc | Xác nhận gắn đúng B/bài; gọi lặp không đếm trùng | API; UI chưa nối |
| C04 | Phát announcement và kiểm tra inbox thông báo của B | Chỉ người nhận đúng phạm vi có thông báo; đọc/xem lại trạng thái chính xác | Cần broker/consumer và Notification API thật |
| C05 | A tạo event có thời gian/sức chứa; B RSVP, hủy, đăng ký khi đầy | Trạng thái và số chỗ đúng; trường hợp đầy trả lỗi phù hợp | API; UI chưa nối |

## D. Kênh và theo dõi

| ID | Thao tác | Kết quả cần đạt | UI hiện tại |
|---|---|---|---|
| D01 | B xem danh sách kênh, tham gia/rời kênh cho phép tự tham gia | Danh sách, membership và feed đổi đúng sau tải lại | API; mobile join/rời mới là demo |
| D02 | D truy cập bài/kênh riêng không tham gia, thử link trực tiếp | Không thấy nội dung; không tương tác được | API |
| D03 | Người quản lý thêm/bỏ thành viên, đổi thông tin kênh | Chỉ đúng vai trò/scope thực hiện; quyền xem thay đổi ngay | API |
| D04 | B theo dõi/bỏ theo dõi A | Danh sách following đúng; không theo dõi tài khoản khác tổ chức hoặc không hoạt động | API; UI chưa nối |

## E. Nhắn tin

| ID | Thao tác | Kết quả cần đạt | UI hiện tại |
|---|---|---|---|
| E01 | A tạo hội thoại trực tiếp với B; gửi tin; B mở ở phiên riêng | Hai bên thấy cùng hội thoại và lịch sử sau tải lại | Chat API có; Web/mobile hiển thị demo/local |
| E02 | Gửi lại cùng `client_message_id` và cùng nội dung; gửi lại ID đó với nội dung khác | Retry không tạo tin trùng; nội dung khác bị từ chối | API |
| E03 | B đánh dấu đã đọc; A gửi tiếp | Unread/read thay đổi đúng theo từng tài khoản | API; realtime thật cần test riêng |
| E04 | A tạo nhóm, thêm/bỏ B, chuyển chủ; người ngoài nhóm thử đọc/gửi | Chỉ thành viên được đọc/gửi; chỉ chủ có quyền quản lý nhóm | API |
| E05 | A nhắn C khác tổ chức; B thử xem hội thoại của A và D | Không tạo/truy cập được; không lộ nội dung | API |
| E06 | Gửi file chat pending/clean/infected, B tải file | Chỉ file clean được gắn và thành viên hợp lệ tải được | API/hạ tầng; UI chưa nối |

## Quyết định nghiệm thu

1. Kiểm tra A01–A03 trên Web trước để xác nhận một tài khoản cho hai ứng dụng.
2. Kiểm tra B01–B05 trên API/mobile thật, rồi tải lại và đối chiếu bằng tài khoản thứ hai. Không lấy thành công của Web demo làm kết quả backend.
3. Kiểm tra C, D, E qua Gateway; đánh `BLOCKED` nếu database, broker, storage hoặc scanner chưa sẵn sàng. Ghi rõ phần hạ tầng gây chặn.
4. Chỉ nghiệm thu toàn bộ MXH khi Web/mobile đã nối các thao tác cần dùng, qua được ca phân quyền âm tính và kiểm tra lại sau đăng xuất/tải lại.
