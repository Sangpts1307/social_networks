# OpenAPI Contracts

Đặt đặc tả REST API công khai và API nội bộ giữa các service tại đây. Không đặt business code.

- [Admin Identity API](admin-identity-v1.yaml) là contract nguồn cho Admin Portal
  phase đầu. Request đi qua Gateway tại `:8080`; Gateway proxy cùng path tới
  Identity tại `:8081` với private gateway credential.

- [Social public API](gateway-social-v1.yaml): contract dùng chung cho Web,
  Android và client khác qua `/api/v1/social/*`; Bearer Identity token.
- [Social internal API](social-v1.yaml): cùng payload, riêng private gateway key
  và identity context; không dùng từ client. [Hướng dẫn và ví dụ](../../docs/specs/phase-1-social/02-api-client-guide.md).
