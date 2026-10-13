# Chat Service

Laravel 12 API boundary with `mongodb/laravel-mongodb`. Chat persistence is MongoDB `chat_db`, not MySQL: conversations, members, messages, Identity display read models and event reliability collections are documents.

MongoDB collections use `database/mongodb/ensure_indexes.js` and `database/mongodb/seed.js` instead of relational migrations. The PHP MongoDB extension (`ext-mongodb`) must be enabled before using models or seeders.

Run `php artisan serve --port=8084`. Health: `GET /api/v1/health`.
