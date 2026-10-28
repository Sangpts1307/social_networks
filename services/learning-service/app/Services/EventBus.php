<?php
namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class EventBus
{
    public function connect(): AMQPStreamConnection
    {
        if (!config('event_bus.user') || !config('event_bus.password')) throw new \RuntimeException('RabbitMQ credentials are not configured.');
        return new AMQPStreamConnection(config('event_bus.host'), config('event_bus.port'), config('event_bus.user'), config('event_bus.password'), config('event_bus.vhost'), connection_timeout: 5, read_write_timeout: 10);
    }

    public function topology($channel): void
    {
        $exchange = config('event_bus.exchange'); $queue = config('event_bus.notification_queue');
        $channel->exchange_declare($exchange, 'topic', false, true, false);
        $channel->exchange_declare($exchange.'.dead', 'fanout', false, true, false);
        $channel->queue_declare($queue.'.dead', false, true, false, false);
        $channel->queue_bind($queue.'.dead', $exchange.'.dead');
        $channel->queue_declare($queue, false, true, false, false, false, new \PhpAmqpLib\Wire\AMQPTable(['x-dead-letter-exchange' => $exchange.'.dead']));
        $channel->queue_bind($queue, $exchange, '#');
    }
}
