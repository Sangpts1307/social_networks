<?php

namespace App\Filament\Pages;

use App\Auth\RemoteIdentityUser;
use App\Services\IdentityGatewayClient;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Roles extends Page
{
    protected string $view = 'filament.pages.roles';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Role & quyền';

    protected static \UnitEnum|string|null $navigationGroup = 'Identity';

    /** @var array<int, array<string, mixed>> */
    public array $roles = [];

    /** @var array<int, array<string, mixed>> */
    public array $permissions = [];

    public ?string $selectedRoleId = null;

    /** @var array<int, string> */
    public array $permissionIds = [];

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

    public function selectRole(string $roleId): void
    {
        $this->selectedRoleId = $roleId;
        $role = collect($this->roles)->firstWhere('id', $roleId);
        $this->permissionIds = collect($role['permissions'] ?? [])->pluck('id')->all();
    }

    public function savePermissions(): void
    {
        if ($this->selectedRoleId === null) {
            return;
        }
        $response = app(IdentityGatewayClient::class)->send('PATCH', "roles/{$this->selectedRoleId}/permissions", $this->token(), ['permission_ids' => $this->permissionIds]);
        $notification = Notification::make()->title($response === null ? 'Không thể cập nhật quyền.' : 'Permission của role đã được cập nhật.');
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
