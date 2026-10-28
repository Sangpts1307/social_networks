# Enterprise Knowledge Platform

> **Tiến độ mới nhất 22/10/2026:** [Hiện trạng, bằng chứng kiểm thử và việc cần làm](docs/Base_du_an/SYSTEM_PROGRESS_2026-10-22.md). Backend đã mở rộng; chưa nghiệm thu toàn hệ thống.

Monorepo cho mạng xã hội nội bộ, học tập doanh nghiệp và AI Knowledge Graph.

Đọc [`Cấu trúc cuối cùng.md`](./Cấu%20trúc%20cuối%20cùng.md) trước khi triển khai. Đây là quyết định kiến trúc ưu tiên cao nhất của dự án.

## Cấu trúc

```text
social_networks/
├── apps/
│   ├── web/                       # Vue 3 + TypeScript + TailwindCSS
│   └── mobile/                    # React Native + Expo + TypeScript + NativeWind
├── gateway/
│   └── api-gateway/               # NodeJS + NestJS
├── services/
│   ├── identity-service/          # Laravel + MySQL
│   ├── social-service/            # Laravel + MySQL
│   ├── learning-service/          # Laravel + MySQL
│   ├── chat-service/              # Laravel + MongoDB
│   ├── notification-service/      # Laravel + MySQL
│   └── ai-knowledge-service/      # Python + MySQL + Neo4j; gồm hybrid search
├── contracts/
│   ├── openapi/                   # REST contracts
│   └── events/                    # RabbitMQ event contracts
├── infrastructure/
│   └── docker/                    # Môi trường phát triển cục bộ
├── design/                        # Bản thiết kế và HTML tham chiếu
├── docs/                          # Tài liệu phát sinh khi triển khai
└── Cấu trúc cuối cùng.md
```

## Chạy local toàn bộ service

Sau khi đã cài dependencies, cấu hình `.env` cho từng service và mở Docker Desktop,
chạy một lệnh từ thư mục gốc monorepo:

```bash
node run_all.js
```

Script bảo đảm MinIO private/bucket Learning trước, rồi khởi động theo thứ tự domain
service → Gateway → Admin → Web. Nó kiểm tra cổng trước khi sang service tiếp theo
và không đụng vào process đang chạy sẵn.
Nhấn `Ctrl+C` để dừng các process do script khởi động.

## Tài liệu Learning private

Tài liệu PDF/DOCX/XLSX được lưu private trong MinIO; MySQL của Learning chỉ lưu metadata,
phiên bản và hash. Xem [hướng dẫn MinIO](./infrastructure/docker/README.md) để chạy Docker,
tạo bucket tự động và cấu hình local. Sau khi cập nhật source lần đầu, chạy migration và seed
quyền trước khi đăng nhập lại:

```bash
cd services/learning-service && php artisan migrate
cd ../identity-service && php artisan db:seed --class=RbacSeeder
```

Các thư mục framework con chỉ được sinh bằng công cụ chính thức khi bắt đầu triển khai component tương ứng. Không dựng trước cây thư mục rỗng.

## Nguyên tắc

1. Web và Mobile chỉ gọi NodeJS API Gateway.
2. Mỗi service sở hữu database và migration riêng.
3. Không truy cập, join hoặc tạo foreign key xuyên database của service khác.
4. Giao tiếp đồng bộ qua REST; giao tiếp bất đồng bộ qua RabbitMQ.
5. MySQL dùng cho nghiệp vụ và metadata AI; MongoDB cho Chat; Neo4j cho Knowledge Graph.
6. Hybrid search thuộc `ai-knowledge-service` trong giai đoạn hiện tại.
7. Chưa tạo thư mục test trong giai đoạn dựng base.

## API toàn hệ thống (2026-10-21)

Xem [báo cáo coverage, cấu hình, quy tắc thưởng và giới hạn tích hợp](docs/api/API_COVERAGE_2026-10-21.md), [danh sách route từ source](docs/api/ROUTE_INVENTORY.md) và [OpenAPI bổ sung](contracts/openapi/platform-additions-v1.json). Không dùng lại tài liệu inventory cũ ghi 0 endpoint. Course/Lesson vẫn hoãn; Tri thức tích lũy points/coin chờ duyệt theo kỳ tuần/tháng, tách khỏi Social.
