# Bản thiết kế Figma ĐATN — tiến độ đã lưu

[Mở file Figma](https://www.figma.com/design/EDb4fkxGn3GtlDVuJ2flBg)

**Trạng thái: chưa hoàn tất.** Kết nối Figma MCP đã báo hết hạn mức thao tác của gói Starter. Không có thay đổi nào được ghi vào Figma sau thông báo này.

## Những phần đang có

- 19 màn hình/trạng thái của ứng dụng đã được dựng thành khung riêng, dùng chữ, vector, ảnh nội dung và instance chỉnh sửa được; 106 thành phần dùng chung đã được tạo.
- 8 trang quản trị đã được chuyển sang các lớp gốc chỉnh sửa được, nhưng còn nằm chung trong một khung tham chiếu.
- Các bản tham chiếu vẫn được giữ để đối chiếu và khôi phục.

Liên kết xem các bản tham chiếu:

- [Đăng nhập, cổng ứng dụng, bảng tin, nhắn tin và kiểm duyệt](https://www.figma.com/design/EDb4fkxGn3GtlDVuJ2flBg?node-id=11-2).
- [Hỏi đáp, thư viện, đồ thị, hồ sơ và các trang chi tiết](https://www.figma.com/design/EDb4fkxGn3GtlDVuJ2flBg?node-id=9-2).
- [8 trang quản trị](https://www.figma.com/design/EDb4fkxGn3GtlDVuJ2flBg?node-id=13-2).

## Phần cần hoàn tất

1. Khôi phục độ trong suốt cho một số nền trong các khung đã dựng: hiện một số vùng chữ trắng ở đăng nhập/cổng ứng dụng bị nền trắng che.
2. Dựng riêng 8 khung quản trị, bố trí các thành phần dùng chung cho gọn.
3. Tạo liên kết trình diễn và trang hướng dẫn.
4. Kiểm tra trực quan toàn bộ, rồi xóa các bản tham chiếu tạm.

## Hai cách tiếp tục

**Tiếp tục qua kết nối:** khi tài khoản Figma có thêm lượt thao tác, yêu cầu tiếp tục từ `state.json` và `progress.json`. Không cần dựng lại 19 màn hình.

**Chạy công cụ khôi phục trong Figma Desktop:** thư mục `plugin` chứa mã để thực hiện các bước còn lại. Mã đã kiểm tra cú pháp JavaScript; **chưa chạy thử trong Figma**, nên đây là công cụ khôi phục dự kiến, không phải xác nhận đã hoàn tất.

1. Trong Figma Desktop, mở đúng file ở liên kết trên. Chọn **Plugins → Development → New plugin**, tạo một plugin Design kiểu chạy một lần, không có giao diện.
2. Giữ lại `id` mà Figma vừa cấp. Thay nội dung `code.js` trong thư mục plugin vừa tạo bằng `plugin/code.js` trong bộ này. Dùng cấu hình của `plugin/manifest.json`, thay `id` mẫu bằng `id` Figma vừa cấp.
3. Chạy plugin từ **Plugins → Development**. Plugin đọc các lớp đã có, sửa nền trong suốt, dựng 8 khung quản trị, tạo liên kết trình diễn và hướng dẫn. Các khung tham chiếu chỉ bị xóa sau khi đủ 19+8 khung và kiểm tra không còn thiếu font.
4. Nếu plugin báo lỗi, giữ nguyên file và gửi nguyên văn thông báo để sửa tiếp. Các thao tác plugin có thể hoàn tác bằng Undo.

Figma hướng dẫn tạo và chạy plugin phát triển tại [Create a classic plugin for development](https://help.figma.com/hc/en-us/articles/360042786733-Create-a-classic-plugin-for-development). Quy tắc `id` được mô tả tại [Plugin manifest](https://developers.figma.com/docs/plugins/manifest/).

## Nguồn giao diện

- `apps/web/src/App.vue`, các view Vue đang sử dụng và ba trang chi tiết trong `components/base-square`.
- Tám trang có sẵn trong `design/exports/admin`.
- Dữ liệu từ `apps/web/src/mock/mockData.js`; API giả chỉ nằm trong bản xem thử riêng.
- Biểu tượng SVG từ kho chính thức `google/material-design-icons`; hình ảnh nội dung được giữ theo nguồn của giao diện.
- Trong bản dựng, font Menlo không có trong môi trường Figma được thay bằng JetBrains Mono.

Các module cũ không được ứng dụng gọi và các bản HTML trùng giao diện không được tính là màn hình mới. Hiện `apps/mobile` chỉ có tài liệu, chưa có giao diện ứng dụng để chuyển thành bản thiết kế.

Không sửa các view, API hay luồng đăng nhập của ứng dụng. `apps/web/figma-preview.html`, `figma-preview.ts`, `figma-admin-preview.html` và các tài nguyên `public/figma-*` là bộ xem thử riêng phục vụ chuyển đổi.
