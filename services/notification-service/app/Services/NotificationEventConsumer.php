<?php
namespace App\Services;

use Illuminate\Support\Facades\Validator;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class NotificationEventConsumer
{
    public function __construct(private EventBus $bus, private NotificationIngestor $ingestor) {}

    public function process(array $event): void
    {
        $map = [
            'social.comment.created.v1' => ['social', 'comment', 'Bạn có bình luận mới'],
            'learning.answer.created.v1' => ['learning', 'answer', 'Câu hỏi có câu trả lời mới'],
            'learning.qa.comment.created.v1' => ['learning', 'comment', 'Nội dung tri thức có bình luận mới'],
            'learning.document.reviewed.v1' => ['learning', 'document_approved', 'Tài liệu đã được duyệt'],
            'social.announcement.published.v1' => ['social', 'announcement', 'Kênh có thông báo mới'],
            'chat.message.created.v1' => ['chat', 'chat_message', 'Bạn có tin nhắn mới'],
        ];
        if (($event['event_type'] ?? '') === 'learning.document.reviewed.v1' && ($event['payload']['action'] ?? '') !== 'approve') return;
        $definition = $map[$event['event_type'] ?? ''] ?? null;
        if (!$definition) return;
        [$producer, $type, $title] = $definition;
        if (($event['source_service'] ?? '') !== $producer) throw new \InvalidArgumentException('Event producer mismatch.');
        $payload = $event['payload'] ?? [];
        $recipients = array_values(array_diff(array_unique($payload['recipient_user_ids'] ?? []), [$payload['actor_user_id'] ?? null]));
        if ($recipients === []) return;
        $data = Validator::make(['event_id' => $event['event_id'] ?? null, 'organization_id' => $payload['organization_id'] ?? null,
            'notification_type' => $type, 'recipient_user_ids' => $recipients, 'title' => $title, 'body' => $title,
            'source_type' => $payload['source_type'] ?? ($producer === 'chat' ? 'message' : null),
            'source_id' => $payload['source_id'] ?? $payload['message_id'] ?? null], [
                'event_id' => 'required|uuid', 'organization_id' => 'required|uuid', 'notification_type' => 'required|string',
                'recipient_user_ids' => 'required|array|max:100', 'recipient_user_ids.*' => 'required|uuid|distinct',
                'title' => 'required|string', 'body' => 'required|string', 'source_type' => 'nullable|string|max:64', 'source_id' => 'nullable|uuid',
            ])->validate();
        $this->ingestor->ingest($producer, $data);
    }

    public function consume(int $limit = 100): int
    {
        $connection = $this->bus->connect(); $channel = $connection->channel(); $count = 0;
        try {
            $this->bus->topology($channel); $channel->basic_qos(null, 1, null);
            $channel->basic_consume(config('event_bus.notification_queue'), '', false, false, false, false, function ($message) use (&$count) {
                try {
                    $event = json_decode($message->body, true, flags: JSON_THROW_ON_ERROR);
                    if (!is_array($event) || !is_string($event['source_service'] ?? null)) throw new \InvalidArgumentException('Invalid event envelope.');
                    $key = (string) config('services.event_keys.'.($event['source_service'] ?? ''));
                    $properties = $message->get_properties();
                    $headers = isset($properties['application_headers']) ? $properties['application_headers']->getNativeData() : [];
                    if (strlen($key) < 32 || !hash_equals(hash_hmac('sha256', $message->body, $key), (string) ($headers['signature'] ?? ''))) throw new \InvalidArgumentException('Invalid event signature.');
                    $this->process($event); $message->ack(); $count++;
                } catch (\Illuminate\Validation\ValidationException|\InvalidArgumentException|\JsonException) { $message->reject(false); }
                catch (HttpExceptionInterface $e) {
                    $message->reject($e->getStatusCode() >= 500);
                    if ($e->getStatusCode() >= 500) throw new \RuntimeException('Dependency unavailable.');
                } catch (\Throwable $e) { $message->nack(false, true); throw $e; }
            });
            while ($channel->is_consuming() && $count < $limit) {
                try { $channel->wait(null, false, 5); } catch (AMQPTimeoutException) { break; }
            }
            return $count;
        } finally { $channel->close(); $connection->close(); }
    }
}
