<?php

namespace App\Filament\Pages;

use App\Auth\RemoteIdentityUser;
use App\Services\IdentityGatewayClient;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Departments extends Page
{
    protected string $view = 'filament.pages.departments';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Phòng ban';

    protected static \UnitEnum|string|null $navigationGroup = 'Identity';

    /** @var array<int, array<string, mixed>> */
    public array $units = [];

    public string $code = '';

    public string $name = '';

    public string $unitType = 'department';

    public int $sortOrder = 0;

    public ?string $parentId = null;

    public ?string $managerUserId = null;

    public ?string $editingUnitId = null;

    public function mount(): void
    {
        $this->refreshUnits();
    }

    public static function canAccess(): bool
    {
        return Filament::auth()->user() instanceof RemoteIdentityUser && Filament::auth()->user()->can('identity.org_units.read');
    }

    public function getTitle(): string
    {
        return 'Phòng ban';
    }

    public function parentName(?string $parentId): string
    {
        if ($parentId === null) {
            return '—';
        }

        return (string) (collect($this->units)->firstWhere('id', $parentId)['name'] ?? 'Không xác định');
    }

    public function canManage(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof RemoteIdentityUser && $user->can('identity.org_units.manage');
    }

    public function save(): void
    {
        $this->validate(['code' => ['required', 'string', 'max:64'], 'name' => ['required', 'string', 'max:255'], 'unitType' => ['required', 'string', 'max:32'], 'sortOrder' => ['integer', 'min:0'], 'parentId' => ['nullable', 'uuid']]);
        $payload = [
            'code' => $this->code, 'name' => $this->name, 'unit_type' => $this->unitType, 'sort_order' => $this->sortOrder, 'parent_id' => $this->parentId, 'status' => 'active',
        ];
        $client = app(IdentityGatewayClient::class);
        $response = $this->editingUnitId === null
            ? $client->send('POST', 'org-units', $this->token(), $payload)
            : $client->send('PATCH', "org-units/{$this->editingUnitId}", $this->token(), $payload);
        if ($response === null) {
            Notification::make()->title('Không thể lưu phòng ban.')->danger()->send();

            return;
        }
        if ($this->editingUnitId !== null) {
            $manager = $client->send('PATCH', "org-units/{$this->editingUnitId}/manager", $this->token(), ['manager_user_id' => $this->managerUserId]);
            if ($manager === null) {
                Notification::make()->title('Phòng ban đã lưu, nhưng không cập nhật được quản lý.')->warning()->send();
            }
        }
        $this->cancelEdit();
        $this->refreshUnits();
        Notification::make()->title('Đã lưu phòng ban.')->success()->send();
    }

    public function edit(string $unitId): void
    {
        $unit = collect($this->units)->firstWhere('id', $unitId);
        if (! is_array($unit)) {
            return;
        }

        $this->editingUnitId = $unit['id'];
        $this->code = $unit['code'];
        $this->name = $unit['name'];
        $this->unitType = $unit['unit_type'];
        $this->sortOrder = $unit['sort_order'];
        $this->parentId = $unit['parent_id'];
        $this->managerUserId = $unit['manager_user_id'];
    }

    public function cancelEdit(): void
    {
        $this->reset('code', 'name', 'parentId', 'managerUserId', 'editingUnitId');
        $this->unitType = 'department';
        $this->sortOrder = 0;
    }

    public function refreshUnits(): void
    {
        $response = app(IdentityGatewayClient::class)->get('org-units', $this->token());
        $this->units = $response['data'] ?? [];
    }

    private function token(): string
    {
        return (string) session('admin.identity_token');
    }
}
