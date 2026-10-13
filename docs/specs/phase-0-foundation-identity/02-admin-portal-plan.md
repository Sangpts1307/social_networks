# ADM — Admin Portal dùng Filament 5

[← Phase 0](README.md) · [Quyết định kiến trúc](../../../Cấu%20trúc%20cuối%20cùng.md) · [Admin Identity API](../../../contracts/openapi/admin-identity-v1.yaml)

> Quyết định 2026-09-27: xây dựng một `admin-service` độc lập bằng Laravel MVC
> và Filament 5. Đây là cổng vận hành tập trung; Vue không nằm trong phạm vi
> Admin Portal. `identity-service` vẫn là nguồn sự thật duy nhất cho tài khoản,
> RBAC và phiên đăng nhập.

## Mục tiêu phase đầu

Đưa Admin Portal chạy local tại `http://127.0.0.1:8086/admin`, với đăng nhập
qua Identity và bốn chức năng có dữ liệu thật: dashboard, quản lý người dùng,
phòng ban và role/permission. Chưa triển khai moderation Social/Learning trong
phase này.

## Ranh giới bắt buộc

```text
Browser
  -> admin-service /admin (Filament: Blade + Livewire)
  -> API Gateway /api/v1/admin/*
  -> identity-service
  -> identity_db
```

- `admin-service` không import model, migration hoặc kết nối database của
  `identity-service`, `social-service` hay `learning-service`.
- API Gateway không chứa business logic hoặc truy vấn database; Gateway chuyển
  tiếp identity/token/correlation ID tới service sở hữu nghiệp vụ.
- `admin-service` không tạo password, role hay user cục bộ. Token Identity chỉ
  được giữ trong Laravel session được mã hóa, không trả về browser JavaScript.
- Identity và service sở hữu dữ liệu phải kiểm tra permission ở phía mình; menu
  Filament bị ẩn không được coi là biện pháp authorization.
- Chưa tạo `admin_db` trong phase đầu nếu session file/Redis đáp ứng. Nếu mở audit
  aggregate sau này, DB đó chỉ lưu read model riêng của Admin Portal.

## Cấu trúc source khi bắt đầu code

Chỉ scaffold các thư mục có mã nguồn thực tế bằng công cụ Laravel/Filament.

```text
services/admin-service/
├── app/
│   ├── Filament/
│   │   ├── Pages/                 # Login custom, dashboard, trang admin API
│   │   ├── Resources/             # User, Org Unit, Role theo API client
│   │   └── Widgets/               # KPI Identity
│   ├── Http/
│   │   └── Middleware/            # Identity session + permission guard
│   └── Services/
│       ├── GatewayClient.php
│       └── IdentityAdminClient.php
├── config/
├── resources/views/               # Blade tùy biến khi Filament không đủ
└── routes/web.php
```

Filament resource/page sẽ là custom page hoặc API-backed resource. Không dùng
Eloquent resource để query bảng nghiệp vụ ở service khác.

## Backlog thực hiện

| ID | Công việc | Trạng thái | Phụ thuộc | Tiêu chí hoàn thành |
|---|---|---|---|---|
| ADM-001 | Chốt contract admin Identity và error envelope | `DONE` | Gateway/Identity auth hiện có | `admin-identity-v1.yaml` có endpoint, request/response, pagination, error, permission và tenant scope. |
| ADM-002 | Scaffold `admin-service` Laravel 12 + Filament 5 | `DONE` | ADM-001 | `/admin` trả trang login local ở port 8086; config không chứa secret. |
| ADM-003 | Gateway route và service authentication cho admin API | `DONE` | ADM-001 | Gateway chỉ proxy; Identity vẫn là nơi authorize và Gateway fail-closed. |
| ADM-004 | Identity admin endpoints: dashboard + user | `DONE` | ADM-001, ADM-003 | Search/filter, status, unlock, force-password-change được authorize và audit; không lộ password/token. |
| ADM-005 | Identity admin endpoints: org unit + role/permission | `DONE` | ADM-001, ADM-003 | Org-unit create/update/manager, role assignment và permission matrix có self-escalation guard. |
| ADM-006 | Filament login và panel authorization | `DONE` | ADM-002, ADM-003 | Login dùng Identity session; token ở Laravel session; session invalid/revoked không vào được `/admin`. |
| ADM-007 | Dashboard và User UI | `DONE` | ADM-004, ADM-006 | Dashboard/User gọi API, có permission-aware action và confirmation cho thao tác nhạy cảm. |
| ADM-008 | Org Unit và Role UI | `DONE` | ADM-005, ADM-006 | Org-unit và manager, role/permission, gán/gỡ role hiển thị/chỉnh sửa theo permission. |
| ADM-009 | Audit, kiểm thử và local handoff | `DONE` | ADM-004..008 | Audit event, authorization tests, contract validation và smoke local `/admin` đã pass. |

## Contract cần hoàn thành trước ADM-002

Các endpoint public thông qua Gateway, versioned và cùng error envelope:

```text
GET    /api/v1/admin/dashboard/identity
GET    /api/v1/admin/users
GET    /api/v1/admin/users/{id}
PATCH  /api/v1/admin/users/{id}/status
POST   /api/v1/admin/users/{id}/unlock
POST   /api/v1/admin/users/{id}/force-password-change

GET    /api/v1/admin/org-units
POST   /api/v1/admin/org-units
PATCH  /api/v1/admin/org-units/{id}
PATCH  /api/v1/admin/org-units/{id}/manager

GET    /api/v1/admin/roles
GET    /api/v1/admin/permissions
PATCH  /api/v1/admin/roles/{id}/permissions
POST   /api/v1/admin/users/{id}/role-assignments
DELETE /api/v1/admin/users/{id}/role-assignments/{assignmentId}

GET    /api/v1/admin/audit-logs
```

Contract cần ghi rõ: required permission, tenant/organization scope, cursor
pagination, filter/sort/search allow-list, idempotency cho action ghi, response
không có credential, status code và error code. Khi endpoint thay đổi phải cập
nhật `admin-identity-v1.yaml`; `identity-v1.yaml` và
`gateway-identity-v1.yaml` phản ánh thêm permission trong Identity session.

## Thiết kế đăng nhập

1. Admin vào `/admin`; custom Filament login gửi credentials từ server tới
   Gateway/Identity, không gọi Identity trực tiếp từ browser.
2. Identity xác minh credentials, lock state và `must_change_password_at`, rồi
   cấp opaque session token hiện có.
3. `admin-service` lưu token mã hóa trong Laravel server session và dựng admin
   principal từ response `auth/session`.
4. Middleware xác minh session Identity định kỳ và trước action ghi; token bị
   revoke/expired sẽ xóa session Filament và chuyển về login.
5. Gateway và Identity kiểm tra quyền thật cho từng API; Filament chỉ dùng cùng
   tập permission để điều khiển navigation/action.

## Permission baseline

| Nhóm | Quyền phase đầu |
|---|---|
| `super_admin` | Tất cả thao tác trong Admin Portal; không được tự xóa audit log. |
| `hr_manager` | Xem/quản lý user và phòng ban trong organization được cấp; không được sửa permission hệ thống. |
| `team_leader` | Chỉ xem/manage membership trong scope được Identity cấp; không quản lý role global. |
| `qa_expert`, `member` | Không được vào Admin Portal trong phase đầu, trừ khi permission cụ thể được cấp sau này. |

Tên permission cụ thể do ADM-001 chốt, không suy diễn chỉ từ tên role.

## Kiểm thử và nghiệm thu

- Lint/Pint và test Laravel cho `admin-service`; test API/authorization cho
  Gateway và Identity ở phạm vi endpoint mới.
- Contract validation giữa `gateway-identity-v1.yaml` và `identity-v1.yaml`.
- Các tình huống tối thiểu: session expired/revoked, role không có quyền, actor
  tự tăng quyền, truy cập organization khác, user locked, Gateway/Identity
  unavailable và request có correlation ID.
- Smoke local: login, dashboard, tìm user, lock/unlock, tạo/sửa org unit, gán
  role hợp lệ và tra audit log tại `http://127.0.0.1:8086/admin`.

## Phase tiếp theo, chưa mở trong backlog này

| Nhóm panel | Service sở hữu | Điều kiện bắt đầu |
|---|---|---|
| Post/report moderation | Social | Social admin API, moderation policy và audit event đã chốt. |
| Q&A, document, course moderation | Learning | Learning admin API và content lifecycle đã chốt. |
| Broadcast/template | Notification | Notification admin API, audience scope và approval policy đã chốt. |
| Knowledge curation | AI Knowledge | Curation API, reviewer policy và event contract đã chốt. |
| Audit tổng hợp | Read model từ events | Outbox/consumer idempotent và retention/access policy đã chốt. |

## Nhật ký thực hiện

| Ngày | ID | Người làm | Kết quả / bằng chứng | Bước tiếp theo |
|---|---|---|---|---|
| 2026-09-27 | ADM-001..009 | Chủ dự án + AI | Hoàn tất contract, Admin service Laravel 12 + Filament 5, Gateway proxy, Identity admin API/UI, audit và test authorization; smoke local `/admin` pass. | Nối moderation Social rồi Learning theo contract cùng ranh giới. |
