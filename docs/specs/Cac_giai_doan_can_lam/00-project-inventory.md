# Project Inventory

> **Đối soát Foundation 2026-09-16:** đã có source, model/migration, Web/Gateway build và HTTP health local PASS. Các nhận định “chưa có source” bên dưới là snapshot cũ, không còn dùng cho Foundation. Xem [bằng chứng hiện tại](../../Base_du_an/FOUNDATION_AUDIT_2026-09-16.md). Chưa xác minh business flow/auth hay migration database; không nâng feature lên VERIFIED_IMPLEMENTED.

## Phạm vi kiểm kê

Thời điểm kiểm kê: 2026-09-10. Phạm vi sản phẩm là thư mục `social_networks/`. Thư mục gốc `D:\ĐATN` còn chứa tài liệu phân tích, một demo Knowledge Graph và bộ kỹ năng AI; các nội dung đó không được coi là runtime code của `social_networks` nếu không được tham chiếu rõ ràng.

### Quy ước trạng thái bằng chứng

| Trạng thái | Ý nghĩa dùng trong bộ spec |
| --- | --- |
| `VERIFIED_IMPLEMENTED` | Có source/config/runtime artifact chứng minh chức năng chạy được. |
| `VERIFIED_PARTIAL` | Có phần artifact thật, nhưng thiếu phần cần thiết để hoàn thành chức năng. |
| `DOCUMENTED_NOT_IMPLEMENTED` | Có tài liệu/thiết kế mô tả, nhưng không có source implementation tương ứng. |
| `INFERRED` | Diễn giải hợp lý từ bằng chứng; không phải yêu cầu đã duyệt. |
| `UNKNOWN` | Không đủ bằng chứng để kết luận. |
| `TODO / SPEC_GAP` | Thiếu quyết định hoặc chi tiết bắt buộc trước khi triển khai. |

## Repository Structure

```text
social_networks/
├── apps/
│   ├── web/                         # README, chưa có Vue source
│   └── mobile/                      # README, chưa có Android source
├── contracts/
│   ├── events/                      # README, chưa có event schema
│   └── openapi/                     # README, chưa có OpenAPI document
├── design/
│   ├── exports/html-tailwind/       # 2 mock HTML có nội dung; 8 HTML placeholder
│   └── stitch/                      # Link dự án Google Stitch
├── docs/                            # README và bộ specs này
├── gateway/api-gateway/             # README, chưa có NestJS source
├── infrastructure/docker/           # README, chưa có Compose/Dockerfile
├── services/
│   ├── identity-service/            # README, chưa có Laravel source
│   ├── social-service/              # README, chưa có Laravel source
│   ├── learning-service/            # README, chưa có Laravel source
│   ├── chat-service/                # README, chưa có Laravel source
│   ├── notification-service/        # README, chưa có Laravel source
│   └── ai-knowledge-service/        # README, chưa có Python source
├── README.md
└── Cấu trúc cuối cùng.md             # Quyết định kiến trúc ưu tiên cao nhất
```

## Applications and Services Found

| Artifact | Trạng thái hiện tại | Vai trò được tài liệu mô tả | Bằng chứng |
| --- | --- | --- | --- |
| Web App | `VERIFIED_PARTIAL` | Vue 3 + TypeScript + TailwindCSS; chỉ gọi Gateway. Chưa có source app. | `apps/web/README.md`; `design/exports/html-tailwind/01-welcome.html`; `02-login.html` |
| Android Mobile App | `DOCUMENTED_NOT_IMPLEMENTED` | Android Native Kotlin/Java; chỉ gọi Gateway. | `apps/mobile/README.md` |
| API Gateway | `DOCUMENTED_NOT_IMPLEMENTED` | NodeJS/NestJS boundary cho Web/Mobile. | `gateway/api-gateway/README.md` |
| Identity Service | `DOCUMENTED_NOT_IMPLEMENTED` | Account, profile, department, role, permission, token. | `services/identity-service/README.md` |
| Social Service | `DOCUMENTED_NOT_IMPLEMENTED` | Post, media metadata, comment, reaction, upvote, reputation. | `services/social-service/README.md` |
| Learning Service | `DOCUMENTED_NOT_IMPLEMENTED` | Course, lesson, document, learning progress, Q&A, FAQ. | `services/learning-service/README.md` |
| Chat Service | `DOCUMENTED_NOT_IMPLEMENTED` | Conversation, member, message, read state. | `services/chat-service/README.md` |
| Notification Service | `DOCUMENTED_NOT_IMPLEMENTED` | In-app notification, push device, send history. | `services/notification-service/README.md` |
| AI Knowledge Service | `DOCUMENTED_NOT_IMPLEMENTED` | AI pipeline, AI metadata/chunk, curation, KG, expert finder, recommendation, hybrid search. | `services/ai-knowledge-service/README.md` |

## Frameworks and Languages

| Thành phần | Current implementation | Intended/documented stack |
| --- | --- | --- |
| Web | Không có `package.json`, source hoặc build config. | Vue 3, TypeScript, TailwindCSS |
| Mobile | Không có Gradle/Kotlin/Java source. | Android Native Kotlin/Java |
| Gateway | Không có `package.json`, NestJS source hoặc config. | NodeJS + NestJS |
| Core services | Không có `composer.json`, Laravel source, route hoặc config. | PHP + Laravel |
| AI | Không có `requirements.txt`, `pyproject.toml`, FastAPI/worker source. | Python; framework chưa chốt bằng source |

**Evidence:** repository file inventory; `README.md`; `Cấu trúc cuối cùng.md`.

## Data, Messaging, Search and Storage

| Thành phần | Current state | Intended/documented state |
| --- | --- | --- |
| MySQL | `NOT_FOUND`: không có schema, migration, connection config. | Database riêng cho Identity, Social, Learning, Notification và AI metadata. |
| MongoDB | `NOT_FOUND`: không có schema/config. | Database riêng cho Chat. |
| Neo4j | `NOT_FOUND`: không có config/query/driver. | Knowledge Graph store của AI Knowledge Service. |
| Redis | `NOT_FOUND`: không có config/usage. | Cache, rate limit, presence, distributed lock, dữ liệu tạm. |
| RabbitMQ | `NOT_FOUND`: không có event schema, producer/consumer hoặc worker. | Broker bất đồng bộ chính. |
| MinIO/S3 | `NOT_FOUND`: không có config/client. | Object storage cho binary files. |
| Hybrid Search | `NOT_FOUND`: không có index, vector store hoặc query code. | Thuộc AI Knowledge Service trong giai đoạn hiện tại. |

**Evidence:** `Cấu trúc cuối cùng.md`, sections 4, 6, 8; toàn bộ file inventory không có runtime config tương ứng.

## Realtime and External Services

Không có WebSocket, broadcasting, Pusher, FCM, Algolia, Firebase, outbound client hoặc secret/config hiện hữu trong `social_networks`.

Tài liệu cũ đề cập Pusher, Firebase và Algolia; chúng là `DOCUMENTED_NOT_IMPLEMENTED`, đồng thời có mâu thuẫn cần quyết định với kiến trúc ưu tiên hiện hành. Xem [14-gap-analysis.md](14-gap-analysis.md) và [OPEN_QUESTIONS.md](../OPEN_QUESTIONS.md).

## Deployment and Test Infrastructure

| Hạng mục cần tìm | Kết quả |
| --- | --- |
| `Dockerfile*`, `docker-compose*` | `NOT_FOUND` |
| `.env.example` | `NOT_FOUND` |
| CI/CD config | `NOT_FOUND` |
| Docker/Kubernetes/nginx/deployment script | `NOT_FOUND` |
| Composer/NPM/Python dependency manifests | `NOT_FOUND` |
| Automated test source/configuration | `NOT_FOUND` |
| Git repository metadata trong `social_networks` | `NOT_FOUND` |

## Design Artifacts

| File | Phân loại | Kết luận |
| --- | --- | --- |
| `design/exports/html-tailwind/01-welcome.html` | Static UI reference | Có mock welcome/dashboard với Social, Learning, notification, course progress và pending message. Không gọi API. |
| `design/exports/html-tailwind/02-login.html` | Static UI reference | Có form email/password, remember-me và link forgot-password. Script chặn submit và mô phỏng thành công; không xác thực thật. |
| Các file `03`, `05`-`09`, `14`, `15` | Static placeholders | Chỉ chứa title và comment “Dán code giao diện ... tại đây”; không có screen implementation. |
| `design/stitch/project-link.md` | Design process link | Mô tả luồng Stitch -> HTML/Tailwind -> Vue; không phải source Vue. |

## Ancillary Artifacts Outside Product Directory

| Artifact | Phân loại | Kết luận |
| --- | --- | --- |
| `Mô tả đề tài.docx` | Intended business/design document | Mô tả mục tiêu, domains và AI Knowledge Graph; không chứng minh implementation. |
| `ananylics/Social Networks.xlsx` | Intended requirements/WBS | Có yêu cầu UI/functional ban đầu; không có implementation status. |
| `ananylics/system_architecture*.md/png` | Older architecture proposals | Có một số stack mâu thuẫn với quyết định kiến trúc cuối. |
| `test_knowledge_graph/demo_knowledge_graph.py` | Standalone demo | NetworkX/PyVis graph dùng dữ liệu mock hard-coded; không import hoặc được gọi bởi `social_networks`. |
| `AI_agent/` | Tooling/skills collection | Không thuộc application runtime của sản phẩm. |

## Unknown Components and Inventory Gaps

1. Không có evidence về entry point, cách khởi động hay environment thực tế.
2. Không có version pinning cho bất cứ stack nào.
3. Không có API, event, schema, migration, seed data, model hay route.
4. Không thể xác minh quyền, business rule, validation, pagination, error response hoặc observability runtime.
5. Không thể kết luận các external SaaS (Pusher/Firebase/Algolia/LLM provider) đã được chọn hay tích hợp.

Chi tiết câu hỏi cần chủ dự án quyết định nằm tại [OPEN_QUESTIONS.md](../OPEN_QUESTIONS.md).
