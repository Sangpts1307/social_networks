# FINAL - Quyết định kiến trúc dự án

## 1. Vai trò của tài liệu

Đây là tài liệu quyết định kiến trúc hiện hành và là nguồn tham chiếu ưu tiên cao nhất của thư mục `social_networks`.

Khi triển khai ở những lần sau phải đọc file này trước. Nếu tài liệu cũ, sơ đồ cũ hoặc đề xuất khác mâu thuẫn với file `Cấu trúc cuối cùng.md` thì áp dụng nội dung trong file này, trừ khi chủ dự án đưa ra quyết định mới rõ ràng.

## 2. Mục tiêu hệ thống

Xây dựng nền tảng doanh nghiệp gồm:

- Mạng xã hội và truyền thông nội bộ.
- Chat cá nhân, chat nhóm và thông báo realtime.
- Hệ thống học tập, khóa học, tài liệu và Q&A.
- Gamification và điểm uy tín theo chuyên môn.
- AI xử lý tài liệu và nội dung.
- Knowledge Graph, Expert Finder, gợi ý học tập và tìm kiếm lai.
- Web VueJS và ứng dụng React Native + Expo cùng sử dụng một hệ thống API.

## 3. Kiến trúc bắt buộc

Hệ thống sử dụng kiến trúc microservice. Không chuyển dự án thành modular monolith nếu chưa có yêu cầu mới từ chủ dự án.

Luồng truy cập chính:

```text
Web VueJS ----------+
React Native -------+--> NodeJS/NestJS API Gateway --> các service nghiệp vụ

Admin Portal (Filament 5)
    --> admin-service --> API Gateway / internal admin APIs --> service sở hữu dữ liệu

Các service -- REST nội bộ / RabbitMQ events --> các service khác
```

### Client

- Web: Vue 3 + TypeScript + TailwindCSS.
- Mobile: React Native + Expo + TypeScript.
- Web và Mobile chỉ gọi API Gateway, không gọi trực tiếp service phía sau.
- Admin Portal: Laravel MVC + Filament 5 (Blade, Livewire và Alpine.js), không
  dùng Vue. Admin Portal gọi API đã được Gateway/service công bố; không truy cập
  trực tiếp database của service khác.

### API Gateway

- Công nghệ: NodeJS + NestJS.
- Là entry point duy nhất cho Web và Mobile.
- Chịu trách nhiệm route request, xác thực token, rate limiting, correlation ID, chuẩn hóa lỗi và kết nối realtime ở biên hệ thống.
- Gateway không chứa nghiệp vụ và không sở hữu dữ liệu nghiệp vụ.
- Gateway không truy cập trực tiếp database của service.

### Backend nghiệp vụ

Các service nghiệp vụ chính viết bằng PHP Laravel:

1. `admin-service` — cổng vận hành Filament 5, không sở hữu dữ liệu nghiệp vụ
   của các service khác.
2. `identity-service`
3. `social-service`
4. `learning-service`
5. `chat-service`
6. `notification-service`

AI và tìm kiếm viết bằng Python vì cần hệ sinh thái NLP/LLM:

7. `ai-knowledge-service`

Hybrid Search nằm trong `ai-knowledge-service` ở giai đoạn hiện tại. Chỉ tách thành Search Service độc lập khi tải tìm kiếm hoặc vòng đời triển khai thực sự yêu cầu.

Không áp dụng DDD nhiều tầng một cách máy móc. Mỗi Laravel service ưu tiên cấu trúc Laravel tiêu chuẩn với `Http`, `Models`, `Services`, `Jobs`, `Events`, `Listeners`, `Notifications`, `Policies`, `database` và `routes`. Chỉ thêm abstraction khi có nhu cầu thực tế.

## 4. Phạm vi và database của từng service

| Service | Trách nhiệm sở hữu | Database chính |
| --- | --- | --- |
| Admin | Giao diện vận hành Filament, session/cached view state và read model audit khi được mở phạm vi; chỉ gọi API để thao tác dữ liệu nghiệp vụ | Không có domain database trong phase đầu; chỉ thêm `admin_db` riêng khi cần session/audit read model |
| Identity | Tài khoản, hồ sơ, phòng ban, vai trò, quyền, phiên đăng nhập | MySQL riêng |
| Social | Bài viết, media metadata, bình luận, reaction, upvote, reputation | MySQL riêng |
| Learning | Khóa học, bài học, tài liệu, tiến độ, câu hỏi và câu trả lời | MySQL riêng |
| Chat | Conversation, thành viên, message, trạng thái đọc | MongoDB riêng |
| Notification | Thông báo trong ứng dụng, thiết bị nhận push, lịch sử gửi | MySQL riêng |
| AI Knowledge | Job AI, tài liệu đầu vào, chunk metadata, AI suggestion, hàng đợi kiểm duyệt và Hybrid Search | MySQL riêng; Neo4j cho Knowledge Graph |

Trong môi trường phát triển có thể chạy một MySQL Server nhưng phải tạo database/schema riêng cho từng service. Không dùng chung bảng giữa các service. Khi triển khai production có thể tách các database sang instance khác mà không thay đổi ranh giới nghiệp vụ.

MongoDB chỉ dành cho dữ liệu Chat Service trong quyết định hiện tại. Không chuyển dữ liệu chat sang MySQL nếu chưa có quyết định mới.

AI Knowledge Service sử dụng MySQL cho metadata nghiệp vụ AI và trạng thái pipeline. Neo4j tiếp tục được sử dụng để lưu node/edge của Knowledge Graph. Không sử dụng `pgvector` vì dự án đã chốt MySQL thay cho PostgreSQL.

## 5. Quy tắc liên kết dữ liệu giữa các database

Database của các service không tự tìm, join hoặc tạo foreign key trực tiếp sang database khác.

Áp dụng các cơ chế sau:

1. **UUID liên service**: Ví dụ Social Service chỉ lưu `author_id` là UUID do Identity Service cấp.
2. **REST nội bộ**: Dùng khi cần dữ liệu mới nhất và request phải có kết quả ngay.
3. **RabbitMQ event**: Dùng để đồng bộ dữ liệu và xử lý bất đồng bộ.
4. **Read model cục bộ**: Service nhận event và lưu bản sao tối thiểu cần thiết, ví dụ `user_id`, `display_name`, `avatar_url`.
5. **Eventual consistency**: Chấp nhận bản sao dữ liệu giữa các service có độ trễ ngắn.
6. **Không chia sẻ migration hoặc model ORM** giữa các service.

Ví dụ:

```text
Identity cập nhật hồ sơ
    -> phát user.profile.updated
    -> Social, Chat, Learning nhận event
    -> cập nhật read model cục bộ nếu cần
```

Nếu một service không cần lưu bản sao, service đó gọi API nội bộ của service sở hữu dữ liệu. API nội bộ không đi vòng qua database.

## 6. Giao tiếp giữa các service

### Đồng bộ

- REST/JSON cho các thao tác cần phản hồi ngay.
- Định nghĩa API trong `contracts/openapi`.
- Internal API phải có service authentication.
- Mọi request cần có `correlation_id` để theo dõi xuyên service.

### Bất đồng bộ

- RabbitMQ là message broker chính.
- Event schema đặt trong `contracts/events`.
- Tên event dùng dạng quá khứ: `user.created`, `post.published`, `comment.created`, `lesson.completed`, `document.uploaded`.
- Consumer phải idempotent để xử lý an toàn khi event được giao lại.
- Event quan trọng phải có `event_id`, `event_type`, `occurred_at`, `producer`, `version` và `payload`.
- Khi triển khai dữ liệu thật, ưu tiên Outbox Pattern để tránh ghi database thành công nhưng phát event thất bại.

## 7. Xác thực và phân quyền

- Identity Service là nguồn dữ liệu duy nhất về tài khoản, vai trò và quyền.
- Identity Service phát access token; ưu tiên JWT ký bất đối xứng để Gateway và các service xác minh bằng public key.
- Gateway kiểm tra token trước khi forward request.
- Service vẫn phải kiểm tra quyền nghiệp vụ đối với tài nguyên do chính service sở hữu.
- Không lưu mật khẩu hoặc dữ liệu xác thực trong service khác.

## 8. Realtime, cache và file

- Realtime có thể dùng Pusher ở giai đoạn đầu hoặc triển khai gateway WebSocket khi cần.
- Chat Service sở hữu message; realtime chỉ là kênh truyền, không phải nguồn dữ liệu chính.
- Redis dùng cho cache, rate limit, presence, khóa phân tán và dữ liệu tạm thời; không dùng Redis làm nguồn dữ liệu nghiệp vụ lâu dài.  
- File nhị phân lưu trong object storage như MinIO/S3; database chỉ lưu metadata và object key.

## 9. Cấu trúc repository chuẩn

```text
social_networks/
├── apps/
│   ├── web/
│   └── mobile/
├── gateway/
│   └── api-gateway/
├── services/
│   ├── admin-service/             # Laravel MVC + Filament 5; Admin Portal
│   ├── identity-service/          # Laravel + MySQL
│   ├── social-service/            # Laravel + MySQL
│   ├── learning-service/          # Laravel + MySQL
│   ├── chat-service/              # Laravel + MongoDB
│   ├── notification-service/      # Laravel + MySQL
│   └── ai-knowledge-service/      # Python + MySQL + Neo4j; gồm Hybrid Search
├── contracts/
│   ├── openapi/
│   └── events/
├── infrastructure/
│   └── docker/
├── design/
├── docs/
├── Cấu trúc cuối cùng.md
└── README.md
```

Không đặt business code dùng chung giữa các service. Chỉ chia sẻ contract, schema hoặc thư viện kỹ thuật rất nhỏ và có version rõ ràng.

Đây là cấu trúc base tối thiểu. Không tạo trước cây `src`, `app`, `modules`, `feature`, `database`, `monitoring` hoặc `deployment` rỗng. Khi bắt đầu một component, dùng công cụ scaffold chính thức của Laravel, NestJS, Vue hoặc Android rồi chỉ giữ các thư mục thực sự có code/cấu hình.

## 10. Quy tắc cho giai đoạn dựng base

- Tạm thời không tạo thư mục `test`, `tests`, `Feature`, `Unit`, `Integration`, `e2e` hoặc `androidTest` trong cây source.
- Chưa cần triển khai Kubernetes.
- Chưa cần service mesh.
- Chưa cần CQRS/Event Sourcing đầy đủ.
- Chưa cần tách mỗi service thành repository riêng; monorepo hiện tại được giữ nguyên.
- Chưa thêm công nghệ mới nếu Laravel, MySQL, MongoDB, Redis, RabbitMQ và Neo4j đã đáp ứng yêu cầu.
- Khi khởi tạo Laravel/NestJS bằng công cụ scaffold, phải loại bỏ thư mục test được sinh tự động trong giai đoạn này.

Quy định “chưa tạo test” chỉ áp dụng cho giai đoạn dựng base theo yêu cầu hiện tại, không có nghĩa dự án production không cần kiểm thử. Chỉ bổ sung lại khi chủ dự án yêu cầu.

## 11. Thứ tự triển khai

1. Docker Compose cho MySQL, MongoDB, Redis, RabbitMQ, Neo4j và MinIO.
2. API Gateway NestJS với health check, proxy, auth middleware và correlation ID.
3. Identity Service và luồng đăng nhập end-to-end.
4. Web VueJS kết nối Gateway.
5. Social Service và Notification Service.
6. Learning Service.
7. Chat Service và realtime.
8. AI Knowledge Service, Hybrid Search, quy trình curation và Neo4j.
9. React Native Mobile kết nối cùng API Gateway.

## 12. Chỉ dẫn cho các lần làm việc tiếp theo

Trước khi sửa hoặc sinh code:

1. Đọc `Cấu trúc cuối cùng.md`.
2. Xác định đúng service sở hữu nghiệp vụ và dữ liệu.
3. Không cho service truy cập trực tiếp database của service khác.
4. Không bỏ NodeJS API Gateway.
5. Không đổi các PHP Laravel core service sang NodeJS hoặc một monolith.
6. Giữ MongoDB cho Chat Service.
7. Giữ MySQL database riêng cho từng service còn lại và cho metadata AI; chỉ
   tạo `admin_db` khi Admin Portal thực sự cần dữ liệu riêng (session/audit read
   model), không dùng nó làm bản sao ghi cho nghiệp vụ.
8. Giữ Neo4j cho Knowledge Graph.
9. Không tự động tạo test trong giai đoạn base hiện tại.
10. Nếu cần thay đổi một quyết định trên, phải cập nhật lại file này cùng lúc với source code.

## 13. Quyết định mobile — 2026-10-16

Chủ dự án thay thế lựa chọn Kotlin/Java bằng React Native + Expo + TypeScript, Android/iOS và bố cục thích ứng tablet. NativeWind 4 + Tailwind 3 dùng cho className; không dùng cấu hình Tailwind 4 của web cho mobile. Khởi đầu với App.tsx và src/screens, src/components, src/services, src/types; chỉ tạo thư mục khi có code. Tách component trình bày, gọi API trong services, xử lý loading/error bằng if. Chưa cần Redux, Expo Router hay abstraction nhiều tầng. Mobile chỉ gọi Gateway; token nhạy cảm dùng SecureStore; push/realtime theo contract backend. Xem [thiết kế mobile](docs/specs/phase-5-mobile/02-mobile-design.md) và [hướng dẫn cài](apps/mobile/README.md). Stack đã chốt; source demo được khởi tạo ngày 2026-10-19. Chưa nghiệm thu API thật/native.
