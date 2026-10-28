<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationIngestor
{
    public function __construct(private IdentityDirectory $identity, private NotificationFanout $fanout) {}
    public function ingest(string $producer, array $data): void
    {
        $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        $prior = DB::table('processed_events')->where('producer', $producer)->where('event_id', $data['event_id'])->first();
        if ($prior) { abort_unless(hash_equals($prior->result_hash, $hash), 409, 'Event payload changed.'); return; }
        $recipients = $data['recipient_user_ids'];
        try { $this->identity->requireUsers($data['organization_id'], $recipients); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $error) {
            if ($error->getStatusCode() !== 422) throw $error;
            $recipients = $this->identity->activeUsers($data['organization_id'], $recipients);
        }
        $fanout = $this->fanout;
        DB::transaction(function () use ($data, $producer, $hash, $fanout, $recipients) {
            $inserted = DB::table('processed_events')->insertOrIgnore(['id' => (string) Str::uuid(), 'producer' => $producer, 'event_id' => $data['event_id'],
                'event_type' => $data['notification_type'], 'result_hash' => $hash, 'processed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            if (!$inserted) {
                $prior = DB::table('processed_events')->where('producer', $producer)->where('event_id', $data['event_id'])->first();
                abort_unless(hash_equals($prior->result_hash, $hash), 409, 'Event payload changed.'); return;
            }
            foreach ($recipients as $userId) $fanout->create([...$data, 'producer' => $producer], $userId);
        }, 3);
    }
}
