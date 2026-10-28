<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use PhpAmqpLib\Message\AMQPMessage;

class OutboxPublisher
{
    public function __construct(private EventBus $bus) {}
    public function publish(int $limit = 100): int
    {
        $signingKey = (string) config('event_bus.signing_key');
        if (strlen($signingKey) < 32) throw new \RuntimeException('Event signing key is not configured.');
        $connection = $this->bus->connect(); $channel = $connection->channel();
        try {
            $this->bus->topology($channel); $channel->confirm_select();
            $channel->set_nack_handler(fn () => throw new \RuntimeException('Broker rejected event.'));
            $channel->set_return_listener(fn () => throw new \RuntimeException('Event was not routed.'));
            $count = 0;
            foreach (DB::table('outbox_events')->whereNull('published_at')->orderBy('occurred_at')->limit($limit)->get() as $row) {
                $id = $row->id; $type = $row->event_type; $payload = json_decode($row->payload_json, true, flags: JSON_THROW_ON_ERROR); $occurred = $row->occurred_at;
                $update = fn (array $values) => DB::table('outbox_events')->where('id', $id)->update($values);
                $type = preg_match('/\.v[0-9]+$/', $type) ? $type : $type.'.v1';
                $date = $occurred instanceof \MongoDB\BSON\UTCDateTime ? $occurred->toDateTime()->format(DATE_ATOM) : \Carbon\Carbon::parse($occurred)->toISOString();
                $event = ['event_id' => $id, 'event_type' => $type, 'event_version' => 1, 'occurred_at' => $date, 'source_service' => 'learning', 'payload' => $payload];
                $body = json_encode($event, JSON_THROW_ON_ERROR);
                $message = new AMQPMessage($body, ['application_headers' => new \PhpAmqpLib\Wire\AMQPTable(['signature' => hash_hmac('sha256', $body, $signingKey)]), 'content_type' => 'application/json', 'delivery_mode' => 2, 'message_id' => $id]);
                $channel->basic_publish($message, config('event_bus.exchange'), $type, true);
                $channel->wait_for_pending_acks_returns(5);
                $update(['published_at' => now(), 'updated_at' => now()]); $count++;
            }
            return $count;
        } finally { $channel->close(); $connection->close(); }
    }
}
