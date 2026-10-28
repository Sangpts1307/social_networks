<?php
namespace App\Http\Controllers\Api;

use App\Services\LearningAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardController
{
    public function wallet(Request $request)
    {
        $actor = $request->attributes->get('learning_actor');
        $wallet = DB::table('reward_wallets')->where('organization_id', $actor['organization_id'])->where('user_id', $actor['user_id'])->first(['points_balance', 'coins_balance']);
        return response()->json(['data' => $wallet ?? ['points_balance' => 0, 'coins_balance' => 0],
            'pending' => DB::table('reward_awards')->where('organization_id', $actor['organization_id'])->where('recipient_user_id', $actor['user_id'])->where('status', 'pending')
                ->selectRaw('COALESCE(SUM(points_amount), 0) AS points, COALESCE(SUM(coins_amount), 0) AS coins')->first()]);
    }

    public function ledger(Request $request)
    {
        $data = $request->validate(['cursor' => 'nullable|uuid']);
        $actor = $request->attributes->get('learning_actor');
        $q = DB::table('reward_ledger_entries')->where('organization_id', $actor['organization_id'])->where('user_id', $actor['user_id']);
        if (!empty($data['cursor'])) {
            $cursor = (clone $q)->where('id', $data['cursor'])->first();
            abort_unless($cursor, 422, 'Invalid cursor.');
            $q->where(fn ($q) => $q->where('created_at', '<', $cursor->created_at)->orWhere(fn ($q) => $q->where('created_at', $cursor->created_at)->where('id', '<', $cursor->id)));
        }
        $rows = $q->orderByDesc('created_at')->orderByDesc('id')->limit(21)->get();
        $more = $rows->count() > 20; $rows = $rows->take(20)->values();
        return response()->json(['data' => $rows, 'meta' => ['next_cursor' => $more ? $rows->last()->id : null]]);
    }

    public function accruals(Request $request)
    {
        $actor = $request->attributes->get('learning_actor');
        $data = $request->validate(['cursor' => 'nullable|uuid', 'status' => 'nullable|in:pending,approved,canceled']);
        $q = DB::table('reward_awards')->where('organization_id', $actor['organization_id'])->where('recipient_user_id', $actor['user_id']);
        if (!empty($data['status'])) $q->where('status', $data['status']);
        if (!empty($data['cursor'])) $q->where('id', '>', $data['cursor']);
        $rows = $q->orderBy('id')->limit(21)->get(); $more = $rows->count() > 20; $rows = $rows->take(20)->values();
        return response()->json(['data' => $rows, 'meta' => ['next_cursor' => $more ? $rows->last()->id : null]]);
    }

    public function leaderboard(Request $request)
    {
        $actor = $request->attributes->get('learning_actor');
        $data = $request->validate(['page' => 'nullable|integer|min:1|max:10000']);
        $page = $data['page'] ?? 1;
        $rows = DB::table('reward_wallets')->where('organization_id', $actor['organization_id'])->where('points_balance', '>', 0)
            ->orderByDesc('points_balance')->orderBy('user_id')->offset(($page - 1) * 20)->limit(21)->get(['user_id', 'points_balance']);
        $more = $rows->count() > 20;
        return response()->json(['data' => $rows->take(20)->values(), 'meta' => ['next_page' => $more ? $page + 1 : null, 'basis' => 'approved_lifetime_points']]);
    }

    public function rules(Request $request, LearningAuthorization $auth)
    {
        $actor = $auth->requirePermission($request, 'learning.rewards.manage');
        return response()->json(['data' => DB::table('reward_rules')->where('organization_id', $actor['organization_id'])->orderBy('code')->get()]);
    }

    public function saveRule(Request $request, LearningAuthorization $auth, ?string $ruleId = null)
    {
        $actor = $auth->requirePermission($request, 'learning.rewards.manage');
        $data = $request->validate(['code' => 'required|regex:/^[a-z][a-z0-9_.-]{0,79}$/',
            'trigger_event_type' => 'required|in:learning.answer.upvoted,learning.question.upvoted,learning.document.upvoted',
            'points_delta' => 'required|integer|min:0|max:10000', 'coins_delta' => 'required|integer|min:0|max:10000', 'daily_limit' => 'nullable|integer|min:1|max:10000',
            'status' => 'required|in:draft,active,disabled', 'topic_id' => 'nullable|uuid']);
        if (!empty($data['topic_id'])) abort_unless(DB::table('knowledge_topics')->where('id', $data['topic_id'])->where('organization_id', $actor['organization_id'])->whereNull('deleted_at')->exists(), 422);
        $id = $ruleId ?? (string) Str::uuid();
        DB::transaction(function () use ($actor, $data, $id, $ruleId) {
            $query = DB::table('reward_rules')->where('organization_id', $actor['organization_id'])->where('id', $id);
            if ($ruleId) abort_unless((clone $query)->lockForUpdate()->first(), 404);
            abort_if(DB::table('reward_rules')->where('organization_id', $actor['organization_id'])->where('code', $data['code'])->where('id', '!=', $id)->exists(), 409, 'Rule code already exists.');
            // Rule code and trigger are immutable so one interaction cannot be awarded twice by renaming it.
            if ($ruleId) { $old = (clone $query)->first(); abort_unless($old->code === $data['code'] && $old->trigger_event_type === $data['trigger_event_type'], 409, 'Rule identity is immutable.'); }
            $values = ['code' => $data['code'], 'trigger_event_type' => $data['trigger_event_type'], 'points_delta' => $data['points_delta'], 'coins_delta' => $data['coins_delta'],
                'daily_limit' => $data['daily_limit'] ?? null, 'status' => $data['status'], 'eligibility_json' => json_encode(['topic_id' => $data['topic_id'] ?? null]),
                'approved_by_user_id' => $actor['user_id'], 'updated_at' => now()];
            if ($ruleId) $query->update($values);
            else DB::table('reward_rules')->insert(['id' => $id, 'organization_id' => $actor['organization_id'], 'created_at' => now(), ...$values]);
        });
        return response()->json(['data' => DB::table('reward_rules')->where('id', $id)->first()], $ruleId ? 200 : 201);
    }
}
