<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardEngine
{
    /** Trusted producer supplies the verified content owner and interaction actor. Caller owns transaction. */
    public function award(array $event): void
    {
        if ($event['actor_user_id'] === $event['recipient_user_id']) return;
        $rules = DB::table('reward_rules')->where('organization_id', $event['organization_id'])
            ->where('trigger_event_type', $event['event_type'])->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>', now()))->orderBy('id')->get();
        if ($rules->isEmpty()) return;
        foreach ($rules as $rule) {
            $eligibility = json_decode($rule->eligibility_json ?? '{}', true);
            if (!empty($eligibility['topic_id']) && ($event['topic_id'] ?? null) !== $eligibility['topic_id']) continue;
            if ($rule->points_delta <= 0 && $rule->coins_delta <= 0) continue;
            // Serialize each rule so the daily cap and accrual are atomic across content.
            DB::table('reward_rules')->where('id', $rule->id)->lockForUpdate()->first();
            $existingQuery = DB::table('reward_awards')->where('organization_id', $event['organization_id'])->where('rule_id', $rule->id)
                ->where('actor_user_id', $event['actor_user_id'])->where('source_type', $event['source_type'])->where('source_id', $event['source_id']);
            $existing = (clone $existingQuery)->lockForUpdate()->first();
            if ($existing && $existing->status !== 'canceled') continue;
            $awards = DB::table('reward_awards')->where('organization_id', $event['organization_id'])->where('rule_id', $rule->id)
                ->where('recipient_user_id', $event['recipient_user_id'])->whereIn('status', ['pending', 'approved']);
            if ($rule->daily_limit !== null && (clone $awards)->where('created_at', '>=', now('Asia/Ho_Chi_Minh')->startOfDay()->utc())->lockForUpdate()->get()->count() >= $rule->daily_limit) continue;
            $id = $existing?->id ?? (string) Str::uuid();
            $values = ['organization_id' => $event['organization_id'], 'rule_id' => $rule->id,
                'recipient_user_id' => $event['recipient_user_id'], 'actor_user_id' => $event['actor_user_id'], 'source_id' => $event['source_id'],
                'source_type' => $event['source_type'], 'event_id' => $event['event_id'], 'points_amount' => $rule->points_delta, 'coins_amount' => $rule->coins_delta,
                'status' => 'pending', 'settlement_id' => null, 'canceled_at' => null, 'cancellation_reason' => null, 'created_at' => now()];
            if ($existing) $existingQuery->update($values);
            else DB::table('reward_awards')->insert(['id' => $id, ...$values]);
            app(RewardEligibility::class)->audit((object) ['id' => $id, ...$values], 'pending', $existing ? 'vote_reactivated' : 'interaction_created');

        }
    }
}
