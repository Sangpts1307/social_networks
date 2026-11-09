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

    public string $statusFilter = '';

    public string $roleFilter = '';

    public int $pageNumber = 1;

    public int $lastPage = 1;

    public int $totalUsers = 0;

    public ?string $openMenu = null;

    public function toggleMenu(string $menu): void
    {
        abort_unless(in_array($menu, ['filters', 'columns'], true), 422);
        $this->openMenu = $this->openMenu === $menu ? null : $menu;
    }

    public function closeMenu(): void { $this->openMenu = null; }

    public array $visibleColumns = ['user' => true, 'roles' => true, 'status' => true, 'actions' => true];

    public function resetFilters(): void
    {
        $this->reset('statusFilter', 'roleFilter');
        $this->pageNumber = 1;
        $this->refreshUsers();
    }

    public function resetColumns(): void
    {
        $this->visibleColumns = ['user' => true, 'roles' => true, 'status' => true, 'actions' => true];
    }

    public function updatedStatusFilter(): void { $this->pageNumber = 1; $this->refreshUsers(); }

    public function updatedRoleFilter(): void { $this->pageNumber = 1; $this->refreshUsers(); }

    public function goToPage(int $page): void
    {
        $this->pageNumber = max(1, min($page, $this->lastPage));
        $this->refreshUsers();
    }

    /** @var array<int, array<string, mixed>> */
    public array $users = [];

    public array $selectedIds = [];

    public function toggleSelectPage(): void
    {
        $ids = array_column($this->users, 'id');
        $this->selectedIds = count(array_intersect($this->selectedIds, $ids)) === count($ids) ? [] : $ids;
    }

    public function deleteSelected(): void
    {
        abort_unless($this->canUpdateUsers(), 403);
        $allowed = collect($this->users)->pluck('id')->all();
        $failed = 0;
        foreach (array_intersect($this->selectedIds, $allowed) as $id) {
            if ($this->client()->send('DELETE', "users/{$id}", $this->token()) === null) $failed++;
        }
        $this->selectedIds = [];
        $this->refreshUsers();
        $this->notify($failed === 0, $failed ? "Không thể xoá {$failed} người dùng (có thể gồm tài khoản của bạn hoặc quản trị viên hệ thống)." : 'Đã xoá người dùng đã chọn.');
    }

    /** @var array<int, array<string, mixed>> */
    public array $roles = [];

    /** @var array<string, string> */
    public array $roleSelections = [];

    public array $scopeSelections = [];

    public array $unitSelections = [];

    public array $descendantSelections = [];

    public array $expirySelections = [];

    public array $orgUnits = [];

    public string $newDisplayName = '';

    public string $newEmail = '';

    public string $newEmployeeCode = '';

    public string $newOrgUnitId = '';

    public string $newPassword = '';

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
        $this->pageNumber = 1;
        $this->refreshUsers();
    }

    public function canUpdateUsers(): bool
    {
        return $this->currentUser()?->can('identity.users.update') ?? false;
    }

    public function canCreateUsers(): bool
    {
        return $this->currentUser()?->can('identity.users.create') ?? false;
    }

    public function createUser(): void
    {
        abort_unless($this->canCreateUsers(), 403);

        $this->validate([
            'newDisplayName' => ['required', 'string', 'max:255'],
            'newEmail' => ['required', 'email', 'max:255'],
            'newEmployeeCode' => ['nullable', 'string', 'max:64'],
            'newOrgUnitId' => ['required', 'uuid'],
            'newPassword' => ['required', 'string', 'min:12', 'max:72'],
        ], [], [
            'newDisplayName' => 'họ tên', 'newEmail' => 'email',
            'newEmployeeCode' => 'mã nhân viên', 'newOrgUnitId' => 'phòng ban',
            'newPassword' => 'mật khẩu ban đầu',
        ]);

        $response = $this->client()->send('POST', 'users', $this->token(), [
            'display_name' => trim($this->newDisplayName),
            'email' => trim($this->newEmail),
            'employee_code' => trim($this->newEmployeeCode) ?: null,
            'primary_org_unit_id' => $this->newOrgUnitId,
            'password' => $this->newPassword,
        ]);

        if ($response === null) {
            $this->notify(false, 'Không thể tạo người dùng. Kiểm tra email công ty, mã nhân viên, phòng ban và độ mạnh mật khẩu.');
            return;
        }

        $this->reset('newDisplayName', 'newEmail', 'newEmployeeCode', 'newOrgUnitId', 'newPassword');
        $this->notify(true, 'Đã tạo người dùng. Nhân sự sẽ phải đổi mật khẩu ở lần đăng nhập đầu tiên.');
        $this->refreshUsers();
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
        $response = $this->client()->get('users?'.http_build_query(array_filter(['q' => $this->search, 'status' => $this->statusFilter, 'role_id' => $this->roleFilter, 'per_page' => 20, 'page' => $this->pageNumber])), $this->token());
        $this->users = $response['data'] ?? [];
        $this->lastPage = max(1, (int) ($response['meta']['last_page'] ?? 1));
        $this->totalUsers = (int) ($response['meta']['total'] ?? count($this->users));
        if ($this->pageNumber > $this->lastPage) {
            $this->pageNumber = $this->lastPage;
            $this->refreshUsers();
            return;
        }
        $this->selectedIds = array_values(array_intersect($this->selectedIds, array_column($this->users, 'id')));
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
        $notification = Notification::make()->title($success ? $message : ($message ?: 'Không thể kết nối hoặc thao tác bị từ chối.'));
        $success ? $notification->success() : $notification->danger();
        $notification->send();
    }
}
