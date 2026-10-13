# Social Service

Laravel 12 service owning Inside channels, posts, attachments, comments, reactions, saves, tags, moderation and social outbox/inbox data in `social_db`.

All Identity values (`organization_id`, `author_user_id`, `org_unit_id`) are UUID references only; this service never creates a foreign key to `identity_db`.

Run `php artisan migrate --seed` after configuring MySQL, then `php artisan serve --port=8082`. Health: `GET /api/v1/health`.

## Social API từ 13/10/2026

Backend JSON dùng chung cho Web/Android qua `/api/v1/social/*` tại Gateway. Có kênh/self-membership, text post/feed, comments/reply, reaction, private save, post report/moderation và transactional outbox.

Xem [hướng dẫn API/client và chạy local](../../docs/specs/phase-1-social/02-api-client-guide.md), [OpenAPI công khai](../../contracts/openapi/gateway-social-v1.yaml) và [contract nội bộ](../../contracts/openapi/social-v1.yaml).

`SOCIAL_GATEWAY_KEY` phải cấu hình giống Gateway, tối thiểu 32 ký tự. Chạy `php artisan migrate` trên DB Social đã kiểm tra; không reset dữ liệu hiện có. Kiểm thử nhanh: `php artisan test`. Kiểm thử MySQL + Gateway/Identity thật từ root source: `python3 scripts/test-social-local.py` (database riêng và cleanup).
