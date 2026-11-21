<?php

namespace App\Filament\Pages;

use App\Auth\RemoteIdentityUser;
use App\Services\IdentityGatewayClient;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class Roles extends Page
{
    protected string $view = 'filament.pages.roles';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Role & quyền';

    protected static \UnitEnum|string|null $navigationGroup = 'Identity';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createRole')
                ->label('Tạo vai trò mới')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->visible(fn () => $this->canManage())
                ->action(fn () => $this->openCreateRole()),
        ];
    }

    /** @var array<int, array<string, mixed>> */
    public array $roles = [];

    /** @var array<int, array<string, mixed>> */
    public array $permissions = [];

    public ?string $selectedRoleId = null;

    /** @var array<int, string> */
    public array $permissionIds = [];

    public bool $showRoleForm = false;

    public ?string $editingRoleId = null;

    public string $roleName = '';

    public string $roleCode = '';

    public string $roleDescription = '';

    public function mount(): void
    {
        $this->refreshRoles();
    }

    public static function canAccess(): bool
    {
        return Filament::auth()->user() instanceof RemoteIdentityUser && Filament::auth()->user()->can('identity.roles.read');
    }

    public function getTitle(): string
    {
        return 'Role & quyền';
    }

    public function canManage(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof RemoteIdentityUser && $user->can('identity.roles.manage');
    }

    /** @return array<string, mixed>|null */
    public function selectedRole(): ?array
    {
        if ($this->selectedRoleId === null) {
            return null;
        }

        $role = collect($this->roles)->firstWhere('id', $this->selectedRoleId);

        return is_array($role) ? $role : null;
    }

    public function selectRole(string $roleId): void
    {
        $this->selectedRoleId = $roleId;
        $role = collect($this->roles)->firstWhere('id', $roleId);
        $this->permissionIds = collect($role['permissions'] ?? [])->pluck('id')->all();
    }

    public function updatedRoleName(string $value): void
    {
        if ($this->editingRoleId !== null || trim($this->roleCode) !== '') {
            return;
        }

        $slug = Str::slug($value, '_');
        if ($slug !== '' && ! preg_match('/^[a-z]/', $slug)) {
            $slug = 'role_'.$slug;
        }
        $this->roleCode = Str::limit($slug, 100, '');
    }

    public function openCreateRole(): void
    {
        abort_unless($this->canManage(), 403);
        $this->reset('editingRoleId', 'roleName', 'roleCode', 'roleDescription');
        $this->resetValidation();
        $this->showRoleForm = true;
    }

    public function openEditRole(string $roleId): void
    {
        abort_unless($this->canManage(), 403);
        $role = collect($this->roles)->firstWhere('id', $roleId);
        if (! is_array($role) || ($role['is_system'] ?? false)) {
            Notification::make()->title('Không thể sửa thông tin vai trò hệ thống.')->warning()->send();

            return;
        }

        $this->editingRoleId = $role['id'];
        $this->roleName = (string) ($role['name'] ?? '');
        $this->roleCode = (string) ($role['code'] ?? '');
        $this->roleDescription = (string) ($role['description'] ?? '');
        $this->resetValidation();
        $this->showRoleForm = true;
    }

    public function cancelRoleForm(): void
    {
        $this->reset('showRoleForm', 'editingRoleId', 'roleName', 'roleCode', 'roleDescription');
        $this->resetValidation();
    }

    public function saveRole(): void
    {
        abort_unless($this->canManage(), 403);

        $this->roleName = trim($this->roleName);
        $this->roleCode = trim($this->roleCode);
        if ($this->roleCode === '') {
            $slug = Str::slug($this->roleName, '_');
            if ($slug !== '' && ! preg_match('/^[a-z]/', $slug)) {
                $slug = 'role_'.$slug;
            }
            $this->roleCode = Str::limit($slug, 100, '');
        }

        $this->validate([
            'roleName' => ['required', 'string', 'max:255'],
            'roleCode' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/'],
            'roleDescription' => ['nullable', 'string', 'max:2000'],
        ], [
            'roleCode.regex' => 'Mã định danh chỉ gồm chữ thường a-z, số 0-9 và dấu gạch dưới (_), bắt đầu bằng chữ cái.',
        ], [
            'roleName' => 'tên vai trò',
            'roleCode' => 'mã định danh',
            'roleDescription' => 'mô tả',
        ]);

        $payload = [
            'name' => $this->roleName,
            'code' => $this->roleCode,
            'description' => trim($this->roleDescription) ?: null,
        ];

        $isEditing = $this->editingRoleId !== null;
        $response = app(IdentityGatewayClient::class)->send(
            $isEditing ? 'PATCH' : 'POST',
            $isEditing ? "roles/{$this->editingRoleId}" : 'roles',
            $this->token(),
            $payload,
        );

        if ($response === null) {
            Notification::make()
                ->title('Không thể lưu vai trò. Vui lòng kiểm tra mã định danh có bị trùng lặp hoặc vai trò hệ thống không.')
                ->danger()
                ->send();

            return;
        }

        $savedId = $response['data']['id'] ?? $this->editingRoleId;
        $this->cancelRoleForm();
        $this->refreshRoles();

        if (is_string($savedId) && $savedId !== '') {
            $this->selectRole($savedId);
        }

        Notification::make()
            ->title($isEditing ? 'Đã cập nhật thông tin vai trò.' : 'Đã tạo vai trò mới. Hãy tích chọn permission ở bên dưới.')
            ->success()
            ->send();
    }

    public function deleteRole(string $roleId): void
    {
        abort_unless($this->canManage(), 403);

        $response = app(IdentityGatewayClient::class)->send('DELETE', "roles/{$roleId}", $this->token());
        if ($response === null) {
            Notification::make()
                ->title('Không thể xoá vai trò. Vai trò hệ thống hoặc đang được gán cho nhân sự không thể xoá.')
                ->danger()
                ->send();

            return;
        }

        if ($this->selectedRoleId === $roleId) {
            $this->selectedRoleId = null;
            $this->permissionIds = [];
        }
        if ($this->editingRoleId === $roleId) {
            $this->cancelRoleForm();
        }

        $this->refreshRoles();
        Notification::make()->title('Đã xoá vai trò.')->success()->send();
    }

    public function savePermissions(): void
    {
        abort_unless($this->canManage(), 403);

        if ($this->selectedRoleId === null) {
            return;
        }
        $response = app(IdentityGatewayClient::class)->send('PATCH', "roles/{$this->selectedRoleId}/permissions", $this->token(), ['permission_ids' => $this->permissionIds]);
        $notification = Notification::make()->title($response === null ? 'Không thể cập nhật quyền (không thể tự sửa role đang gán cho chính mình hoặc vượt phạm vi quyền).' : 'Permission của role đã được cập nhật.');
        $response === null ? $notification->danger() : $notification->success();
        $notification->send();
        $this->refreshRoles();
    }

    public function refreshRoles(): void
    {
        $client = app(IdentityGatewayClient::class);
        $this->roles = $client->get('roles', $this->token())['data'] ?? [];
        $this->permissions = $client->get('permissions', $this->token())['data'] ?? [];
    }

    private function token(): string
    {
        return (string) session('admin.identity_token');
    }
}
