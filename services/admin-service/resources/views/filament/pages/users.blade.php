<x-filament-panels::page>
    <x-filament::section class="admin-table-card">
        <div class="admin-table-toolbar flex flex-wrap items-center justify-end gap-2 border-b border-gray-200 dark:border-white/10">
            <x-filament::input.wrapper class="w-full sm:max-w-xs" prefix-icon="heroicon-o-magnifying-glass">
                <x-filament::input wire:model.live.debounce.300ms="search" placeholder="Tìm tên, email, mã nhân viên" aria-label="Tìm kiếm người dùng" />
            </x-filament::input.wrapper>
            <div class="relative" x-data x-on:click.outside="$wire.openMenu === 'filters' && $wire.closeMenu()" x-on:keydown.escape.window="$wire.closeMenu()">
                <x-filament::icon-button icon="heroicon-o-funnel" :color="$openMenu === 'filters' || $statusFilter || $roleFilter ? 'primary' : 'gray'" wire:click="toggleMenu('filters')" tooltip="Bộ lọc" aria-label="Bộ lọc" />@if ($statusFilter || $roleFilter)<span class="absolute -right-1 -top-2 rounded-full bg-primary-100 px-1 text-xs text-primary-700">{{ (int) (bool) $statusFilter + (int) (bool) $roleFilter }}</span>@endif
                @if ($openMenu === 'filters')
                    <div class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-gray-900">
                        <div class="mb-4 flex items-center justify-between"><strong>Bộ lọc</strong><button type="button" wire:click="resetFilters" class="text-sm text-danger-600">Đặt lại</button></div>
                        <label class="mb-4 block text-sm">
                            <span class="mb-1.5 block font-medium">Role</span>
                            <select wire:model.live="roleFilter" class="block w-full min-w-0 max-w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900">
                                <option value="">Tất cả</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block text-sm">
                            <span class="mb-1.5 block font-medium">Trạng thái</span>
                            <select wire:model.live="statusFilter" class="block w-full min-w-0 max-w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900">
                                <option value="">Tất cả</option>
                                <option value="active">Hoạt động</option>
                                <option value="suspended">Tạm dừng</option>
                                <option value="inactive">Chưa hoạt động</option>
                            </select>
                        </label>
                    </div>
                @endif
            </div>
            <div class="relative" x-data x-on:click.outside="$wire.openMenu === 'columns' && $wire.closeMenu()" x-on:keydown.escape.window="$wire.closeMenu()">
                <x-filament::icon-button icon="heroicon-o-view-columns" :color="$openMenu === 'columns' ? 'primary' : 'gray'" wire:click="toggleMenu('columns')" tooltip="Chọn cột" aria-label="Chọn cột" />
                @if ($openMenu === 'columns')
                    <div class="absolute right-0 z-20 mt-2 w-56 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-gray-900">
                        <div class="mb-3 flex items-center justify-between"><strong>Cột</strong><button type="button" wire:click="resetColumns" class="text-sm text-danger-600">Đặt lại</button></div>
                        @foreach (['user' => 'Người dùng', 'roles' => 'Role', 'status' => 'Trạng thái', 'actions' => 'Thao tác'] as $key => $label)
                            <label class="flex items-center gap-2 py-2 text-sm"><input type="checkbox" wire:model.live="visibleColumns.{{ $key }}" @disabled($key === 'user') class="rounded border-gray-300">{{ $label }}</label>
                        @endforeach
                    </div>
                @endif
            </div>
            <x-filament::icon-button icon="heroicon-o-arrow-path" color="gray" wire:click="refreshUsers" tooltip="Làm mới" aria-label="Làm mới danh sách" />
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[62rem] divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-white/5 dark:text-gray-300">
                    <tr>
                        <th class="w-10 px-4 py-3">@if ($this->canUpdateUsers())<input type="checkbox" wire:click="toggleSelectPage" @checked(count($users) > 0 && count(array_intersect($selectedIds, array_column($users, 'id'))) === count($users)) aria-label="Chọn tất cả trên trang" class="rounded border-gray-300">@endif</th>
                        <th class="px-4 py-3">Người dùng</th>
                        @if ($visibleColumns['roles'])<th class="px-4 py-3">Role</th>@endif
                        @if ($visibleColumns['status'])<th class="px-4 py-3">Trạng thái</th>@endif
                        @if ($visibleColumns['actions'])<th class="px-4 py-3 text-right">Thao tác</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user['id'] }}" class="align-top text-gray-700 dark:text-gray-200">
                            <td class="px-4 py-4">@if ($this->canUpdateUsers())<input type="checkbox" value="{{ $user['id'] }}" wire:model.live="selectedIds" aria-label="Chọn {{ $user['display_name'] }}" class="rounded border-gray-300">@endif</td>
                            <td class="px-4 py-4">
                                <a href="{{ \App\Filament\Pages\UserForm::getUrl(['id' => $user['id']]) }}" class="font-medium text-gray-950 hover:text-primary-600 hover:underline dark:text-white dark:hover:text-primary-400">{{ $user['display_name'] }}</a>
                                <div class="mt-0.5 text-sm text-gray-500">{{ $user['email'] }}</div>
                                @if (!empty($user['employee_code']))
                                    <div class="mt-0.5 text-xs text-gray-400">{{ $user['employee_code'] }}</div>
                                @endif
                            </td>
                            @if ($visibleColumns['roles'])<td class="px-4 py-4">
                                <div class="flex max-w-sm flex-wrap gap-1.5">
                                    @forelse ($user['roles'] as $role)
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">
                                            {{ $role['name'] }}
                                        </span>
                                    @empty
                                        <span class="text-gray-500">Chưa gán role</span>
                                    @endforelse
                                </div>
                            </td>@endif
                            @if ($visibleColumns['status'])<td class="px-4 py-4">
                                <x-filament::badge :color="!empty($user['locked']) ? 'danger' : ($user['status'] === 'active' ? 'success' : 'warning')">
                                    {{ !empty($user['locked']) ? 'Đã khóa' : ($user['status'] === 'active' ? 'Hoạt động' : 'Tạm dừng') }}
                                </x-filament::badge>
                            </td>@endif
                            @if ($visibleColumns['actions'])<td class="px-4 py-4 text-right">
                                <div class="flex flex-col items-end gap-1.5">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        @if ($this->canUnlockUsers() && !empty($user['locked']))
                                            <x-filament::button size="sm" color="success" wire:click="unlock('{{ $user['id'] }}')" wire:confirm="Mở khóa tài khoản này?">Mở khóa</x-filament::button>
                                        @endif
                                        @if ($this->canUpdateUsers())
                                            <x-filament::button size="sm" color="gray" tag="a" :href="\App\Filament\Pages\UserForm::getUrl(['id' => $user['id']])">Sửa</x-filament::button>
                                            @if ($user['status'] === 'active')
                                                <x-filament::button size="sm" color="warning" wire:click="setStatus('{{ $user['id'] }}', 'suspended')" wire:confirm="Tạm dừng tài khoản này?">Tạm dừng</x-filament::button>
                                            @else
                                                <x-filament::button size="sm" color="success" wire:click="setStatus('{{ $user['id'] }}', 'active')" wire:confirm="Kích hoạt tài khoản này?">Kích hoạt</x-filament::button>
                                            @endif
                                        @else
                                            <x-filament::button size="sm" color="gray" tag="a" :href="\App\Filament\Pages\UserForm::getUrl(['id' => $user['id']])">Chi tiết</x-filament::button>
                                        @endif
                                    </div>
                                    @if ($this->canUpdateUsers())
                                        <div class="flex justify-end">
                                            <x-filament::button size="sm" color="gray" wire:click="forcePasswordChange('{{ $user['id'] }}')" wire:confirm="Buộc người dùng đổi mật khẩu ở lần đăng nhập sau?">Buộc đổi mật khẩu</x-filament::button>
                                        </div>
                                    @endif
                                </div>
                            </td>@endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ 2 + (int) $visibleColumns['roles'] + (int) $visibleColumns['status'] + (int) $visibleColumns['actions'] }}" class="px-4 py-10 text-center text-gray-500">Không có người dùng phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500">
            <span>{{ $totalUsers }} người dùng · Trang {{ $pageNumber }}/{{ $lastPage }}</span>
            <div class="flex gap-2">
                <x-filament::button size="sm" color="gray" wire:click="goToPage({{ $pageNumber - 1 }})" :disabled="$pageNumber <= 1">Trước</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="goToPage({{ $pageNumber + 1 }})" :disabled="$pageNumber >= $lastPage">Sau</x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
