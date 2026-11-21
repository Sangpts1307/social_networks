<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ContentModerationService;
use App\Services\SocialAccess;
use App\Services\SocialOutbox;
use App\Services\SocialPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModerationController extends Controller
{
    public function __construct(
        private SocialAccess $access,
        private SocialPagination $pages,
        private SocialOutbox $events,
        private ContentModerationService $scanner
    ) {}

    public function report(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $data = $request->validate([
            'reason_code' => ['required', 'in:spam,secret_exposure,harassment,misinformation,toxic_content,other'],
            'detail' => ['nullable', 'string', 'max:5000'],
        ]);

        return DB::transaction(function () use ($actor, $postId, $data) {
            $this->access->post($actor, $postId, lock: true);
            $existing = DB::table('post_reports')
                ->where('target_type', 'post')
                ->where('target_id', $postId)
                ->where('reporter_user_id', $actor['user_id'])
                ->whereIn('status', ['open', 'under_review'])
                ->first();

            if ($existing) {
                return response()->json(['data' => ['id' => $existing->id, 'status' => $existing->status]]);
            }

            $id = (string) Str::uuid();
            DB::table('post_reports')->insert([
                'id' => $id,
                'organization_id' => $actor['organization_id'],
                'target_type' => 'post',
                'target_id' => $postId,
                'reporter_user_id' => $actor['user_id'],
                'report_reason_code' => $data['reason_code'],
                'report_detail' => isset($data['detail']) ? strip_tags($data['detail']) : null,
                'source' => 'user',
                'status' => 'open',
                'opened_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->events->record($actor, 'post', $postId, 'post.reported', ['report_id' => $id]);

            return response()->json(['data' => ['id' => $id, 'status' => 'open']], 201);
        }, 3);
    }

    public function reports(Request $request)
    {
        $actor = $this->access->actor($request);
        $this->access->permission($actor, 'social.moderation.manage');
        $data = $request->validate(['status' => ['sometimes', 'in:open,under_review,resolved,dismissed']]);
        $q = DB::table('post_reports as r')
            ->where('r.organization_id', $actor['organization_id'])
            ->where('r.status', $data['status'] ?? 'open')
            ->whereIn('r.target_id', $this->access->posts($actor)->select('p.id'))
            ->select('r.*');

        return response()->json($this->pages->page($q, $request, 'r.created_at', 'r.id', 'reports:' . ($data['status'] ?? 'open')));
    }

    public function adminPosts(Request $request)
    {
        $actor = $this->access->actor($request);
        $this->access->permission($actor, 'social.moderation.manage');

        $filters = $request->validate([
            'tab' => ['sometimes', 'in:all,news,posts'],
            'status' => ['sometimes', 'in:all,queue,flagged,reported,hidden,removed,clear'],
            'channel_id' => ['sometimes', 'uuid'],
            'q' => ['sometimes', 'nullable', 'string', 'max:200'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $orgId = $actor['organization_id'];

        $query = DB::table('posts as p')
            ->leftJoin('social_channels as c', 'c.id', '=', 'p.channel_id')
            ->where('p.organization_id', $orgId)
            ->whereNull('p.deleted_at');

        $tab = $filters['tab'] ?? 'all';
        if ($tab === 'news') {
            $query->where('p.post_type', 'news');
        } elseif ($tab === 'posts') {
            $query->whereIn('p.post_type', ['update', 'announcement', 'event']);
        }

        if (!empty($filters['channel_id'])) {
            $query->where('p.channel_id', $filters['channel_id']);
        }

        if (!empty($filters['q'])) {
            $term = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($filters['q'])) . '%';
            $query->where(function ($sub) use ($term) {
                $sub->whereRaw("p.title LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("p.body_plaintext LIKE ? ESCAPE '!'", [$term]);
            });
        }

        $status = $filters['status'] ?? 'all';
        if ($status === 'queue') {
            $query->where(function ($sub) {
                $sub->where('p.moderation_status', 'flagged')
                    ->orWhere('p.publication_status', 'hidden')
                    ->orWhereExists(function ($rep) {
                        $rep->selectRaw('1')
                            ->from('post_reports as r')
                            ->whereColumn('r.target_id', 'p.id')
                            ->where('r.target_type', 'post')
                            ->whereIn('r.status', ['open', 'under_review']);
                    });
            });
        } elseif ($status === 'flagged') {
            $query->where(function ($sub) {
                $sub->where('p.moderation_status', 'flagged')
                    ->orWhereExists(function ($rep) {
                        $rep->selectRaw('1')
                            ->from('post_reports as r')
                            ->whereColumn('r.target_id', 'p.id')
                            ->where('r.target_type', 'post')
                            ->where('r.source', 'ai')
                            ->whereIn('r.status', ['open', 'under_review']);
                    });
            });
        } elseif ($status === 'reported') {
            $query->whereExists(function ($rep) {
                $rep->selectRaw('1')
                    ->from('post_reports as r')
                    ->whereColumn('r.target_id', 'p.id')
                    ->where('r.target_type', 'post')
                    ->where('r.source', 'user')
                    ->whereIn('r.status', ['open', 'under_review']);
            });
        } elseif ($status === 'hidden') {
            $query->where('p.publication_status', 'hidden');
        } elseif ($status === 'removed') {
            $query->where('p.publication_status', 'removed');
        } elseif ($status === 'clear') {
            $query->where('p.moderation_status', 'clear')
                  ->where('p.publication_status', 'published');
        }

        $total = (clone $query)->count();
        $perPage = (int) ($filters['per_page'] ?? 15);
        $page = (int) ($filters['page'] ?? 1);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $posts = $query->select([
            'p.id', 'p.organization_id', 'p.channel_id', 'p.author_user_id',
            'p.post_type', 'p.title', 'p.body_markdown', 'p.body_plaintext',
            'p.visibility', 'p.publication_status', 'p.moderation_status',
            'p.published_at', 'p.comments_locked_at', 'p.created_at', 'p.updated_at',
            'c.name as channel_name', 'c.code as channel_code',
        ])
        ->selectSub(DB::table('post_reactions')->selectRaw('COUNT(*)')->whereColumn('post_id', 'p.id'), 'reaction_count')
        ->selectSub(DB::table('post_comments')->selectRaw('COUNT(*)')->whereColumn('post_id', 'p.id')->whereNull('deleted_at'), 'comment_count')
        ->orderByDesc('p.created_at')
        ->offset($offset)
        ->limit($perPage)
        ->get();

        $postIds = $posts->pluck('id')->all();

        $reports = DB::table('post_reports')
            ->where('target_type', 'post')
            ->whereIn('target_id', $postIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('target_id');

        $actions = DB::table('moderation_actions')
            ->where('target_type', 'post')
            ->whereIn('target_id', $postIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('target_id');

        $enriched = $posts->map(function ($post) use ($reports, $actions) {
            $postReports = $reports->get($post->id, collect());
            $postActions = $actions->get($post->id, collect());

            $openReports = $postReports->whereIn('status', ['open', 'under_review']);
            $aiReport = $postReports->where('source', 'ai')->first();
            $userReports = $postReports->where('source', 'user')->values();

            $aiRiskScore = $aiReport ? (float) $aiReport->risk_score : null;
            $aiAlert = null;
            if ($aiReport) {
                $scorePct = round(($aiReport->risk_score ?? 0.8) * 100);
                $reasonDesc = match ($aiReport->report_reason_code) {
                    'secret_exposure' => 'Lộ API Key / Bảo mật',
                    'toxic_content' => 'Ngôn từ nhạy cảm',
                    'harassment' => 'Quấy rối / Đe dọa',
                    'spam' => 'Spam / Cờ bạc',
                    default => 'Vi phạm chính sách',
                };
                $aiAlert = "AI Cảnh báo: {$reasonDesc} ({$scorePct}%)";
            }

            return [
                'id' => $post->id,
                'channel_id' => $post->channel_id,
                'channel_name' => $post->channel_name ?? 'Chung',
                'channel_code' => $post->channel_code ?? 'general',
                'author_user_id' => $post->author_user_id,
                'post_type' => $post->post_type,
                'title' => $post->title,
                'body_markdown' => $post->body_markdown,
                'body_plaintext' => $post->body_plaintext,
                'visibility' => $post->visibility,
                'publication_status' => $post->publication_status,
                'moderation_status' => $post->moderation_status,
                'published_at' => $post->published_at,
                'comments_locked_at' => $post->comments_locked_at,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
                'reaction_count' => (int) $post->reaction_count,
                'comment_count' => (int) $post->comment_count,
                'open_reports_count' => $openReports->count(),
                'user_reports_count' => $userReports->count(),
                'ai_risk_score' => $aiRiskScore,
                'ai_alert' => $aiAlert,
                'reports' => $postReports->values()->all(),
                'latest_action' => $postActions->first(),
            ];
        });

        $summary = [
            'total_all' => DB::table('posts')->where('organization_id', $orgId)->whereNull('deleted_at')->count(),
            'total_news' => DB::table('posts')->where('organization_id', $orgId)->whereNull('deleted_at')->where('post_type', 'news')->count(),
            'total_posts' => DB::table('posts')->where('organization_id', $orgId)->whereNull('deleted_at')->whereIn('post_type', ['update', 'announcement', 'event'])->count(),
            'total_queue' => DB::table('posts')->where('organization_id', $orgId)->whereNull('deleted_at')->where(function ($sub) {
                $sub->where('moderation_status', 'flagged')
                    ->orWhere('publication_status', 'hidden')
                    ->orWhereExists(function ($rep) {
                        $rep->selectRaw('1')->from('post_reports as r')->whereColumn('r.target_id', 'posts.id')->where('r.target_type', 'post')->whereIn('r.status', ['open', 'under_review']);
                    });
            })->count(),
            'total_reported' => DB::table('post_reports')->where('organization_id', $orgId)->where('target_type', 'post')->where('source', 'user')->whereIn('status', ['open', 'under_review'])->distinct('target_id')->count('target_id'),
            'total_flagged' => DB::table('posts')->where('organization_id', $orgId)->whereNull('deleted_at')->where('moderation_status', 'flagged')->count(),
            'total_hidden' => DB::table('posts')->where('organization_id', $orgId)->whereNull('deleted_at')->where('publication_status', 'hidden')->count(),
        ];

        return response()->json([
            'data' => $enriched,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
            ],
            'summary' => $summary,
        ]);
    }

    public function moderate(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $this->access->permission($actor, 'social.moderation.manage');
        $data = $request->validate([
            'action' => ['required', 'in:hide,remove,restore,lock_comments,unlock_comments,approve,dismiss'],
            'reason_code' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/D'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        return DB::transaction(function () use ($actor, $postId, $data) {
            $post = $this->access->post($actor, $postId, moderator: true, lock: true);
            $before = [
                'publication_status' => $post->publication_status,
                'moderation_status' => $post->moderation_status,
                'comments_locked_at' => $post->comments_locked_at,
            ];
            $after = $before;

            if (in_array($data['action'], ['hide', 'remove'], true)) {
                abort_unless(in_array($post->publication_status, ['published', 'hidden', 'removed'], true), 409, 'Only previously published posts can be moderated.');
                $after['publication_status'] = $data['action'] === 'hide' ? 'hidden' : 'removed';
                $after['moderation_status'] = 'blocked';
            } elseif ($data['action'] === 'restore') {
                abort_unless(in_array($post->publication_status, ['hidden', 'removed'], true) && $post->published_at !== null, 409, 'Post cannot be restored.');
                $after['publication_status'] = 'published';
                $after['moderation_status'] = 'clear';
            } elseif (in_array($data['action'], ['approve', 'dismiss'], true)) {
                $after['moderation_status'] = 'clear';
                if ($post->publication_status === 'hidden' && $post->published_at !== null) {
                    $after['publication_status'] = 'published';
                }
            } else {
                abort_unless(in_array($post->publication_status, ['published', 'hidden'], true), 409, 'Post is not active.');
                $after['comments_locked_at'] = $data['action'] === 'lock_comments' ? now()->toDateTimeString() : null;
            }

            DB::table('posts')->where('id', $postId)->update([...$after, 'updated_at' => now(), 'updated_by_user_id' => $actor['user_id']]);
            $id = (string) Str::uuid();
            DB::table('moderation_actions')->insert([
                'id' => $id,
                'target_type' => 'post',
                'target_id' => $postId,
                'action_type' => $data['action'],
                'reason_code' => $data['reason_code'],
                'note' => isset($data['note']) ? strip_tags($data['note']) : null,
                'actor_type' => 'user',
                'actor_user_id' => $actor['user_id'],
                'previous_state_json' => json_encode($before, JSON_THROW_ON_ERROR),
                'new_state_json' => json_encode($after, JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);

            if (in_array($data['action'], ['hide', 'remove', 'approve', 'dismiss'], true)) {
                DB::table('post_reports')->where('organization_id', $actor['organization_id'])
                    ->where('target_type', 'post')->where('target_id', $postId)
                    ->whereIn('status', ['open', 'under_review'])
                    ->update([
                        'status' => $data['action'] === 'dismiss' ? 'dismissed' : 'resolved',
                        'resolved_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            $this->events->record($actor, 'post', $postId, 'post.moderated', ['action' => $data['action'], 'audit_id' => $id]);

            try {
                $auditAction = match ($data['action']) {
                    'hide' => 'HIDE_POST',
                    'remove' => 'REMOVE_POST',
                    'restore' => 'RESTORE_POST',
                    'lock_comments' => 'LOCK_COMMENTS',
                    'unlock_comments' => 'UNLOCK_COMMENTS',
                    'approve' => 'APPROVE_POST',
                    'dismiss' => 'DISMISS_REPORT',
                    default => strtoupper($data['action'])
                };
                app(\App\Services\AuditLoggerClient::class)->record(
                    organizationId: $actor['organization_id'],
                    actorUserId: $actor['user_id'],
                    eventType: 'social.post.moderated',
                    entityType: 'post',
                    entityId: $postId,
                    action: $auditAction,
                    payload: [
                        'action' => $data['action'],
                        'reason_code' => $data['reason_code'],
                        'note' => $data['note'] ?? null,
                        'previous_state' => $before,
                        'new_state' => $after,
                    ],
                    riskLevel: in_array($data['action'], ['remove', 'hide']) ? 'medium' : 'normal',
                    status: 'success',
                    actorName: $actor['display_name'] ?? 'Moderator',
                    actorEmail: $actor['email'] ?? null,
                    module: 'social'
                );
            } catch (\Throwable) {}

            return response()->json(['data' => ['id' => $postId, ...$after]]);
        }, 3);
    }

    public function scanPost(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $this->access->permission($actor, 'social.moderation.manage');

        $post = $this->access->post($actor, $postId, moderator: true);
        $result = $this->scanner->inspect($post->title, $post->body_markdown);

        if ($result['is_flagged']) {
            DB::transaction(function () use ($actor, $postId, $post, $result) {
                $newModStatus = $result['should_auto_hide'] ? 'blocked' : 'flagged';
                $newPubStatus = $result['should_auto_hide'] ? 'hidden' : $post->publication_status;

                DB::table('posts')->where('id', $postId)->update([
                    'moderation_status' => $newModStatus,
                    'publication_status' => $newPubStatus,
                    'updated_at' => now(),
                ]);

                $reportId = (string) Str::uuid();
                DB::table('post_reports')->insert([
                    'id' => $reportId,
                    'organization_id' => $actor['organization_id'],
                    'target_type' => 'post',
                    'target_id' => $postId,
                    'reporter_user_id' => null,
                    'report_reason_code' => $result['reason_code'],
                    'report_detail' => $result['detail'],
                    'source' => 'ai',
                    'risk_score' => $result['risk_score'],
                    'status' => 'open',
                    'opened_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('moderation_actions')->insert([
                    'id' => (string) Str::uuid(),
                    'report_id' => $reportId,
                    'target_type' => 'post',
                    'target_id' => $postId,
                    'action_type' => $result['should_auto_hide'] ? 'hide' : 'flag',
                    'reason_code' => $result['reason_code'],
                    'note' => $result['detail'],
                    'actor_type' => 'ai',
                    'actor_user_id' => $actor['user_id'],
                    'created_at' => now(),
                ]);
            });

            try {
                app(\App\Services\AuditLoggerClient::class)->record(
                    organizationId: $actor['organization_id'],
                    actorUserId: null,
                    eventType: $result['should_auto_hide'] ? 'social.post.auto_blocked' : 'social.post.auto_flagged',
                    entityType: 'post',
                    entityId: $postId,
                    action: $result['should_auto_hide'] ? 'AUTO_BLOCK_POST' : 'AUTO_FLAG_POST',
                    payload: [
                        'category' => $result['category'] ?? 'policy_violation',
                        'detail' => $result['detail'] ?? '',
                        'risk_score' => $result['risk_score'] ?? 0.8,
                    ],
                    riskLevel: $result['should_auto_hide'] ? 'high' : 'medium',
                    status: $result['should_auto_hide'] ? 'blocked' : 'success',
                    actorName: 'System Guard (AI)',
                    actorEmail: 'system.ai@internal.network',
                    module: 'social'
                );
            } catch (\Throwable) {}
        }

        return response()->json([
            'data' => [
                'post_id' => $postId,
                'is_flagged' => $result['is_flagged'],
                'risk_score' => $result['risk_score'],
                'category' => $result['category'],
                'detail' => $result['detail'],
                'matches' => $result['matches'],
            ],
        ]);
    }

    public function scanAll(Request $request)
    {
        $actor = $this->access->actor($request);
        $this->access->permission($actor, 'social.moderation.manage');

        $posts = DB::table('posts')->where('organization_id', $actor['organization_id'])
            ->whereNull('deleted_at')
            ->where('moderation_status', 'clear')
            ->get(['id', 'title', 'body_markdown']);

        $scanned = 0;
        $flagged = 0;

        foreach ($posts as $post) {
            $scanned++;
            $result = $this->scanner->inspect($post->title, $post->body_markdown);
            if ($result['is_flagged']) {
                $flagged++;
                DB::transaction(function () use ($actor, $post, $result) {
                    $newModStatus = $result['should_auto_hide'] ? 'blocked' : 'flagged';
                    $newPubStatus = $result['should_auto_hide'] ? 'hidden' : 'published';

                    DB::table('posts')->where('id', $post->id)->update([
                        'moderation_status' => $newModStatus,
                        'publication_status' => $newPubStatus,
                        'updated_at' => now(),
                    ]);

                    $reportId = (string) Str::uuid();
                    DB::table('post_reports')->insert([
                        'id' => $reportId,
                        'organization_id' => $actor['organization_id'],
                        'target_type' => 'post',
                        'target_id' => $post->id,
                        'reporter_user_id' => null,
                        'report_reason_code' => $result['reason_code'],
                        'report_detail' => $result['detail'],
                        'source' => 'ai',
                        'risk_score' => $result['risk_score'],
                        'status' => 'open',
                        'opened_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
            }
        }

        return response()->json([
            'data' => [
                'scanned_count' => $scanned,
                'flagged_count' => $flagged,
                'message' => "Đã quét {$scanned} bài viết. Phát hiện {$flagged} bài viết có rủi ro.",
            ],
        ]);
    }
}
