# Đối soát Foundation — 2026-09-16

> **Cập nhật tiếp nối:** MySQL Identity đã kết nối và kiểm chứng migration/seed thành công. Có sự cố script tạo schema nhầm database và đã sửa ledger, không mất dữ liệu; đọc [báo cáo Identity](IDENTITY_DATABASE_VERIFICATION_2026-09-16.md). Các dòng MySQL auth BLOCKED bên dưới là kết quả trước khi chủ dự án sửa env.

## Phạm vi và dữ liệu

Source tiếp tục ở `social_networks/`; tận dụng code có sẵn, không scaffold lại. Đọc baseline hiện ở `docs/Base_du_an/IMPLEMENTATION_BASELINE.md` (đã được chuyển từ đường dẫn cũ). Không phát hiện AGENTS.md trong cây dự án đã tìm. Thư mục chưa có Git repository; không tự khởi tạo hoặc suy đoán remote/CI provider.

Đợt này không chạy migration/seed, không reset/xóa database, không ghi đè `.env`. Chỉ bổ sung cấu hình mẫu, quy tắc ignore, sửa Gateway startup và phục hồi dependencies Node để chạy trên macOS. Docker vẫn hoãn.

## Kiểm kê source và cách chạy

| Thành phần | Source hiện có | Cách chạy local / giới hạn |
|---|---|---|
| Web | Vue 3 + TypeScript + Vite/Tailwind; Login, Welcome | `apps/web`: `npm run dev`; cổng 5173; login hiện mô phỏng, chưa gọi Identity |
| Gateway | NestJS; CORS, request ID, registry URL, `/api/health` | `gateway/api-gateway`: `npm run start:dev`; cổng 8080; chưa có protected proxy/auth middleware |
| Identity | Laravel; 16 models, 16 migration files; 1 health route | `services/identity-service`: `php artisan serve --host=127.0.0.1 --port=8081`; `.env` trỏ MySQL 127.0.0.1:3306/identity_db |
| Social | Laravel; 20 models, 1 migration file; health route | cổng 8082; social_db; ngoài phạm vi triển khai nghiệp vụ đợt này |
| Learning | Laravel; 22 models, 3 migration files; health route | cổng 8083; learning_db; API dữ liệu graph chưa triển khai |
| Chat | Laravel; 7 models, Mongo index/seed scripts; health route | cổng 8084; Composer platform FAIL: ext-mongodb 2.3.3, dependency yêu cầu ^2.4 |
| Notification | Laravel; 11 models, 2 migration files; health route | cổng 8085; notification_db; nghiệp vụ thông báo chưa triển khai |
| AI Knowledge | FastAPI, Alembic, graph fixtures, Cypher seed, Dockerfile | cổng 8090; chưa có `.env`/venv tại service. API demo trả fixtures, không chứng minh Neo4j integration |

Model/migration file count không phải số bảng đã migrate hoặc số chức năng đã hoàn thành.

## Môi trường máy và blocker

- PHP 8.4.23, Composer 2.10.2, Node 24.21.0, npm 11.19.0.
- `python3` mặc định 3.9.6; Python 3.13.15 đã có tại `~/.pyenv/versions/3.13.15/bin/python`, đạt khai báo >=3.12 của AI service. Chưa cài lại Python.
- MySQL có listener cổng 3306. `php artisan migrate:status` với cấu hình Identity hiện tại trả **Access denied cho app@localhost** sau khi được phép kết nối. Chủ dự án đang cập nhật `.env`; chưa xác nhận cấu hình mới.
- Không thấy listener MongoDB/Redis/Neo4j/MinIO trên các cổng thường dùng trong snapshot `lsof`; chưa đủ bằng chứng kết luận chưa cài. Các kết nối này chưa kiểm chứng.
- Chat không chỉ cần extension có mặt: phiên bản phải thỏa Composer. Không nâng PHP extension toàn máy trong đợt này.
- Chưa xác nhận Git/CI remote; không thể đánh dấu CI green.

## Phần đã sửa

1. `.gitignore` ở root: bỏ qua env/credential/dependencies/build/cache, vẫn cho phép `.env.example`.
2. `.env.example` của Laravel MySQL chuyển mặc định sang 3306, password placeholder `CHANGE_ME`; Chat dùng mẫu Mongo local 27017; AI mẫu MySQL local/Neo4j password placeholder. `.env` thực tế giữ nguyên.
3. Gateway bổ sung `class-validator`, `class-transformer` vào package/lockfile: trước đó build được nhưng tiến trình chết khi khởi tạo ValidationPipe.
4. Gateway tự nạp `.env` bằng Node native loader; biến môi trường từ caller có ưu tiên. Mặc định bind 127.0.0.1; có `HOST` để đổi khi đóng gói sau. CORS defaults hỗ trợ localhost và 127.0.0.1:5173.
5. Khôi phục executable bit của launcher Node đã bị mất; bổ sung native optional dependencies đúng Mac ARM64 cho Web. Không đưa dependency Mac riêng vào package.json. Khi chuyển OS cần cài từ lockfile thay vì chép node_modules.
6. `scripts/local-doctor.py`: kiểm kê read-only, chỉ in các field kết nối không phải credential và platform failures.
7. `scripts/smoke-local.py`: khởi động ba ứng dụng ở cổng thử riêng, kiểm tra HTTP rồi dừng đúng tiến trình do script tạo. Không mutate database.

## Bằng chứng kiểm tra

| Kiểm tra | Kết quả |
|---|---|
| Gateway `npm run typecheck` + `npm run build` | PASS |
| Web `npm run build` | PASS, Vite 18 modules |
| `python3 scripts/smoke-local.py` | PASS: Gateway 18080/api/health, Identity 18081/api/v1/health, Web 15173/ trả HTTP 200; Gateway giữ X-Request-Id |
| PHP syntax app/routes/migrations/seeders/config/bootstrap | PASS 196 files; không lint vendor/cache |
| Laravel `route:list --path=api --json` | Cả 5 service có health route; chưa có business API |
| Composer platform Identity/Social/Learning/Notification | PASS |
| Composer platform Chat | FAIL ext-mongodb 2.3.3 vs ^2.4 |
| Identity `migrate:status` (chỉ đọc) | BLOCKED: MySQL từ chối tài khoản cấu hình |
| `.gitignore` qua Git tạm ngoài dự án | PASS: env/credential/dependency ignored; template được phép |
| Rà soát mẫu env và pattern private key/access key trong source | Không phát hiện private-key/access-key pattern trong phạm vi quét; không phải chứng nhận không có secret toàn hệ thống |

Các password cố định trong fixture/UI demo và fallback AI cũ là dữ liệu mẫu đã nhận diện, không dùng làm credential production. Seeder Identity dùng `updateOrCreate` và có thể ghi lại password/status user demo: không tự chạy trên dữ liệu hiện có. Auth API/profile/protected proxy chưa được kiểm tra vì chưa triển khai.

## Kết luận và bước tiếp

- DONE: PLAT-001 (kiểm kê), PLAT-002 (source foundation + hướng dẫn + build/start), PLAT-003 (mẫu cấu hình và quy tắc secret trong phạm vi kiểm tra).
- PLAT-004/006/015 chưa đạt do thiếu kết nối database hợp lệ và chưa kiểm thử migration/seed trên DB riêng.
- PLAT-008 chỉ đạt health/startup, còn protected proxy; PLAT-007/013 mới có UUID/UTC hoặc request ID cơ bản.
- PLAT-009 có contract health Identity/AI nhưng thiếu kiểm tra contract và chưa thống nhất version public Gateway; PLAT-010 mới có model/schema outbox/inbox, chưa có publish/consume idempotency runtime.
- Redis/object storage và CI chưa nghiệm thu. Docker PLAT-016–018 giữ DEFERRED.
- Khi chủ dự án báo đã cập nhật `.env`: thử lại `migrate:status`, xác nhận database kiểm thử riêng trước khi chạy migration. Sau đó rà soát Identity schema và thực hiện login qua Gateway.
