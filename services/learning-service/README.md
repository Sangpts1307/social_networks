# Learning Service

Laravel 12 service owning Base Square taxonomy, Q&A, documents/versioning/access rules, courses/progress, reward ledger, moderation and outbox/inbox data in `learning_db`.

Identity accounts and organization data are external UUID references. The reward ledger is immutable by design; a correction is a compensating entry, never an in-place rewrite.

Run `php artisan migrate --seed` after configuring MySQL, then `php artisan serve --host=127.0.0.1 --port=8083`. Health: `GET /api/v1/health`.

## Knowledge Foundation hiện có

- `GET /api/v1/knowledge/topics` trả taxonomy theo organization.
- `GET /api/v1/knowledge-items` và `/{id}` chỉ trả metadata document `published`, `internal`, chưa archive.
- Hai route này là API nội bộ: chỉ Gateway được gọi, qua `LEARNING_GATEWAY_KEY` và identity context đã xác minh. Không gọi trực tiếp từ Web.
- Gateway public routes là `/api/v1/learning/...`; Web mở **Base Square** sau khi đăng nhập.

Đặt cùng một `LEARNING_GATEWAY_KEY` tối thiểu 32 ký tự trong `.env` của Gateway và Learning. Không đưa key này vào Web. Khởi động lại Gateway/Learning sau khi đổi `.env`.

## Kiểm tra

```bash
php artisan test
cd ../..
python3 scripts/test-learning-local.py
```

Runner tạo `learning_foundation_test` và một account MySQL tạm, chạy migrate/seed lặp cùng Identity/Gateway/Vue, rồi tự dọn. Nó không reset `learning_db`.
