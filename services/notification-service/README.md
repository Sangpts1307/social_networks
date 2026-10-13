# Notification Service

Laravel 12 service owning in-app notifications, templates, preferences, encrypted device registration metadata, delivery attempts, broadcasts and outbox/inbox data in `notification_db`.

Notification source IDs are references to owning services and are never cross-database foreign keys. Opening a source always requires a live authorization check by its owner.

Run `php artisan migrate --seed` after configuring MySQL, then `php artisan serve --port=8085`. Health: `GET /api/v1/health`.
