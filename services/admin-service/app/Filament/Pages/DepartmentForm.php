<?php
namespace App\Filament\Pages;

use App\Auth\RemoteIdentityUser;
use App\Services\IdentityGatewayClient;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class DepartmentForm extends Page
{
    protected string $view = 'filament.pages.department-form';
    protected static ?string $slug = 'departments/form';
    protected static bool $shouldRegisterNavigation = false;
    public ?string $recordId = null;
    public string $code = '';
    public string $name = '';
    public string $unitType = 'department';
    public int $sortOrder = 0;
    public ?string $parentId = null;
    public ?string $managerUserId = null;
    public array $units = [];
    public array $users = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        return $user instanceof RemoteIdentityUser && $user->can('identity.org_units.manage');
    }
    public function mount(): void
    {
        $this->recordId = request()->query('id');
        $client = app(IdentityGatewayClient::class);
        $this->units = $client->get('org-units', $this->token())['data'] ?? [];
        $this->users = $client->get('users?per_page=100', $this->token())['data'] ?? [];
        if ($this->recordId) {
            $unit = collect($this->units)->firstWhere('id', $this->recordId);
            abort_unless($unit, 404);
            $this->code = $unit['code']; $this->name = $unit['name'];
            $this->unitType = $unit['unit_type']; $this->sortOrder = $unit['sort_order'];
            $this->parentId = $unit['parent_id']; $this->managerUserId = $unit['manager_user_id'];
        }
    }
    public function getTitle(): string { return $this->recordId ? 'Sửa phòng ban' : 'Tạo phòng ban'; }
    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->validate(['code' => ['required', 'string', 'max:64'], 'name' => ['required', 'string', 'max:255'], 'unitType' => ['required', 'string', 'max:32'], 'sortOrder' => ['integer', 'min:0'], 'parentId' => ['nullable', 'uuid'], 'managerUserId' => ['nullable', 'uuid']]);
        $payload = ['code' => trim($this->code), 'name' => trim($this->name), 'unit_type' => trim($this->unitType), 'sort_order' => $this->sortOrder, 'parent_id' => $this->parentId ?: null, 'status' => 'active'];
        $client = app(IdentityGatewayClient::class);
        $response = $client->send($this->recordId ? 'PATCH' : 'POST', $this->recordId ? "org-units/{$this->recordId}" : 'org-units', $this->token(), $payload);
        if ($response === null) { Notification::make()->title('Không thể lưu phòng ban.')->danger()->send(); return; }
        $id = $this->recordId ?? ($response['data']['id'] ?? null);
        if ($id && $this->managerUserId) $client->send('PATCH', "org-units/{$id}/manager", $this->token(), ['manager_user_id' => $this->managerUserId]);
        if ($id && $this->recordId && ! $this->managerUserId) $client->send('PATCH', "org-units/{$id}/manager", $this->token(), ['manager_user_id' => null]);
        Notification::make()->title('Đã lưu phòng ban.')->success()->send();
        $this->redirect(Departments::getUrl());
    }
    private function token(): string { return (string) session('admin.identity_token'); }
}
