<x-filament-panels::page>
    <div class="flex justify-end gap-2">
        @if ($this->canManage() && count($selectedIds) > 0)<x-filament::button color="danger" wire:click="deleteSelected" wire:confirm="Xoá các phòng ban đã chọn?">Xoá đã chọn ({{ count($selectedIds) }})</x-filament::button>@endif
        @if ($this->canManage())<x-filament::button tag="a" :href="\App\Filament\Pages\DepartmentForm::getUrl()" icon="heroicon-o-plus">Tạo phòng ban</x-filament::button>@endif
    </div>
    <x-filament::section class="admin-table-card">
        <div class="admin-table-toolbar flex flex-wrap items-center justify-end gap-2 border-b border-gray-200 dark:border-white/10">
            <x-filament::input.wrapper class="w-full sm:max-w-xs" prefix-icon="heroicon-o-magnifying-glass"><x-filament::input wire:model.live.debounce.300ms="search" placeholder="Tìm tên hoặc mã phòng ban" aria-label="Tìm kiếm phòng ban" /></x-filament::input.wrapper>
            <div class="relative"><x-filament::icon-button icon="heroicon-o-funnel" :color="$openMenu === 'filters' ? 'primary' : 'gray'" wire:click="toggleMenu('filters')" tooltip="Bộ lọc" aria-label="Bộ lọc" />
                @if ($openMenu === 'filters')<div class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-gray-900">
                    <div class="mb-4 flex justify-between"><strong>Bộ lọc</strong><button type="button" wire:click="resetFilters" class="text-sm text-danger-600">Đặt lại</button></div>
                    <label class="mb-4 grid gap-2 text-sm">Loại đơn vị<select wire:model.live="typeFilter" class="rounded-lg border-gray-300 bg-white dark:bg-gray-900"><option value="">Tất cả</option>@foreach (collect($units)->pluck('unit_type')->unique() as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select></label>
                    <label class="grid gap-2 text-sm">Trạng thái<select wire:model.live="statusFilter" class="rounded-lg border-gray-300 bg-white dark:bg-gray-900"><option value="">Tất cả</option><option value="active">Hoạt động</option><option value="inactive">Ngừng hoạt động</option></select></label>
                </div>@endif
            </div>
            <div class="relative"><x-filament::icon-button icon="heroicon-o-view-columns" :color="$openMenu === 'columns' ? 'primary' : 'gray'" wire:click="toggleMenu('columns')" tooltip="Chọn cột" aria-label="Chọn cột" />
                @if ($openMenu === 'columns')<div class="absolute right-0 z-20 mt-2 w-56 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-gray-900">
                    <div class="mb-3 flex justify-between"><strong>Cột</strong><button type="button" wire:click="resetColumns" class="text-sm text-danger-600">Đặt lại</button></div>
                    @foreach (['code' => 'Mã', 'name' => 'Tên', 'type' => 'Loại', 'parent' => 'Đơn vị cha', 'status' => 'Trạng thái', 'actions' => 'Thao tác'] as $key => $label)<label class="flex items-center gap-2 py-2 text-sm"><input type="checkbox" wire:model.live="visibleColumns.{{ $key }}" @disabled($key === 'name') class="rounded border-gray-300">{{ $label }}</label>@endforeach
                </div>@endif
            </div>
            <x-filament::icon-button icon="heroicon-o-arrow-path" color="gray" wire:click="refreshUnits" tooltip="Làm mới" aria-label="Làm mới danh sách" />
        </div>
        <div class="overflow-x-auto"><table class="w-full min-w-[48rem] divide-y divide-gray-200 text-left text-sm dark:divide-white/10"><thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-white/5 dark:text-gray-300"><tr>
            <th class="w-10 px-4 py-3"><span class="sr-only">Chọn</span></th>
            @if ($visibleColumns['code'])<th class="px-4 py-3">Mã</th>@endif
            <th class="px-4 py-3">Tên</th>
            @if ($visibleColumns['type'])<th class="px-4 py-3">Loại</th>@endif
            @if ($visibleColumns['parent'])<th class="px-4 py-3">Đơn vị cha</th>@endif
            @if ($visibleColumns['status'])<th class="px-4 py-3">Trạng thái</th>@endif
            @if ($visibleColumns['actions'])<th class="px-4 py-3 text-right">Thao tác</th>@endif
        </tr></thead><tbody class="divide-y divide-gray-200 dark:divide-white/10">
            @forelse ($this->filteredUnits() as $unit)<tr wire:key="unit-{{ $unit['id'] }}" class="text-gray-700 dark:text-gray-200">
                <td class="px-4 py-4">@if ($this->canManage())<input type="checkbox" value="{{ $unit['id'] }}" wire:model.live="selectedIds" aria-label="Chọn {{ $unit['name'] }}" class="rounded border-gray-300">@endif</td>
                @if ($visibleColumns['code'])<td class="px-4 py-4 font-mono text-xs">{{ $unit['code'] }}</td>@endif
                <td class="px-4 py-4 font-medium">{{ $unit['name'] }}</td>
                @if ($visibleColumns['type'])<td class="px-4 py-4">{{ $unit['unit_type'] }}</td>@endif
                @if ($visibleColumns['parent'])<td class="px-4 py-4">{{ $this->parentName($unit['parent_id']) }}</td>@endif
                @if ($visibleColumns['status'])<td class="px-4 py-4"><x-filament::badge :color="$unit['status'] === 'active' ? 'success' : 'gray'">{{ $unit['status'] === 'active' ? 'Hoạt động' : 'Ngừng hoạt động' }}</x-filament::badge></td>@endif
                @if ($visibleColumns['actions'])<td class="px-4 py-4 text-right">@if ($this->canManage())<x-filament::button size="sm" color="gray" tag="a" :href="\App\Filament\Pages\DepartmentForm::getUrl(['id' => $unit['id']])">Sửa</x-filament::button>@endif</td>@endif
            </tr>@empty<tr><td colspan="{{ 2 + (int) $visibleColumns['code'] + (int) $visibleColumns['type'] + (int) $visibleColumns['parent'] + (int) $visibleColumns['status'] + (int) $visibleColumns['actions'] }}" class="px-4 py-10 text-center text-gray-500">Không có phòng ban phù hợp.</td></tr>@endforelse
        </tbody></table></div>
    </x-filament::section>
</x-filament-panels::page>
