<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('outbox:publish {--limit=100}', function () {
    $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
    if ($limit === false) { $this->error('Invalid limit.'); return 1; }
    try { $count = app(\App\Services\OutboxPublisher::class)->publish($limit); }
    catch (\Throwable) { $this->error('Publish failed; pending events were retained for retry.'); return 1; }
    $this->info('Published events: '.$count); return 0;
})->purpose('Publish durable outbox events with broker confirms; consumers deduplicate event_id');
