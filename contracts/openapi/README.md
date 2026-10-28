# OpenAPI Contracts

Đặt đặc tả REST API công khai và API nội bộ giữa các service tại đây. Không đặt business code.

- [Admin Identity API](admin-identity-v1.yaml) là contract nguồn cho Admin Portal
  phase đầu. Request đi qua Gateway tại `:8080`; Gateway proxy cùng path tới
  Identity tại `:8081` với private gateway credential.

- [Social public API](gateway-social-v1.yaml): contract dùng chung cho Web,
  Android và client khác qua `/api/v1/social/*`; Bearer Identity token.
- [Social internal API](social-v1.yaml): cùng payload, riêng private gateway key
  và identity context; không dùng từ client. [Hướng dẫn và ví dụ](../../docs/specs/phase-1-social/02-api-client-guide.md).

## API additions — 2026-10-21

`platform-additions-v1.json` mô tả 77 operation mới tại Gateway cho Identity, Social, Tri thức/rewards, Chat, Notification và AI base. Các contract Identity/Social/Learning hiện có vẫn giữ các operation trước đó. [Inventory thực tế](../../docs/api/ROUTE_INVENTORY.md) và [coverage](../../docs/api/API_COVERAGE_2026-10-21.md) phân biệt implementation, kiểm thử và tích hợp còn thiếu. Kiểm tra tất cả bằng `npm run check:contracts` trong Gateway.
