<?php

namespace App\Filament\Pages;

use App\Auth\RemoteIdentityUser;
use Filament\Facades\Filament;
use Filament\Pages\Page;

abstract class ModulePlaceholder extends Page
{
    protected string $view = 'filament.pages.module-placeholder';

    public string $search = '';

    public string $status = 'all';

    public string $scope = 'all';

    protected static string $moduleDescription;

    protected static string $moduleOwner;

    /** @var array<int, string> */
    protected static array $moduleCapabilities = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof RemoteIdentityUser && $user->can('identity.roles.manage');
    }

    public function getTitle(): string
    {
        return static::$navigationLabel ?? static::class;
    }

    public function description(): string
    {
        return static::$moduleDescription;
    }

    public function owner(): string
    {
        return static::$moduleOwner;
    }

    /** @return array<int, string> */
    public function capabilities(): array
    {
        return static::$moduleCapabilities;
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'scope');
    }
}
