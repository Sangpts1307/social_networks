<?php
namespace App\Http\Controllers\Api;

use App\Services\LearningAuthorization;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardSettlementController
{
    public function index(Request $request, LearningAuthorization $auth)
    {
        $actor = $auth->requirePermission($request, 'learning.rewards.manage');
        $data = $request->validate(['cursor' => 'nullable|uuid']);
        $q = DB::table('reward_settlements')->where('organization_id', $actor['organization_id']);
        if (!empty($data['cursor'])) $q->where('id', '>', $data['cursor']);
        $rows = $q->orderBy('id')->limit(21)->get(); $more = $rows->count() > 20; $rows = $rows->take(20)->values();
        return response()->json(['data' => $rows, 'meta' => ['next_cursor' => $more ? $rows->last()->id : null]]);
    }

    public function store(Request $request, LearningAuthorization $auth)
    {
        $actor = $auth->requirePermission($request, 'learning.rewards.manage');
        $data = $request->validate(['period_type' => 'required|in:week,month', 'period_start' => 'required|date_format:Y-m-d']);
        $local = CarbonImmutable::parse($data['period_start'], 'Asia/Ho_Chi_Minh')->startOfDay();
        $start = $data['period_type'] === 'month' ? $local->startOfMonth() : $local->startOfWeek(CarbonImmutable::MONDAY);
        abort_unless($start->equalTo($local), 422, 'Period must start on Monday or the first day of the month.');
        $end = $data['period_type'] === 'month' ? $start->addMonth() : $start->addWeek();
        abort_if($end->isFuture(), 422, 'Only closed periods can be settled.');
        return DB::transaction(function () use ($actor, $data, $start, $end) {
            // One lock shared by every settlement in an organization, including overlapping week/month runs.
            DB::table('reward_rules')->where('organization_id', $actor['organization_id'])->orderBy('id')->lockForUpdate()->get();
            $awards = DB::table('reward_awards')->where('organization_id', $actor['organization_id'])->where('status', 'pending')->whereNull('settlement_id')
                ->where('created_at', '>=', $start->utc())->where('created_at', '<', $end->utc());
            $rows = (clone $awards)->orderBy('id')->lockForUpdate()->get(); abort_if($rows->isEmpty(), 409, 'No unassigned accruals for this period.');
            $id = (string) Str::uuid();
            DB::table('reward_settlements')->insert(['id' => $id, 'organization_id' => $actor['organization_id'], 'period_type' => $data['period_type'],
                'period_start' => $start->utc(), 'period_end' => $end->utc(), 'status' => 'draft', 'created_by_user_id' => $actor['user_id'], 'created_at' => now(), 'updated_at' => now()]);
            $awards->update(['settlement_id' => $id]);
            return response()->json(['data' => ['id' => $id, 'status' => 'draft', 'award_count' => $rows->count(),
                'points_total' => $rows->sum('points_amount'), 'coins_total' => $rows->sum('coins_amount')]], 201);
        }, 3);
    }

    public function show(Request $request, LearningAuthorization $auth, string $settlementId)
    {
        $actor = $auth->requirePermission($request, 'learning.rewards.manage');
        $run = DB::table('reward_settlements')->where('organization_id', $actor['organization_id'])->where('id', $settlementId)->first(); abort_unless($run, 404);
        return response()->json(['data' => $run, 'recipients' => DB::table('reward_awards')->where('settlement_id', $settlementId)->whereIn('status', ['pending', 'approved'])
            ->select('recipient_user_id')->selectRaw('COUNT(*) AS award_count, SUM(points_amount) AS points, SUM(coins_amount) AS coins')->groupBy('recipient_user_id')->get()]);
    }

    public function approve(Request $request, LearningAuthorization $auth, string $settlementId)
    {
        $actor = $auth->requirePermission($request, 'learning.rewards.manage');
        DB::transaction(function () use ($actor, $settlementId) {
            $run = DB::table('reward_settlements')->where('organization_id', $actor['organization_id'])->where('id', $settlementId)->lockForUpdate()->first(); abort_unless($run, 404);
            if ($run->status === 'approved') return;
            abort_unless($run->status === 'draft', 409);
            $candidates = DB::table('reward_awards')->where('settlement_id', $settlementId)->where('status', 'pending')->get();
            $eligibility = app(\App\Services\RewardEligibility::class);
            $eligibility->lockSources($candidates);
            $rows = DB::table('reward_awards')->where('settlement_id', $settlementId)->where('status', 'pending')->orderBy('recipient_user_id')->orderBy('id')->lockForUpdate()->get();
            foreach ($rows as $award) {
                if (!$eligibility->valid($award)) { $eligibility->cancel($award, 'ineligible_at_approval'); continue; }
                $eligibility->audit($award, 'approved', 'settlement_approved');
                DB::table('reward_wallets')->insertOrIgnore(['id' => (string) Str::uuid(), 'organization_id' => $actor['organization_id'],
                    'user_id' => $award->recipient_user_id, 'points_balance' => 0, 'coins_balance' => 0, 'last_ledger_sequence' => 0, 'updated_at' => now()]);
                $wallet = DB::table('reward_wallets')->where('organization_id', $actor['organization_id'])->where('user_id', $award->recipient_user_id);
                $wallet->lockForUpdate()->first();
                foreach (['points' => $award->points_amount, 'coins' => $award->coins_amount] as $currency => $amount) {
                    if ($amount <= 0) continue;
                    $wallet->increment($currency.'_balance', $amount, ['updated_at' => now()]);
                    $wallet->increment('last_ledger_sequence');
                    DB::table('reward_ledger_entries')->insert(['id' => (string) Str::uuid(), 'organization_id' => $actor['organization_id'], 'user_id' => $award->recipient_user_id,
                        'currency_type' => $currency, 'amount_delta' => $amount, 'balance_after' => $wallet->value($currency.'_balance'), 'entry_type' => 'earn',
                        'reason_code' => 'period_settlement', 'source_service' => 'learning-service', 'source_event_id' => $award->id,
                        'source_type' => 'reward_award', 'source_id' => $award->id, 'rule_code' => $award->rule_id, 'occurred_at' => now(), 'created_at' => now(),
                        'metadata_json' => json_encode(['settlement_id' => $settlementId, 'approved_by_user_id' => $actor['user_id']])]);
                }
            }
            DB::table('reward_awards')->where('settlement_id', $settlementId)->where('status', 'pending')->update(['status' => 'approved', 'approved_at' => now()]);
            DB::table('reward_settlements')->where('id', $settlementId)->update(['status' => 'approved', 'approved_by_user_id' => $actor['user_id'], 'approved_at' => now(), 'updated_at' => now()]);
        }, 3);
        return response()->noContent();
    }

    public function cancel(Request $request, LearningAuthorization $auth, string $settlementId)
    {
        $actor = $auth->requirePermission($request, 'learning.rewards.manage');
        DB::transaction(function () use ($actor, $settlementId) {
            $run = DB::table('reward_settlements')->where('organization_id', $actor['organization_id'])->where('id', $settlementId)->lockForUpdate()->first(); abort_unless($run, 404);
            abort_unless($run->status === 'draft', 409, 'Only drafts can be canceled.');
            DB::table('reward_awards')->where('settlement_id', $settlementId)->where('status', 'pending')->update(['settlement_id' => null]);
            DB::table('reward_settlements')->where('id', $settlementId)->update(['status' => 'canceled', 'updated_at' => now()]);
        }, 3);
        return response()->noContent();
    }
}
