# Inside Web

Vue 3, TypeScript và Tailwind CSS cho cổng nội bộ. Phase 0 đã nối giao diện xác thực với API Gateway; các ứng dụng Social, Learning và Knowledge Graph trong màn hình chào vẫn là phần demo/chưa có backend hoàn chỉnh.

## Chạy tại máy

Chạy Identity và Gateway trước, sau đó tại thư mục `apps/web`:

```bash
npm install
npm run dev
```

Mở địa chỉ Vite in ra trên terminal, mặc định `http://127.0.0.1:5173`. Vite chuyển tiếp `/api` tới Gateway `http://127.0.0.1:8080`; có thể đổi đích bằng `VITE_GATEWAY_PROXY_TARGET`.

```bash
VITE_GATEWAY_PROXY_TARGET=http://127.0.0.1:8000 npm run dev
```

Cần tài khoản đã được provision trong `identity_db`; Web không tự tạo tài khoản hoặc seed dữ liệu.

## Kiểm tra

```bash
npm test
npm run build
```

Kiểm thử giao diện bao phủ: form đăng nhập thật, lỗi account bị khóa, lưu/khôi phục phiên, chuyển sang đổi mật khẩu lần đầu và quay về đăng nhập sau khi đổi thành công.

## Phạm vi hiện tại

- Đăng nhập qua Gateway, lưu Bearer token theo lựa chọn duy trì đăng nhập.
- Kiểm tra lại phiên khi tải trang, hiển thị người dùng thật và đăng xuất.
- Bắt buộc đổi mật khẩu lần đầu theo trạng thái do Identity trả về.
- Bảng tin: tạo bài, thích, lưu và bình luận.
- Tin nhắn: chọn hội thoại, gửi tin và nhận phản hồi mô phỏng.
- Học tập/Q&A: thêm câu hỏi mới.
- Knowledge Graph và Hybrid Search: dữ liệu mẫu có thể tương tác.

Web chỉ gọi Gateway, không kết nối trực tiếp database hoặc Identity service. Trang hồ sơ và các màn hình Admin vẫn chờ API Profile/RBAC/Admin tương ứng.
