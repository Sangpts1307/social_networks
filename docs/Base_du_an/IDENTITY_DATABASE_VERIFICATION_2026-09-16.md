# Kiểm chứng database Identity — 2026-09-16

## Kết quả hiện tại

- MySQL local 127.0.0.1:3306 kết nối thành công sau khi chủ dự án sửa `.env`. Không sao chép credential vào tài liệu/template.
- `php artisan migrate:status` trong Identity: **16/16 Ran**.
- `identity_db` có 16 bảng nghiệp vụ, tất cả **0 bản ghi**, và bảng `migrations` có 16 bản ghi. Chưa seed user vào database này.
- Kiểm tra cuối thành công trên **identity_verify_20260916_030142_833ecf**; không dùng database này như môi trường làm việc.

## Sự cố trong lần kiểm thử đầu và khắc phục

Script mới `scripts/verify-identity-database.php` ban đầu đổi default connection nhưng chưa xóa cache Schema của Laravel. Kết quả: bảng migrations nằm trong database thử, còn 16 bảng schema được tạo nhầm vào `identity_db`. Trước lần chạy này `identity_db` có 0 bảng; sau đó cả 16 bảng nghiệp vụ đều trống, không có dữ liệu nghiệp vụ trước đó bị mất. Đây là thay đổi ngoài dự định của trợ lý, không phải migration do chủ dự án thực hiện.

Đã sửa script: chuyển cả named connection mysql và default, xóa cached schema builder, kiểm tra đích của schema, chặn ghi trên connection nguồn đã mở; giữ PDO gốc riêng để kiểm tra nguồn sau khi Laravel purge connection. Phép kiểm tra inventory cũng được sửa để không vô tình reconnect sang DB thử.

Đã so sánh `SHOW CREATE TABLE` của đủ 16 bảng với database thử đã chạy migration: khớp hoàn toàn; tất cả bảng nguồn trống. Sau khi được phép chạy repair, `scripts/reconcile-identity-migrations.php ... --apply` chỉ bổ sung bảng migrations và 16 bản ghi tương ứng. Không drop/reset/tạo lại 16 bảng; không chạy seed vào identity_db. `migrate:status` và kiểm tra số bản ghi sau sửa đều đạt.

Script reconcile là công cụ khắc phục một lần: yêu cầu đúng local identity_db, đúng bộ 16 bảng trống, schema và danh sách migration khớp bản thử. Sau khi có ledger, script từ chối chạy lại. Không dùng công cụ này cho bootstrap thông thường.

## Lỗi seeder đã sửa

`AdminUserSeeder` trước đây dùng `updateOrCreate` cho user và đặt lại password hash, status, failed-login count khi seed lại. Đã chuyển sang `firstOrCreate` để chỉ khởi tạo tài khoản chưa có.

Bài kiểm tra tạo user demo trong database riêng, thay hash/password state thành giá trị khác và trạng thái suspended/failed count 7, chạy lại seed rồi xác nhận cả ba giá trị không đổi. Dữ liệu profile/membership demo vẫn có update logic; không coi toàn bộ seeder là an toàn cho database có người dùng thật.

## Kiểm tra thực hiện

Lệnh từ root social_networks: `php scripts/verify-identity-database.php --verify`.

- PASS: schema builder/default connection trỏ database thử mới.
- PASS: 16 migration được ghi nhận; chạy migrate lần hai không thêm bản ghi.
- PASS: seed tạo 3 user và 5 role.
- PASS: password hash hợp lệ, không là plaintext.
- PASS: seed hai lần không đổi số lượng ở 10 bảng được kiểm tra.
- PASS: bảo toàn credential/account state khi seed lại.
- PASS: unique organization code; unique domain trong organization; domain FK từ chối organization không tồn tại.
- PASS: unique email trong organization; một profile/user.
- PASS: không có foreign key xuyên database trong schema Identity.
- PASS: inventory database nguồn không đổi trong lần kiểm thử cuối.
- PASS: xác nhận riêng cuối cùng identity_db vẫn có 16 bảng nghiệp vụ rỗng và ledger 16 dòng.

Các database thử được giữ lại, không tự xóa: `identity_verify_20260916_021746_30d7eb`, `identity_verify_20260916_021953_34e13d`, `identity_verify_20260916_024526_8d639b`, `identity_verify_20260916_024557_36cd4a`, `identity_verify_20260916_025121_bf72b8`, `identity_verify_20260916_030142_833ecf`. Các bản thử lỗi chỉ là bằng chứng chẩn đoán, không phải môi trường hoạt động.

## Giới hạn và việc tiếp theo

PLAT-006 mới được kiểm chứng cho **Identity**, chưa hoàn tất toàn bộ service/P0 graph metadata. PLAT-004 không còn blocker credential Identity, nhưng các dependency khác chưa kiểm chứng. Chưa đánh dấu toàn bộ Foundation DONE.

Identity vẫn thiếu kiểm chứng/rule cho department cycle, primary membership, unique role assignment với nullable scope, session revoke; user import schema chưa có. Cần rà soát password version/lock state theo baseline trước auth. Seed role đã idempotent trong test, chưa chứng minh phân quyền runtime.

Tiếp theo: rà soát phần Identity schema còn thiếu, rồi auth/session + protected Gateway route. Chỉ seed database làm việc khi đã chọn rõ mục đích dữ liệu demo.
