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
    public string $gender = '';
    public string $birthDate = '';
    public string $employeeCode = '';
    public string $orgUnitId = '';
    public string $password = '';
    public array $orgUnits = [];
    public array $userRoles = [];
    public array $availableRoles = [];
    public string $selectedRoleId = '';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        return $user instanceof RemoteIdentityUser && ($user->can('identity.users.read') || $user->can('identity.users.create') || $user->can('identity.users.update') || $user->can('identity.roles.manage'));
    }

    public function canEdit(): bool
    {
        $user = Filament::auth()->user();
        return $user instanceof RemoteIdentityUser && $user->can($this->recordId ? 'identity.users.update' : 'identity.users.create');
    }

    public function canManageRoles(): bool
    {
        $user = Filament::auth()->user();
        return $user instanceof RemoteIdentityUser && $user->can('identity.roles.manage');
    }

    public function mount(): void
    {
        $this->recordId = request()->query('id');
        $this->orgUnits = $this->client()->get('org-units', $this->token())['data'] ?? [];
        if ($this->recordId) {
            abort_unless(
                Filament::auth()->user()->can('identity.users.read')
                || Filament::auth()->user()->can('identity.users.update')
                || Filament::auth()->user()->can('identity.roles.manage'),
                403
            );
            $this->loadUser();
        } else {
            abort_unless(Filament::auth()->user()->can('identity.users.create'), 403);
        }
        if ($this->canManageRoles()) {
            $this->availableRoles = $this->client()->get('roles', $this->token())['data'] ?? [];
        }
    }

    private function loadUser(): void
    {
        $user = $this->client()->get("users/{$this->recordId}", $this->token())['data'] ?? null;
        abort_unless($user, 404);
        $this->displayName = $user['display_name'];
        $this->gender = $user['profile']['gender'] ?? '';
        $this->birthDate = $user['profile']['birth_date'] ?? '';
        $this->email = $user['email'];
        $this->employeeCode = $user['employee_code'] ?? '';
        $this->orgUnitId = $user['primary_org_unit']['id'] ?? '';
        $this->userRoles = $user['roles'] ?? [];
        $this->selectedRoleId = $this->userRoles[0]['id'] ?? '';
    }

    public function assignRole(): void
    {
        abort_unless($this->recordId && $this->canManageRoles(), 403);
        if ($this->selectedRoleId === '') {
            Notification::make()->title('Hãy chọn role trước khi gán.')->danger()->send();
            return;
        }

        $response = $this->client()->send('POST', "users/{$this->recordId}/role-assignments", $this->token(), [
            'role_id' => $this->selectedRoleId,
            'scope_type' => 'organization',
        ]);

        if ($response === null) {
            Notification::make()->title('Không thể gán role cho người dùng này.')->danger()->send();
            return;
        }

        $this->selectedRoleId = '';
        $this->loadUser();
        Notification::make()->title('Đã gán role cho người dùng.')->success()->send();
    }

    public function removeRole(string $assignmentId): void
    {
        abort_unless($this->recordId && $this->canManageRoles(), 403);
        $response = $this->client()->send('DELETE', "users/{$this->recordId}/role-assignments/{$assignmentId}", $this->token());
        if ($response === null) {
            Notification::make()->title('Không thể gỡ role khỏi người dùng.')->danger()->send();
            return;
        }

        $this->loadUser();
        Notification::make()->title('Đã gỡ role khỏi người dùng.')->success()->send();
    }

    public function getTitle(): string
    {
        return $this->recordId ? ($this->canEdit() ? 'Sửa người dùng' : 'Chi tiết người dùng') : 'Tạo người dùng';
    }

    public function save(): void
    {
        abort_unless(Filament::auth()->user()->can($this->recordId ? 'identity.users.update' : 'identity.users.create'), 403);
        $rules = ['gender' => 'nullable|in:male,female,other', 'birthDate' => 'nullable|date_format:Y-m-d|before_or_equal:today', 'displayName' => ['required', 'string', 'max:255'], 'orgUnitId' => ['required', 'uuid']];
        if (! $this->recordId) {
            $rules += ['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string', 'max:72']];
        } else {
            $rules['password'] = ['nullable', 'string', 'max:72'];
            $rules['employeeCode'] = ['nullable', 'string', 'max:64'];
        }
        if ($this->canManageRoles()) {
            $rules['selectedRoleId'] = ['required', 'uuid', \Illuminate\Validation\Rule::in(array_column($this->availableRoles, 'id'))];
        }
        $this->validate($rules);
        $payload = ['gender' => $this->gender ?: null, 'birth_date' => $this->birthDate ?: null, 'display_name' => trim($this->displayName), 'primary_org_unit_id' => $this->orgUnitId];
        if (! $this->recordId) {
            $payload += ['email' => trim($this->email), 'password' => $this->password];
        } elseif ($this->password !== '') {
            $payload['password'] = $this->password;
        }
        if ($this->recordId) $payload['employee_code'] = trim($this->employeeCode) ?: null;
        if ($this->canManageRoles()) {
            $payload['role_id'] = $this->selectedRoleId;
            if ($this->recordId && isset($this->userRoles[0]['assignment_id'])) {
                $payload['replace_role_assignment_id'] = $this->userRoles[0]['assignment_id'];
            }
        }
        $result = $this->client()->sendWithResponse($this->recordId ? 'PATCH' : 'POST', $this->recordId ? "users/{$this->recordId}" : 'users', $this->token(), $payload);
        if ($result === null || $result['status'] >= 400) {
            $errors = $result['body']['errors'] ?? [];
            $message = match (true) {
                $result === null => 'Không thể kết nối dịch vụ người dùng. Vui lòng thử lại.',
                isset($errors['email']) || ($result['body']['message'] ?? '') === 'Email is unavailable.' => 'Email này đã có tài khoản. Hãy dùng email khác hoặc mở tài khoản hiện có trong danh sách.',
                isset($errors['employee_code']) => 'Mã nhân viên đã được sử dụng hoặc không hợp lệ.',
                $result['status'] === 403 => 'Bạn không có quyền gán Role đã chọn.',
                isset($errors['role_id']) => 'Role đã chọn không hợp lệ. Hãy tải lại trang và chọn lại.',
                $result['status'] === 422 => 'Dữ liệu chưa hợp lệ: '.($result['body']['message'] ?? 'Hãy kiểm tra các trường đã nhập.'),
                default => 'Không thể lưu người dùng. Vui lòng thử lại.',
            };
            Notification::make()->title($message)->danger()->send();
            return;
        }
        Notification::make()->title('Đã lưu người dùng.')->success()->send();
        $this->redirect(Users::getUrl());
    }

    private function client(): IdentityGatewayClient { return app(IdentityGatewayClient::class); }
    private function token(): string { return (string) session('admin.identity_token'); }
}
