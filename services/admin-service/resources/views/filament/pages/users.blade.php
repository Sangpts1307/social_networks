<x-filament-panels::page>
    <div class="flex justify-end gap-2">
        @if ($this->canUpdateUsers() && count($selectedIds) > 0)
            <x-filament::button color="danger" wire:click="deleteSelected" wire:confirm="Xoá các người dùng đã chọn?">Xoá đã chọn ({{ count($selectedIds) }})</x-filament::button>
        @endif
        @if ($this->canCreateUsers())<x-filament::button tag="a" :href="\App\Filament\Pages\UserForm::getUrl()" icon="heroicon-o-plus">Tạo người dùng</x-filament::button>@endif
    </div>
    <x-filament::section class="admin-table-card">
        <div class="admin-table-toolbar flex flex-wrap items-center justify-end gap-2 border-b border-gray-200 dark:border-white/10">
            <x-filament::input.wrapper class="w-full sm:max-w-xs" prefix-icon="heroicon-o-magnifying-glass">
                <x-filament::input wire:model.live.debounce.300ms="search" placeholder="Tìm tên, email, mã nhân viên" aria-label="Tìm kiếm người dùng" />
            </x-filament::input.wrapper>
            <div class="relative">
                <x-filament::icon-button icon="heroicon-o-funnel" :color="$openMenu === 'filters' ? 'primary' : 'gray'" wire:click="toggleMenu('filters')" tooltip="Bộ lọc" aria-label="Bộ lọc" />@if ($statusFilter || $roleFilter)<span class="absolute -right-1 -top-2 rounded-full bg-primary-100 px-1 text-xs text-primary-700">{{ (int) (bool) $statusFilter + (int) (bool) $roleFilter }}</span>@endif
                @if ($openMenu === 'filters')
                    <div class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-gray-900">
                        <div class="mb-4 flex items-center justify-between"><strong>Bộ lọc</strong><button type="button" wire:click="resetFilters" class="text-sm text-danger-600">Đặt lại</button></div>
                        <label class="mb-4 grid gap-2 text-sm">Role<select wire:model.live="roleFilter" class="rounded-lg border-gray-300 bg-white dark:bg-gray-900"><option value="">Tất cả</option>@foreach ($roles as $role)<option value="{{ $role['id'] }}">{{ $role['name'] }}</option>@endforeach</select></label>
                        <label class="grid gap-2 text-sm">Trạng thái<select wire:model.live="statusFilter" class="rounded-lg border-gray-300 bg-white dark:bg-gray-900"><option value="">Tất cả</option><option value="active">Hoạt động</option><option value="suspended">Tạm dừng</option><option value="inactive">Chưa hoạt động</option></select></label>
                    </div>
                @endif
            </div>
            <div class="relative">
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
                                <div class="font-medium text-gray-950 dark:text-white">{{ $user['display_name'] }}</div>
                                <div class="mt-1 text-sm text-gray-500">{{ $user['email'] }} · {{ $user['employee_code'] }}</div>
                            </td>
                            @if ($visibleColumns['roles'])<td class="px-4 py-4">
                                <div class="flex max-w-sm flex-wrap gap-1.5">
                                    @forelse ($user['roles'] as $role)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">
                                            {{ $role['name'] }} · {{ ($role['scope_type'] ?? 'organization') === 'self' ? 'Bản thân' : (($role['scope_type'] ?? 'organization') === 'org_unit' ? (collect($orgUnits)->firstWhere('id', $role['org_unit_id'])['name'] ?? 'Phòng ban') : 'Toàn tổ chức') }}
                                            {{ ($role['include_descendants'] ?? false) ? '+ đơn vị con' : '' }}
                                            {{ ($role['expires_at'] ?? null) ? '· Hết hạn: '.$role['expires_at'] : '' }}
                                            @if ($this->canManageRoles() && $role['assignment_id'])
                                                <button type="button" class="text-danger-600 hover:text-danger-700" wire:click="removeRole('{{ $user['id'] }}', '{{ $role['assignment_id'] }}')" wire:confirm="Gỡ role này khỏi người dùng?" aria-label="Gỡ {{ $role['name'] }} · {{ ($role['scope_type'] ?? 'organization') === 'self' ? 'Bản thân' : (($role['scope_type'] ?? 'organization') === 'org_unit' ? (collect($orgUnits)->firstWhere('id', $role['org_unit_id'])['name'] ?? 'Phòng ban') : 'Toàn tổ chức') }}
                                            {{ ($role['include_descendants'] ?? false) ? '+ đơn vị con' : '' }}
                                            {{ ($role['expires_at'] ?? null) ? '· Hết hạn: '.$role['expires_at'] : '' }}">×</button>
                                            @endif
                                        </span>
                                    @empty
                                        <span class="text-gray-500">Chưa gán role</span>
                                    @endforelse
                                </div>
                                @if ($this->canManageRoles())
                                    <details class="mt-3 rounded-lg border border-gray-200 p-2 dark:border-white/10"><summary class="cursor-pointer text-xs font-medium text-primary-600">Quản lý role</summary><div class="mt-3 flex flex-wrap items-center gap-2">
                                        <select wire:model="roleSelections.{{ $user['id'] }}" class="min-w-0 rounded-lg border-gray-300 bg-white px-3 py-2 text-xs shadow-sm outline-none transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900">
                                            <option value="">Gán role…</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <select aria-label="Phạm vi quyền" wire:model.live="scopeSelections.{{ $user['id'] }}" class="rounded-lg border-gray-300 text-xs dark:bg-gray-900">
                                            <option value="organization">Toàn tổ chức</option>
                                            <option value="org_unit">Phòng ban</option>
                                            <option value="self">Bản thân</option>
                                        </select>
                                        @if (($scopeSelections[$user['id']] ?? 'organization') === 'org_unit')
                                            <select aria-label="Phòng ban" wire:model="unitSelections.{{ $user['id'] }}" class="rounded-lg border-gray-300 text-xs dark:bg-gray-900">
                                                <option value="">Chọn phòng ban</option>
                                                @foreach ($orgUnits as $unit)
                                                    <option value="{{ $unit['id'] }}">{{ $unit['name'] }}</option>
                                                @endforeach
                                            </select>
                                            <label class="text-xs"><input type="checkbox" wire:model="descendantSelections.{{ $user['id'] }}"> Gồm đơn vị con</label>
                                        @endif
                                        <input type="datetime-local" aria-label="Ngày hết hạn quyền (giờ Việt Nam)" title="Ngày hết hạn (giờ Việt Nam); để trống nếu không thời hạn" wire:model="expirySelections.{{ $user['id'] }}" class="rounded-lg border-gray-300 text-xs dark:bg-gray-900">
                                        <x-filament::button size="sm" color="gray" wire:click="assignRole('{{ $user['id'] }}')">Gán</x-filament::button>
                                    </div></details>
                                @endif
                            </td>@endif
                            @if ($visibleColumns['status'])<td class="px-4 py-4">
                                <x-filament::badge :color="$user['locked'] ? 'danger' : ($user['status'] === 'active' ? 'success' : 'warning')">
                                    {{ $user['locked'] ? 'Đã khóa' : ($user['status'] === 'active' ? 'Hoạt động' : 'Tạm dừng') }}
                                </x-filament::badge>
                            </td>@endif
                            @if ($visibleColumns['actions'])<td class="px-4 py-4 text-right">
                                <div class="flex max-w-xs flex-wrap justify-end gap-2">
                                    <x-filament::button size="sm" color="gray" wire:click="showEffectivePermissions('{{ $user['id'] }}')">Quyền hiệu lực</x-filament::button>
                                    @if ($this->canUnlockUsers() && $user['locked'])
                                        <x-filament::button size="sm" color="success" wire:click="unlock('{{ $user['id'] }}')" wire:confirm="Mở khóa tài khoản này?">Mở khóa</x-filament::button>
                                    @endif
                                    @if ($this->canUpdateUsers())
                                        <x-filament::button size="sm" color="gray" tag="a" :href="\App\Filament\Pages\UserForm::getUrl(['id' => $user['id']])">Sửa</x-filament::button>
                                        @if ($user['status'] === 'active')
                                            <x-filament::button size="sm" color="warning" wire:click="setStatus('{{ $user['id'] }}', 'suspended')" wire:confirm="Tạm dừng tài khoản này?">Tạm dừng</x-filament::button>
                                        @else
                                            <x-filament::button size="sm" color="success" wire:click="setStatus('{{ $user['id'] }}', 'active')" wire:confirm="Kích hoạt tài khoản này?">Kích hoạt</x-filament::button>
                                        @endif
                                        <x-filament::button size="sm" color="gray" wire:click="forcePasswordChange('{{ $user['id'] }}')" wire:confirm="Buộc người dùng đổi mật khẩu ở lần đăng nhập sau?">Buộc đổi mật khẩu</x-filament::button>
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
    @if ($effectiveAuthorization !== null)
        <x-filament::section :heading="'Quyền hiệu lực · '.$effectiveUserName">
            <p class="mb-3 text-sm">Mỗi dòng là một lần cấp role còn hạn. Quyền chỉ áp dụng trong phạm vi của chính dòng đó; luật tài liệu có thể giới hạn thêm.</p>
            @forelse ($effectiveAuthorization['grants'] ?? [] as $grant)
                <div class="mb-3 rounded-lg border p-3 text-sm">
                    <strong>{{ $grant['role_code'] }}</strong> · {{ ['organization' => 'Toàn tổ chức', 'org_unit' => 'Phòng ban', 'self' => 'Bản thân'][$grant['scope_type']] ?? $grant['scope_type'] }}
                    {{ $grant['org_unit_id'] ? (collect($orgUnits)->firstWhere('id', $grant['org_unit_id'])['name'] ?? $grant['org_unit_id']) : '' }}
                    {{ $grant['include_descendants'] ? '· Gồm đơn vị con' : '' }}
                    <div>{{ $grant['expires_at'] ? 'Hết hạn: '.$grant['expires_at'] : 'Không thời hạn' }}</div>
                    <div class="mt-2">{{ implode(', ', $grant['permissions']) }}</div>
                </div>
            @empty
                <p>Không có role còn hiệu lực.</p>
            @endforelse
        </x-filament::section>
    @endif
</x-filament-panels::page>
