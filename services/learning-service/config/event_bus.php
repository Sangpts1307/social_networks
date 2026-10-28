<?php
return ['signing_key' => env('LEARNING_NOTIFICATION_KEY'), 'host' => env('RABBITMQ_HOST', '127.0.0.1'), 'port' => (int) env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER'), 'password' => env('RABBITMQ_PASSWORD'), 'vhost' => env('RABBITMQ_VHOST', '/'),
    'exchange' => env('RABBITMQ_EXCHANGE', 'platform.events'), 'notification_queue' => env('RABBITMQ_NOTIFICATION_QUEUE', 'notification.inbox')];
