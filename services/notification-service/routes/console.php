<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:deliver {--limit=100}', function () {
    $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
    if ($limit === false) { $this->error('Limit must be between 1 and 1000.'); return 1; }
    try { $count = app(\App\Services\PushDelivery::class)->deliver($limit); }
    catch (\RuntimeException) { $this->error('FCM is not configured. No push was sent.'); return 1; }
    $this->info('Push deliveries accepted by FCM: '.$count);
    return 0;
})->purpose('Deliver pending FCM push notifications with bounded retries');

Artisan::command('notifications:consume {--limit=100}', function () {
    $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
    if ($limit === false) { $this->error('Invalid limit.'); return 1; }
    try { $count = app(\App\Services\NotificationEventConsumer::class)->consume($limit); }
    catch (\Throwable) { $this->error('Consumer unavailable; unacknowledged events remain queued.'); return 1; }
    $this->info('Processed notification events: '.$count); return 0;
})->purpose('Consume RabbitMQ domain events with manual ack, deduplication and dead-letter handling');
