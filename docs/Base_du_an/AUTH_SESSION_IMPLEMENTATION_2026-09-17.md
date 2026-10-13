# Auth/session và protected Gateway — 2026-09-17

## Phạm vi đã triển khai

Backend Identity, Gateway và luồng xác thực trên Vue. Không triển khai public registration, OTP, reset password, refresh token, Admin unlock hoặc RBAC admin trong lượt này.

### Schema

Migration bổ sung `2026_09_17_000001_add_auth_session_state.php`:

- `users.password_version` mặc định 1; `users.locked_at` để khóa không tự hết hạn.
- `user_sessions.password_version` mặc định 0 để phiên cũ chưa có version không được coi là hợp lệ.
- Giữ nguyên bảng/cột cũ; không reset, xóa hoặc seed dữ liệu. Đã áp dụng migration bổ sung vào identity_db sau kiểm thử MySQL riêng.

### Phiên đăng nhập

- Token ngẫu nhiên 32 byte, biểu diễn 64 ký tự hex; DB chỉ lưu SHA-256 trong `session_hash`.
- Thời hạn mặc định 480 phút, cấu hình `IDENTITY_SESSION_MINUTES`. Không có refresh token; hết hạn đăng nhập lại.
- Mỗi request kiểm tra phiên còn hạn/chưa thu hồi, version mật khẩu và trạng thái user.
- Khóa user bằng row lock trước khi kiểm tra mật khẩu/cập nhật counter/session; đổi mật khẩu và thao tác protected sử dụng cùng thứ tự lock user → session.
- Email login không phân biệt hoa thường. Nếu có cùng email ở nhiều organization, trả lỗi đăng nhập chung thay vì đoán tenant; schema hiện unique email theo organization, chưa áp đặt global unique lên dữ liệu.
- Login thành công reset counter; lần sai thứ 10 ghi locked_at. Không tự mở khóa theo thời gian. Account không active hoặc có locked_at/legacy locked_until bị chặn.
- Chưa có Admin unlock API: ID-AUTH-006 vẫn chờ RBAC/Audit.

### Đổi mật khẩu lần đầu

Login vẫn cấp phiên giới hạn khi `must_change_password_at` khác null. Phiên đó chỉ dùng session inspection, đổi mật khẩu và logout; `/me` trả 403 `PASSWORD_CHANGE_REQUIRED`.

Password mới: tối thiểu 12 ký tự, có chữ hoa/thường/số/ký hiệu, tối đa 72 UTF-8 bytes do bcrypt, confirmation khớp và khác mật khẩu cũ. Đổi password tăng version, xóa cờ bắt buộc đổi và thu hồi **tất cả** phiên; client phải đăng nhập lại.

### Gateway và ranh giới service

- Web/Mobile gọi Gateway `/api/v1/...`; Gateway không đọc DB Identity.
- Protected route dùng IdentitySessionGuard kiểm tra Bearer với Identity `/auth/session`, sau đó mới forward. Identity vẫn tự kiểm tra phiên ở endpoint đích; không tin user-id/role do client gửi.
- Chỉ forward route đã khai báo, JSON body, Bearer và request ID; không làm open proxy.
- Timeout 5 giây; lỗi transport/Identity 5xx thành 502, không lộ stack trace và không retry POST.
- Xác thực Gateway → Identity bằng key riêng `IDENTITY_GATEWAY_KEY`, tối thiểu 32 ký tự, qua header `X-Identity-Service-Key`; client không cần/không được giữ key này. Health không cần key.
- Đã tạo key ngẫu nhiên riêng trong `.env` local của Gateway/Identity, không in vào báo cáo/template. Hai service đọc cùng key; không thay credential MySQL.
- Key thiếu/không cấu hình: 503; service key sai: 401. Local service bind loopback; TLS/mTLS, key rotation và triển khai production còn ngoài phạm vi.
- Login throttle 30/phút, password change 10/phút bằng cache local. IP mà Identity thấy hiện là Gateway, nên đây là giới hạn local dùng chung, chưa phải rate limiting per-client production.

## Endpoint công khai qua Gateway

| Method | Path | Quyền / kết quả |
|---|---|---|
| POST | /api/v1/auth/login | Email/password; token + expiry + user tối thiểu |
| GET | /api/v1/auth/session | Bearer active; cho phép phiên bắt buộc đổi password |
| GET | /api/v1/me | Bearer active và đã đổi password lần đầu |
| POST | /api/v1/auth/password | Current password + password + password_confirmation; login lại sau thành công |
| POST | /api/v1/auth/logout | Thu hồi phiên hiện tại, 204 |
| POST | /api/v1/auth/logout-all | Thu hồi mọi phiên của user hiện tại, 204 |

Status chính: 401 phiên/credential sai, 403 account không khả dụng hoặc cần đổi password, 422 validation, 429 throttle, 502 upstream, 503 service key chưa cấu hình. Response user chỉ có id, organization_id, email, display_name, must_change_password; không có password hash, token hash hay profile PII.

Contracts: [Identity](../../contracts/openapi/identity-v1.yaml), [Gateway](../../contracts/openapi/gateway-identity-v1.yaml). File JSON là cú pháp YAML hợp lệ; SwaggerParser đã validate cả hai.

## Giao diện Web

- Form đăng nhập gọi `/api/v1/auth/login` qua Gateway; không còn tài khoản/mật khẩu demo điền sẵn.
- Token được lưu ở `sessionStorage`; chỉ dùng `localStorage` khi người dùng chọn **Duy trì đăng nhập**. Khi tải lại trang, Web gọi `/api/v1/auth/session` để xác minh token trước khi hiển thị portal.
- User có `must_change_password=true` được chuyển vào màn đổi mật khẩu lần đầu. Giao diện kiểm tra policy cơ bản, gọi `/api/v1/auth/password`, xóa token và yêu cầu đăng nhập lại bằng mật khẩu mới.
- Đăng xuất xóa token phía trình duyệt trước, sau đó yêu cầu Gateway thu hồi phiên. Portal hiển thị `display_name` và email thật do Identity trả về.
- Vite dev proxy `/api` tới `http://127.0.0.1:8080` theo mặc định; đổi bằng `VITE_GATEWAY_PROXY_TARGET`. Web không giữ `IDENTITY_GATEWAY_KEY` và không gọi Identity trực tiếp.
- Trang Profile và Admin chưa nối vì API Profile/RBAC/Admin chưa được triển khai.

## Kiểm tra đạt

- `php vendor/bin/phpunit` trong Identity: **12 tests, 87 assertions** trên SQLite in-memory; chặn chạy khi DB test không phải in-memory hoặc tồn tại config cache.
- Bao phủ validation, email mơ hồ, account inactive, 10 lần sai, token hash/expiry/version, logout một/tất cả và cách ly user, đổi password và bắt buộc đổi lần đầu, invalid password/Unicode quá 72 byte, account khóa/xóa sau login, service key thiếu/sai.
- Gateway typecheck/build PASS.
- Vue component-flow tests: **5 tests PASS** cho login, lỗi khóa, khôi phục phiên, đổi mật khẩu bắt buộc và lưu/xóa token.
- Vue production build PASS.
- `npm run check:contracts` tại Gateway: 2 OpenAPI contracts PASS.
- `python3 scripts/test-auth-local.py`: MySQL migration + repeat migration + seed; HTTP Gateway → Identity login/change-password/me/logout/logout-all/lockout; upstream unavailable → 502 PASS.
- Test tạo **duy nhất identity_auth_test** và user cùng tên, quyền chỉ trên DB đó. Từ chối nếu DB/user đã tồn tại. Cleanup trong finally đã xóa DB/user thử sau mỗi lần chạy; cache/log test nằm ở thư mục tạm cũng được dọn. Không tạo identity_verify_* nữa.
- Script verify-identity-database.php nay chỉ đọc inventory. Mode --verify cũ bị vô hiệu hóa và chỉ dẫn sang test-auth-local.py.

SQLite không thay cho concurrency test MySQL; row locking đã triển khai nhưng chưa có stress/race test song song. Component flow và backend HTTP integration đã đạt; browser E2E nối đồng thời UI với server thật còn thiếu.

## Chạy thử

1. Identity: tại `services/identity-service`, chạy `php artisan serve --host=127.0.0.1 --port=8081`.
2. Gateway: tại `gateway/api-gateway`, chạy `npm run start:dev`.
3. Web: tại `apps/web`, chạy `npm run dev`, rồi mở `http://127.0.0.1:5173`. Cần account được provision sẵn; lượt này không seed user vào `identity_db`.
4. Kiểm tra Web riêng: `npm test` và `npm run build` trong `apps/web`.
5. Để chạy trọn backend flow mà không thêm tài khoản vào DB làm việc, tại root chạy `npm run build --prefix gateway/api-gateway`, rồi `python3 scripts/test-auth-local.py`.

## Tiến độ

DONE: ID-DB-003/008 (schema auth/session + checks), ID-AUTH-001–005 và 007–011, ID-API-001, PLAT-008. WEB-ID-001/002 đang `IN_PROGRESS`: code và component/integration tests đạt, còn browser E2E. Không đánh dấu cả Identity/P0 DONE; Profile, Admin unlock/RBAC/Audit và các dependency còn lại vẫn chưa hoàn tất.
