<?php

namespace App\Filament\Pages;

use App\Auth\RemoteIdentityUser;
use App\Services\IdentityGatewayClient;
use App\Services\SocialGatewayClient;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

class PostModeration extends ModulePlaceholder
{
    protected string $view = 'filament.pages.post-moderation';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static ?string $navigationLabel = 'Kiểm duyệt bài viết';

    protected static \UnitEnum|string|null $navigationGroup = 'Kiểm duyệt Social';

    protected static ?int $navigationSort = 10;

    protected static string $moduleDescription = 'Trung tâm kiểm duyệt nội dung mạng xã hội và tin tức nội bộ tích hợp AI Guard.';

    protected static string $moduleOwner = 'social-service';

    protected static array $moduleCapabilities = [
        'Hàng đợi báo cáo vi phạm',
        'AI Guard nhận diện nội dung nhạy cảm tự động',
        'Tạm ẩn & gỡ bỏ bài viết vi phạm',
        'Khôi phục & phê duyệt an toàn',
        'Khoá bình luận & audit log kiểm duyệt',
    ];

    public string $activeTab = 'posts'; // 'posts' | 'news'

    public string $statusFilter = 'all'; // 'all' | 'queue' | 'flagged' | 'reported' | 'hidden' | 'removed' | 'clear'

    public string $channelFilter = '';

    public string $search = '';

    public int $pageNumber = 1;

    public int $perPage = 15;

    public int $lastPage = 1;

    public int $totalPosts = 0;

    public ?string $openMenu = null;

    public function toggleMenu(string $menu): void
    {
        abort_unless(in_array($menu, ['filters', 'columns'], true), 422);
        $this->openMenu = $this->openMenu === $menu ? null : $menu;
    }

    public function closeMenu(): void
    {
        $this->openMenu = null;
    }

    /** @var array<string, bool> */
    public array $visibleColumns = [
        'content' => true,
        'channel' => true,
        'status' => true,
        'actions' => true,
    ];

    public function resetColumns(): void
    {
        $this->visibleColumns = [
            'content' => true,
            'channel' => true,
            'status' => true,
            'actions' => true,
        ];
    }

    public function resetFilters(): void
    {
        $this->statusFilter = 'all';
        $this->channelFilter = '';
        $this->search = '';
        $this->pageNumber = 1;
        $this->refreshPosts();
    }

    public function updatedStatusFilter(): void
    {
        $this->pageNumber = 1;
        $this->refreshPosts();
    }

    /** @var array<int, array<string, mixed>> */
    public array $posts = [];

    /** @var array<int, array<string, mixed>> */
    public array $channels = [];

    /** @var array<string, string> */
    public array $usersMap = [];

    /** @var array<string, int> */
    public array $summary = [
        'total_all' => 0,
        'total_news' => 0,
        'total_posts' => 0,
        'total_queue' => 0,
        'total_reported' => 0,
        'total_flagged' => 0,
        'total_hidden' => 0,
    ];

    public ?array $selectedPost = null;

    public bool $showDetailModal = false;

    public bool $showActionModal = false;

    public ?string $actionPostId = null;

    public string $actionType = 'hide';

    public string $actionReason = 'violates_policy';

    public string $actionNote = '';

    public function mount(): void
    {
        $this->loadUsers();
        $this->loadChannels();
        $this->refreshPosts();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof RemoteIdentityUser && ($user->can('social.moderation.manage') || $user->can('identity.roles.manage'));
    }

    public function switchTab(string $tab): void
    {
        abort_unless(in_array($tab, ['posts', 'news'], true), 422);
        $this->activeTab = $tab;
        $this->pageNumber = 1;
        $this->refreshPosts();
    }

    public function filterStatus(string $status): void
    {
        abort_unless(in_array($status, ['all', 'queue', 'flagged', 'reported', 'hidden', 'removed', 'clear'], true), 422);
        $this->statusFilter = $status;
        $this->pageNumber = 1;
        $this->refreshPosts();
    }

    public function updatedSearch(): void
    {
        $this->pageNumber = 1;
        $this->refreshPosts();
    }

    public function updatedChannelFilter(): void
    {
        $this->pageNumber = 1;
        $this->refreshPosts();
    }

    public function goToPage(int $page): void
    {
        $this->pageNumber = max(1, min($page, $this->lastPage));
        $this->refreshPosts();
    }

    public function refreshPosts(): void
    {
        $params = [
            'tab' => $this->activeTab,
            'status' => $this->statusFilter,
            'page' => $this->pageNumber,
            'per_page' => $this->perPage,
        ];

        if ($this->channelFilter !== '') {
            $params['channel_id'] = $this->channelFilter;
        }

        if (trim($this->search) !== '') {
            $params['q'] = trim($this->search);
        }

        $response = $this->socialClient()->adminPosts($this->token(), $params);

        if ($response === null) {
            $this->posts = [];
            $this->totalPosts = 0;
            $this->lastPage = 1;
            return;
        }

        $this->posts = array_map(function (array $post): array {
            Cache::put($this->postCacheKey($post['id']), $post, now()->addMinutes(30));

            // Keep large post bodies and embedded images out of Livewire's page snapshot.
            unset($post['body_markdown'], $post['body_plaintext'], $post['reports'], $post['latest_action']);
            return $post;
        }, $response['data'] ?? []);
        $meta = $response['meta'] ?? [];
        $this->totalPosts = (int) ($meta['total'] ?? count($this->posts));
        $this->lastPage = max(1, (int) ($meta['last_page'] ?? 1));
        if (isset($response['summary']) && is_array($response['summary'])) {
            $this->summary = array_merge($this->summary, $response['summary']);
        }
    }

    public function openDetail(string $postId): void
    {
        if (collect($this->posts)->contains('id', $postId)) {
            $post = Cache::get($this->postCacheKey($postId));
            if (!is_array($post)) {
                $this->refreshPosts();
                $post = Cache::get($this->postCacheKey($postId));
            }
            if (!is_array($post)) {
                return;
            }

            // Data URI images are already represented by their attachment marker.
            // Sending base64 through Livewire would exceed its request limit again.
            foreach (['body_markdown', 'body_plaintext'] as $field) {
                $post[$field] = preg_replace('/\(data:image\/[^)]+\)/i', '(Ảnh đính kèm)', $post[$field] ?? '');
            }
            $this->selectedPost = $post;
            $this->showDetailModal = true;
        }
    }

    private function postCacheKey(string $postId): string
    {
        return 'admin-post-moderation:' . hash('sha256', $this->token()) . ':' . $postId;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedPost = null;
    }

    public function openAction(string $postId, string $action): void
    {
        abort_unless(in_array($action, ['hide', 'remove', 'restore', 'approve', 'dismiss', 'lock_comments', 'unlock_comments'], true), 422);
        $this->actionPostId = $postId;
        $this->actionType = $action;
        $this->actionReason = match ($action) {
            'hide', 'remove' => 'violates_policy',
            'approve', 'dismiss' => 'false_positive',
            'restore' => 'resolved_safe',
            'lock_comments', 'unlock_comments' => 'moderation_review',
            default => 'policy_check',
        };
        $this->actionNote = '';
        $this->showActionModal = true;
    }

    public function closeAction(): void
    {
        $this->showActionModal = false;
        $this->actionPostId = null;
        $this->actionNote = '';
    }

    public function submitAction(): void
    {
        if (!$this->actionPostId) {
            return;
        }

        $response = $this->socialClient()->moderate(
            $this->token(),
            $this->actionPostId,
            $this->actionType,
            $this->actionReason,
            trim($this->actionNote) ?: null
        );

        if ($response === null) {
            Notification::make()->title('Thao tác kiểm duyệt thất bại hoặc bị từ chối.')->danger()->send();
            return;
        }

        $message = match ($this->actionType) {
            'hide' => 'Đã tạm ẩn bài viết vi phạm.',
            'remove' => 'Đã gỡ bỏ bài viết khỏi hệ thống.',
            'restore' => 'Đã khôi phục bài viết thành công.',
            'approve' => 'Đã phê duyệt an toàn và giải quyết các cảnh báo.',
            'dismiss' => 'Đã bác bỏ báo cáo vi phạm.',
            'lock_comments' => 'Đã khóa bình luận bài viết.',
            'unlock_comments' => 'Đã mở khóa bình luận bài viết.',
            default => 'Đã cập nhật trạng thái kiểm duyệt.',
        };

        Notification::make()->title($message)->success()->send();
        $this->closeAction();
        $this->closeDetail();
        $this->refreshPosts();
    }

    public function quickHide(string $postId): void
    {
        $response = $this->socialClient()->moderate($this->token(), $postId, 'hide', 'sensitive_content', 'Tạm ẩn nhanh từ danh sách kiểm duyệt.');
        if ($response !== null) {
            Notification::make()->title('Đã tạm ẩn bài viết.')->warning()->send();
            $this->refreshPosts();
        } else {
            Notification::make()->title('Không thể ẩn bài viết.')->danger()->send();
        }
    }

    public function quickRestore(string $postId): void
    {
        $response = $this->socialClient()->moderate($this->token(), $postId, 'restore', 'approved_by_admin', 'Khôi phục bài viết sau khi kiểm tra.');
        if ($response !== null) {
            Notification::make()->title('Đã khôi phục bài viết về trạng thái đã đăng.')->success()->send();
            $this->refreshPosts();
        } else {
            Notification::make()->title('Không thể khôi phục bài viết.')->danger()->send();
        }
    }

    public function quickApprove(string $postId): void
    {
        $response = $this->socialClient()->moderate($this->token(), $postId, 'approve', 'false_positive', 'Admin đã kiểm tra và phê duyệt an toàn.');
        if ($response !== null) {
            Notification::make()->title('Đã đánh dấu an toàn và giải quyết các cảnh báo.')->success()->send();
            $this->refreshPosts();
        } else {
            Notification::make()->title('Không thể phê duyệt bài viết.')->danger()->send();
        }
    }

    public function scanPostWithAi(string $postId): void
    {
        $response = $this->socialClient()->scanPost($this->token(), $postId);
        if ($response === null) {
            Notification::make()->title('Không thể quét bài viết bằng AI Guard.')->danger()->send();
            return;
        }

        $data = $response['data'] ?? [];
        if (!empty($data['is_flagged'])) {
            $cat = $data['category'] ?? 'Nội dung nhạy cảm';
            Notification::make()
                ->title("AI Guard phát hiện rủi ro: {$cat} (Điểm rủi ro: " . round(($data['risk_score'] ?? 0.8) * 100) . "%)")
                ->danger()
                ->send();
        } else {
            Notification::make()
                ->title('AI Guard: Bài viết an toàn, không phát hiện vi phạm.')
                ->success()
                ->send();
        }

        $this->refreshPosts();
    }

    public function scanAllWithAi(): void
    {
        $response = $this->socialClient()->scanAll($this->token());
        if ($response === null) {
            Notification::make()->title('Không thể kích hoạt quét hàng loạt.')->danger()->send();
            return;
        }

        $data = $response['data'] ?? [];
        $scanned = $data['scanned_count'] ?? 0;
        $flagged = $data['flagged_count'] ?? 0;

        Notification::make()
            ->title("AI Guard hoàn tất: Đã quét {$scanned} bài viết, phát hiện {$flagged} bài viết có rủi ro.")
            ->info()
            ->send();

        $this->refreshPosts();
    }

    private function loadChannels(): void
    {
        $response = $this->socialClient()->channels($this->token());
        $this->channels = $response['data'] ?? [];
    }

    private function loadUsers(): void
    {
        $response = app(IdentityGatewayClient::class)->get('users?per_page=100', $this->token());
        $users = $response['data'] ?? [];
        $map = [];
        foreach ($users as $user) {
            if (isset($user['id'], $user['display_name'])) {
                $map[$user['id']] = $user['display_name'];
            }
        }
        $this->usersMap = $map;
    }

    public function getAuthorName(?string $userId): string
    {
        if (!$userId) {
            return 'Hệ thống';
        }
        return $this->usersMap[$userId] ?? 'Người dùng (' . substr($userId, 0, 8) . ')';
    }

    private function token(): string
    {
        return (string) session('admin.identity_token');
    }

    private function socialClient(): SocialGatewayClient
    {
        return app(SocialGatewayClient::class);
    }
}
