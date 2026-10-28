<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RewardEligibility
{
    public function valid(object $award): bool
    {
        $org = $award->organization_id; $id = $award->source_id;
        if ($award->source_type === 'question') {
            $content = $this->questions($org)->where('id', $id)->where('author_user_id', $award->recipient_user_id)->lockForUpdate()->first() !== null;
            $vote = DB::table('qa_votes')->where('target_type', 'question')->where('target_id', $id);
        } elseif ($award->source_type === 'answer') {
            $answer = DB::table('answers')->where('organization_id', $org)->where('id', $id)->where('author_user_id', $award->recipient_user_id)
                ->whereNull('deleted_at')->where('answer_status', 'published')->lockForUpdate()->first();
            $content = $answer && $this->questions($org)->where('id', $answer->question_id)->lockForUpdate()->first() !== null;
            $vote = DB::table('qa_votes')->where('target_type', 'answer')->where('target_id', $id);
        } elseif ($award->source_type === 'document') {
            $content = DB::table('documents')->where('organization_id', $org)->where('id', $id)->where('owner_user_id', $award->recipient_user_id)
                ->where('document_status', 'published')->where('confidentiality', 'internal')->whereNull('deleted_at')->whereNull('archived_at')
                ->whereNotNull('published_at')->where('published_at', '<=', now())->lockForUpdate()->first() !== null;
            $vote = DB::table('document_votes')->where('document_id', $id);
        } else return false;
        return $content && $vote->where('user_id', $award->actor_user_id)->where('vote_value', 1)->lockForUpdate()->first() !== null;
    }

    private function questions(string $org)
    {
        return DB::table('questions')->where('organization_id', $org)->where('visibility', 'organization')->whereIn('question_status', ['open', 'closed'])->whereNull('deleted_at');
    }

    /** Lock source rows before awards, matching the vote/edit/delete transaction order. */
    public function lockSources($awards): void
    {
        $questionIds = $awards->where('source_type', 'question')->pluck('source_id')->all();
        $answerIds = $awards->where('source_type', 'answer')->pluck('source_id')->all();
        $questionIds = array_values(array_unique([...$questionIds, ...DB::table('answers')->whereIn('id', $answerIds)->pluck('question_id')->all()]));
        DB::table('questions')->whereIn('id', $questionIds)->orderBy('id')->lockForUpdate()->get();
        DB::table('answers')->whereIn('id', $answerIds)->orderBy('id')->lockForUpdate()->get();
        DB::table('documents')->whereIn('id', $awards->where('source_type', 'document')->pluck('source_id')->all())->orderBy('id')->lockForUpdate()->get();
    }

    public function cancel(object $award, string $reason): void
    {
        $changed = DB::table('reward_awards')->where('id', $award->id)->where('status', 'pending')
            ->update(['status' => 'canceled', 'canceled_at' => now(), 'cancellation_reason' => $reason]);
        if ($changed) $this->audit($award, 'canceled', $reason);
    }

    public function cancelContent(string $org, string $type, string $id, string $reason): void
    {
        $ids = [$id];
        if ($type === 'question') $ids = [...$ids, ...DB::table('answers')->where('organization_id', $org)->where('question_id', $id)->pluck('id')->all()];
        $query = DB::table('reward_awards')->where('organization_id', $org)->where('status', 'pending');
        $query->where(fn ($q) => $q->where(fn ($q) => $q->where('source_type', $type)->where('source_id', $id))
            ->when($type === 'question', fn ($q) => $q->orWhere(fn ($q) => $q->where('source_type', 'answer')->whereIn('source_id', $ids))));
        foreach ($query->orderBy('id')->lockForUpdate()->get() as $award) $this->cancel($award, $reason);
    }

    public function cancelVote(string $org, string $type, string $id, string $actor): void
    {
        foreach (DB::table('reward_awards')->where('organization_id', $org)->where('source_type', $type)->where('source_id', $id)
            ->where('actor_user_id', $actor)->where('status', 'pending')->orderBy('id')->lockForUpdate()->get() as $award) $this->cancel($award, 'vote_removed');
    }

    public function audit(object $award, string $status, string $reason): void
    {
        DB::table('reward_award_transitions')->insert(['id' => (string) Str::uuid(), 'organization_id' => $award->organization_id, 'award_id' => $award->id,
            'status' => $status, 'reason' => $reason, 'points_amount' => $award->points_amount, 'coins_amount' => $award->coins_amount, 'occurred_at' => now()]);
    }
}
