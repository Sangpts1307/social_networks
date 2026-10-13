# Docker infrastructure — cách chạy thứ hai

Ưu tiên [chạy local trực tiếp](../../docs/Base_du_an/LOCAL_DEVELOPMENT.md) bằng môi trường đã cài. Docker không cần cho bước đó.

Compose hiện chỉ đóng gói hạ tầng, chưa phải full application stack. Quy trình đóng gói/kiểm chứng sau local nằm tại [Docker development](../../docs/Base_du_an/DOCKER_DEVELOPMENT.md), task PLAT-016–018.

Khi mở bước Docker: chuẩn bị `.env` nếu chưa có, xác nhận port/volume và chạy `docker compose config --quiet`, rồi `docker compose up -d`, `docker compose ps`. Init MySQL chỉ chạy khi data directory mới; không dùng reset volume để ép init lại. Không xóa dữ liệu hoặc ghi đè `.env` đang có.
## MinIO cho Learning documents

`minio` được Docker build từ MinIO source tại `minio/Dockerfile`; `minio-init` tự tạo bucket
`learning-private` khi Compose khởi động. Không tạo bucket qua UI và không bật
anonymous/public access.

```bash
cd infrastructure/docker
docker compose up -d minio minio-init
docker compose logs --no-log-prefix minio-init
docker compose ps minio minio-init
```

Console local: `http://127.0.0.1:9001`. API S3-compatible: `http://127.0.0.1:9000`.

Đăng nhập Console bằng `MINIO_ROOT_USER` và `MINIO_ROOT_PASSWORD` trong `.env`.
`minio-init` là job một lần nên trạng thái `Exited (0)` sau log
`Private bucket learning-private is ready.` là đúng. Bucket không public; ứng dụng chỉ
phát URL tải có chữ ký ngắn hạn sau khi người dùng đã được Gateway xác thực.

Sau khi đã chạy MinIO lần đầu, cấu hình `services/learning-service/.env` cho local:

```dotenv
LEARNING_DOCUMENT_DISK=s3
LEARNING_TRUST_LOCAL_UPLOADS=true
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minio_secret_change_me
AWS_BUCKET=learning-private
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

`LEARNING_TRUST_LOCAL_UPLOADS=true` chỉ phục vụ máy local để luồng upload → duyệt →
publish có thể chạy ngay. Khi lên môi trường dùng chung/production, đặt giá trị này về
`false` và chỉ cho publish sau khi worker antivirus cập nhật `scan_status=clean`.
