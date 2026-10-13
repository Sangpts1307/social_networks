# Cách 1 — Chạy trực tiếp trên môi trường local

> Quyết định 2026-09-16: dùng môi trường đã cài trên máy trước. Docker không bắt buộc cho cách chạy này. Tài liệu là hướng dẫn cấu hình, chưa xác nhận mọi dịch vụ đã chạy thành công.

## 1. Kiểm tra môi trường có sẵn

Chạy từ terminal của máy; không cài lại nếu đã có phiên bản phù hợp:

```sh
php -v
php -m
composer --version
node --version
npm --version
~/.pyenv/versions/3.13.15/bin/python --version
```

- Laravel cần PHP và extension theo `composer.json`/`composer.lock`; dùng `composer check-platform-reqs` trong service khi đã có vendor.
- Gateway/Web dùng Node/npm theo package và lockfile. Máy hiện có Node 24; cần kiểm tra build thực tế.
- AI Knowledge khai báo Python >=3.12; `python3` đang được kiểm tra là 3.9.6 nhưng máy có `~/.pyenv/versions/3.13.15/bin/python`; chọn interpreter này khi tạo venv.
- Đã quan sát PHP 8.4 có `pdo_mysql`, `redis`, `mongodb` ngày 2026-09-16. Điều này chưa chứng minh MySQL/MongoDB/Redis đang chạy.
- Dùng MySQL, MongoDB, Redis, Neo4j, RabbitMQ, object storage cài trực tiếp trên máy theo nhu cầu task. Không bắt buộc chạy tất cả để mở Web/Identity.

## 2. Cấu hình kết nối local

Giữ nguyên `.env` hiện có. Chỉ sao chép `.env.example` khi chưa có `.env`, rồi chỉnh host/port/user/password theo dịch vụ trên máy. Không in secret vào báo cáo hay commit `.env`.

| Thành phần | Cấu hình cần kiểm tra | Lưu ý |
|---|---|---|
| Laravel MySQL | `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Local thường 3306, nhưng phải xác nhận. Template đã chuyển sang local 3306; Docker dùng cấu hình riêng |
| Chat MongoDB | Theo `.env.example` và `config/database.php` của Chat | Local thường 27017; không dùng migration MySQL cho Chat |
| Redis | `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` | Local thường 6379; kiểm tra auth theo máy |
| AI metadata | `DATABASE_URL` | Trỏ MySQL local, database `ai_knowledge_db` |
| Neo4j | `NEO4J_URI`, `NEO4J_USERNAME`, `NEO4J_PASSWORD` | Bolt thường 7687; kiểm tra tài khoản local |
| Gateway | `PORT`, `ALLOWED_ORIGINS`, các `*_SERVICE_URL` | Trỏ ứng dụng local; Web chỉ gọi Gateway |
| Object storage / broker | Theo adapter và task tương ứng | Chỉ cấu hình khi task cần, chưa mặc định đã tích hợp |

Service sở hữu database riêng: `identity_db`, `social_db`, `learning_db`, `notification_db`, `ai_knowledge_db`; Chat dùng `chat_db` MongoDB. Không tự thay đổi credential hay dữ liệu dịch vụ đang chạy.

## 3. Chuẩn bị dependencies và database

Từ thư mục ứng dụng tương ứng, dùng `composer install` cho Laravel và `npm ci` cho Gateway/Web khi cần cài dependencies theo lockfile.

Trong Laravel: chỉ tạo `APP_KEY` với `php artisan key:generate` khi môi trường mới chưa có key; không thay key đang sử dụng. Nếu thay `.env` mà có config cache, dùng `php artisan config:clear`.

Kiểm tra trước bằng:

```sh
php artisan migrate:status
php artisan route:list --path=api
```

Khi đã chọn đúng database và kiểm tra các migration pending, dùng `php artisan migrate`. Kiểm tra migration trên database kiểm thử riêng; không dùng `migrate:fresh`, reset hoặc xóa dữ liệu hiện có. Chỉ chạy seeder sau khi đọc nội dung và xác định dữ liệu demo phù hợp; không mặc định `migrate --seed` cho mọi service.

Với AI: dùng Python 3.12+ tạo `.venv` nếu chưa có, kích hoạt venv, cài `requirements.txt`; cấu hình `.env` rồi kiểm tra `alembic current` trước khi quyết định `alembic upgrade head`. Neo4j dùng `scripts/seed_neo4j.cypher` sau khi kiểm tra database đích. Mongo index/seed thuộc Chat, làm khi mở phạm vi Chat.

## 4. Chạy ứng dụng — mỗi ứng dụng một terminal

Các thư mục dưới đây tính từ root `social_networks/`. Chỉ chạy service cần cho công việc.

| Ứng dụng | Thư mục | Lệnh |
|---|---|---|
| Web | `apps/web` | `npm run dev -- --host 127.0.0.1 --port 5173` |
| Gateway | `gateway/api-gateway` | `npm run start:dev` |
| Identity | `services/identity-service` | `php artisan serve --host=127.0.0.1 --port=8081` |
| Social | `services/social-service` | `php artisan serve --host=127.0.0.1 --port=8082` |
| Learning | `services/learning-service` | `php artisan serve --host=127.0.0.1 --port=8083` |
| Chat | `services/chat-service` | `php artisan serve --host=127.0.0.1 --port=8084` |
| Notification | `services/notification-service` | `php artisan serve --host=127.0.0.1 --port=8085` |
| AI Knowledge | `services/ai-knowledge-service` (venv đã kích hoạt) | `python -m uvicorn app.main:app --reload --host 127.0.0.1 --port 8090` |

Gateway tự nạp `.env` nếu có; biến môi trường caller đã đặt giữ ưu tiên. Mặc định bind `127.0.0.1`; CORS cho localhost và 127.0.0.1:5173. Đặt `HOST=0.0.0.0` khi triển khai container sau.

Laravel REST API không cần chạy Vite riêng trong mỗi service. Với đăng nhập, bắt đầu bằng Web + Gateway + Identity + MySQL; thêm Learning/Neo4j/AI khi làm graph demo.

## 5. Kiểm tra và ghi nhận

```sh
# Chạy trong gateway/api-gateway
npm run typecheck
npm run build

# Chạy trong apps/web
npm run build

# Khi tiến trình đã chạy
curl --fail http://127.0.0.1:8080/api/health
curl --fail http://127.0.0.1:8081/api/v1/health
```

Health chỉ xác nhận endpoint trả lời; phải kiểm tra kết nối database và luồng nghiệp vụ riêng. Ghi lệnh, kết quả và phần chưa đạt vào [Foundation](../specs/phase-0-foundation-identity/01-foundation.md). Sau khi local đạt, chuyển sang [cách 2 — Docker](DOCKER_DEVELOPMENT.md).

## Công cụ kiểm tra đã thêm

Từ root `social_networks/`:

```sh
python3 scripts/local-doctor.py
npm run typecheck --prefix gateway/api-gateway
npm run build --prefix gateway/api-gateway
npm run build --prefix apps/web
python3 scripts/smoke-local.py
```

Smoke dùng cổng 18080, 18081, 15173 rồi tự dừng các tiến trình của nó; không kiểm tra DB/auth. Lệnh cần quyền mở cổng loopback. Khi chuyển từ Windows sang Mac, cài dependencies theo lockfile trên đúng máy; không copy node_modules. Nếu thiếu optional native bindings, xem lỗi package/phiên bản và cài đúng nền tảng, không xóa lockfile để thử ngẫu nhiên.

**Hiện trạng 2026-09-16:** Web/Gateway build và HTTP smoke PASS. MySQL credential Identity còn bị từ chối; không chạy seed trước khi xác nhận DB. Seeder Identity ghi lại password/status tài khoản demo, nên chỉ dùng trên database demo/kiểm thử riêng đã được xác nhận. AI dùng Python 3.13 đã cài; Chat cần ext-mongodb ^2.4 trong khi hiện có 2.3.3.

Xem [báo cáo đầy đủ](FOUNDATION_AUDIT_2026-09-16.md).

## Cập nhật kiểm chứng Identity

MySQL Identity đã kết nối. `identity_db` hiện có 16 bảng nghiệp vụ trống + ledger 16 migration; chưa có user demo. Xem [kết quả và sự cố script đã khắc phục](IDENTITY_DATABASE_VERIFICATION_2026-09-16.md) — thông tin này thay thế blocker credential ở phần lịch sử bên trên.

Từ root dự án:

```sh
# Chỉ đọc số lượng bảng/bản ghi; không in credential hay dữ liệu người dùng
php scripts/verify-identity-database.php

# Tạo một database thử MỚI, chạy migration + seed + kiểm tra; không reset database hiện có
python3 scripts/test-auth-local.py
```

Lệnh `--verify` yêu cầu account local có quyền tạo database thử. Runner mới tự dọn database/tài khoản thử do chính nó tạo; từ chối nếu chúng đã tồn tại. Script là kiểm thử Identity, không thay cho việc nghiệm thu tất cả service.

## Auth backend — 2026-09-17

Đã áp dụng migration auth bổ sung. Gateway/Identity `.env` local có chung IDENTITY_GATEWAY_KEY riêng; template để trống, không copy key vào Web. Login Vue chưa tích hợp. Xem [endpoint, kiểm thử và cách chạy](AUTH_SESSION_IMPLEMENTATION_2026-09-17.md). Runner cũ tạo identity_verify_* đã bị vô hiệu hóa; test-auth-local.py dùng một DB/account có quyền giới hạn và tự dọn sau khi chạy.
