<?php

namespace App\Filament\Pages;

use App\Auth\RemoteIdentityUser;
use App\Services\IdentityGatewayClient;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class Users extends Page
{
    protected string $view = 'filament.pages.users';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Người dùng';

    protected static \UnitEnum|string|null $navigationGroup = 'Identity';

    public string $search = '';

    /** @var array<int, array<string, mixed>> */
    public array $users = [];

    /** @var array<int, array<string, mixed>> */
    public array $roles = [];

    /** @var array<string, string> */
    public array $roleSelections = [];

    public array $scopeSelections = [];

    public array $unitSelections = [];

    public array $descendantSelections = [];

    public array $expirySelections = [];

    public array $orgUnits = [];

    public ?array $effectiveAuthorization = null;

    public string $effectiveUserName = '';

    public function showEffectivePermissions(string $userId): void
    {
        $user = $this->client()->get("users/{$userId}", $this->token())['data'] ?? [];
        $this->effectiveAuthorization = $user['authorization'] ?? null;
        $this->effectiveUserName = $user['display_name'] ?? '';
    }

    public function mount(): void
    {
        $this->refreshUsers();
        $this->roles = $this->client()->get('roles', $this->token())['data'] ?? [];
        $this->orgUnits = $this->client()->get('org-units', $this->token())['data'] ?? [];
    }

    public static function canAccess(): bool
    {
        return Filament::auth()->user() instanceof RemoteIdentityUser && Filament::auth()->user()->can('identity.users.read');
    }

    public function getTitle(): string
    {
        return 'Người dùng';
    }

    public function updatedSearch(): void
    {
        $this->refreshUsers();
    }

    public function canUpdateUsers(): bool
    {
        return $this->currentUser()?->can('identity.users.update') ?? false;
    }

    public function canUnlockUsers(): bool
    {
        return $this->currentUser()?->can('identity.users.unlock') ?? false;
    }

    public function canManageRoles(): bool
    {
        return $this->currentUser()?->can('identity.roles.manage') ?? false;
    }

    public function refreshUsers(): void
    {
        $response = $this->client()->get('users?'.http_build_query(array_filter(['q' => $this->search, 'per_page' => 50])), $this->token());
        $this->users = $response['data'] ?? [];
    }

    public function setStatus(string $userId, string $status): void
    {
        $response = $this->client()->send('PATCH', "users/{$userId}/status", $this->token(), ['status' => $status]);
        $this->notify($response !== null, 'Trạng thái tài khoản đã được cập nhật.');
        $this->refreshUsers();
    }

    public function unlock(string $userId): void
    {
        $response = $this->client()->send('POST', "users/{$userId}/unlock", $this->token());
        $this->notify($response !== null, 'Tài khoản đã được mở khóa.');
        $this->refreshUsers();
    }

    public function forcePasswordChange(string $userId): void
    {
        $response = $this->client()->send('POST', "users/{$userId}/force-password-change", $this->token());
        $this->notify($response !== null, 'Đã yêu cầu người dùng đổi mật khẩu ở lần sử dụng tiếp theo.');
        $this->refreshUsers();
    }

    public function assignRole(string $userId): void
    {
        $roleId = $this->roleSelections[$userId] ?? null;
        if (! is_string($roleId) || $roleId === '') {
            $this->notify(false, 'Hãy chọn role trước khi gán.');

            return;
        }

        $this->validate(["expirySelections.{$userId}" => ['nullable', 'date']]);
        $expiry = ($this->expirySelections[$userId] ?? '') ? Carbon::parse($this->expirySelections[$userId], 'Asia/Ho_Chi_Minh')->utc()->toISOString() : null;
        $response = $this->client()->send('POST', "users/{$userId}/role-assignments", $this->token(), ['role_id' => $roleId, 'scope_type' => $this->scopeSelections[$userId] ?? 'organization', 'org_unit_id' => ($this->scopeSelections[$userId] ?? 'organization') === 'org_unit' ? ($this->unitSelections[$userId] ?? null) : null, 'include_descendants' => ($this->scopeSelections[$userId] ?? 'organization') === 'org_unit' && ($this->descendantSelections[$userId] ?? false), 'expires_at' => $expiry]);
        $this->notify($response !== null, 'Đã gán role cho người dùng.');
        unset($this->roleSelections[$userId]);
        $this->refreshUsers();
    }

    public function removeRole(string $userId, string $assignmentId): void
    {
        $response = $this->client()->send('DELETE', "users/{$userId}/role-assignments/{$assignmentId}", $this->token());
        $this->notify($response !== null, 'Đã gỡ role khỏi người dùng.');
        $this->refreshUsers();
    }

    private function currentUser(): ?RemoteIdentityUser
    {
        $user = Filament::auth()->user();

        return $user instanceof RemoteIdentityUser ? $user : null;
    }

    private function token(): string
    {
        return (string) session('admin.identity_token');
    }

    private function client(): IdentityGatewayClient
    {
        return app(IdentityGatewayClient::class);
    }

    private function notify(bool $success, string $message): void
    {
        $notification = Notification::make()->title($success ? $message : 'Không thể kết nối hoặc thao tác bị từ chối.');
        $success ? $notification->success() : $notification->danger();
        $notification->send();
    }
}
