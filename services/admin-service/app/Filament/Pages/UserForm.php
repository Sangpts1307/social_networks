<?php

namespace App\Filament\Pages;

use App\Auth\RemoteIdentityUser;
use App\Services\IdentityGatewayClient;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class UserForm extends Page
{
    protected string $view = 'filament.pages.user-form';
    protected static ?string $slug = 'users/form';
    protected static bool $shouldRegisterNavigation = false;

    public ?string $recordId = null;
    public string $displayName = '';
    public string $email = '';
    public string $employeeCode = '';
    public string $orgUnitId = '';
    public string $password = '';
    public array $orgUnits = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        return $user instanceof RemoteIdentityUser && ($user->can('identity.users.create') || $user->can('identity.users.update'));
    }

    public function mount(): void
    {
        $this->recordId = request()->query('id');
        $this->orgUnits = $this->client()->get('org-units', $this->token())['data'] ?? [];
        if ($this->recordId) {
            abort_unless(Filament::auth()->user()->can('identity.users.update'), 403);
            $user = $this->client()->get("users/{$this->recordId}", $this->token())['data'] ?? null;
            abort_unless($user, 404);
            $this->displayName = $user['display_name'];
            $this->email = $user['email'];
            $this->employeeCode = $user['employee_code'] ?? '';
            $this->orgUnitId = $user['primary_org_unit']['id'] ?? '';
        } else {
            abort_unless(Filament::auth()->user()->can('identity.users.create'), 403);
        }
    }

    public function getTitle(): string { return $this->recordId ? 'Sửa người dùng' : 'Tạo người dùng'; }

    public function save(): void
    {
        abort_unless(Filament::auth()->user()->can($this->recordId ? 'identity.users.update' : 'identity.users.create'), 403);
        $rules = ['displayName' => ['required', 'string', 'max:255'], 'orgUnitId' => ['required', 'uuid']];
        if (! $this->recordId) {
            $rules += ['email' => ['required', 'email', 'max:255'], 'employeeCode' => ['nullable', 'string', 'max:64'], 'password' => ['required', 'string', 'min:12', 'max:72']];
        }
        $this->validate($rules);
        $payload = ['display_name' => trim($this->displayName), 'primary_org_unit_id' => $this->orgUnitId];
        if (! $this->recordId) $payload += ['email' => trim($this->email), 'employee_code' => trim($this->employeeCode) ?: null, 'password' => $this->password];
        $response = $this->client()->send($this->recordId ? 'PATCH' : 'POST', $this->recordId ? "users/{$this->recordId}" : 'users', $this->token(), $payload);
        if ($response === null) {
            Notification::make()->title('Không thể lưu. Kiểm tra email công ty, phòng ban và mật khẩu.')->danger()->send();
            return;
        }
        Notification::make()->title('Đã lưu người dùng.')->success()->send();
        $this->redirect(Users::getUrl());
    }

    private function client(): IdentityGatewayClient { return app(IdentityGatewayClient::class); }
    private function token(): string { return (string) session('admin.identity_token'); }
}
