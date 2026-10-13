# Cách 2 — Build và chạy bằng Docker

> Thực hiện sau khi ứng dụng chạy local. Chưa xác nhận build/run Docker hoàn chỉnh; không dùng tài liệu này để đánh dấu DONE.

## Hiện trạng đã đọc từ source

- `infrastructure/docker/compose.yaml` khai báo MySQL, MongoDB, Redis, RabbitMQ, Neo4j và MinIO.
- Compose này chỉ chạy hạ tầng, chưa đóng gói Web, Gateway và các Laravel/FastAPI service cùng nhau.
- AI Knowledge có Dockerfile riêng; chưa đủ chứng minh toàn bộ hệ thống chạy Docker.
- Lần kiểm tra trước chỉ xác thực cú pháp Compose; trạng thái container chưa được kiểm tra vì quyền truy cập Docker socket không được cấp. Đây không là blocker của cách chạy local.

## Công việc cần hoàn thiện sau local

1. `PLAT-016`: kiểm tra Compose hạ tầng và health từng thành phần cần dùng; Neo4j dùng profile graph demo. Không đổi volume/database local hiện có.
2. `PLAT-017`: bổ sung Dockerfile cho ứng dụng trong phạm vi được mở; build theo lockfile, quản lý config/secret ngoài image.
3. Cấu hình mạng container: service gọi hostname Compose, không dùng `127.0.0.1` để gọi container khác. Browser vẫn gọi Gateway qua cổng host.
4. Tách cấu hình và dữ liệu Docker khỏi môi trường local, tránh trùng cổng. Chạy migration theo service với database đích đã xác nhận.
5. `PLAT-018`: build/start bằng Compose, kiểm tra health, kết nối, E2E cùng tiêu chí local; ghi image/version, lệnh và kết quả.

## Lệnh đang có cho hạ tầng

Chạy từ `infrastructure/docker`, chỉ khi chuyển sang kiểm chứng Docker và `.env` đã được chuẩn bị:

```sh
docker compose config --quiet
docker compose up -d
docker compose ps
```

Đây chỉ là hạ tầng. Chưa có lệnh Compose đầy đủ được xác minh cho ứng dụng; bổ sung lệnh build/run cụ thể khi hoàn thành PLAT-017. Không chạy `down -v`, reset hoặc xóa database/volume đang có.

Xem [local trước](LOCAL_DEVELOPMENT.md) và [tracker Foundation](../specs/phase-0-foundation-identity/01-foundation.md).
