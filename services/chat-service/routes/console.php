<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('chat:ensure-indexes', function () {
    $db = \Illuminate\Support\Facades\DB::connection('mongodb')->getMongoDB();
    $db->selectCollection('conversations')->createIndex(['organizationId' => 1, 'directKey' => 1], ['unique' => true, 'partialFilterExpression' => ['type' => 'direct'], 'name' => 'direct_conversation_unique']);
    $db->selectCollection('conversation_members')->createIndex(['conversationId' => 1, 'userId' => 1], ['unique' => true]);
    $db->selectCollection('messages')->createIndex(['conversationId' => 1, 'clientMessageId' => 1], ['unique' => true]);
    $db->selectCollection('messages')->createIndex(['conversationId' => 1, 'sentAt' => -1, '_id' => -1]);
    $db->selectCollection('chat_attachments')->createIndex(['conversationId' => 1, 'messageId' => 1]);
    $this->info('Chat indexes are ready.');
})->purpose('Create required unique and history indexes without resetting chat data');

Artisan::command('outbox:publish {--limit=100}', function () {
    $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
    if ($limit === false) { $this->error('Invalid limit.'); return 1; }
    try { $count = app(\App\Services\OutboxPublisher::class)->publish($limit); }
    catch (\Throwable) { $this->error('Publish failed; pending events were retained for retry.'); return 1; }
    $this->info('Published events: '.$count); return 0;
})->purpose('Publish durable outbox events with broker confirms; consumers deduplicate event_id');
