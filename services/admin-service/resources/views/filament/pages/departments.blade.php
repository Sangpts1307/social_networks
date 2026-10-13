<x-filament-panels::page>
    @if ($this->canManage())
        <x-filament::section :heading="$editingUnitId ? 'Cập nhật phòng ban' : 'Tạo phòng ban'" description="Thông tin này được quản lý bởi Identity service.">
            <form wire:submit="save" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Mã phòng ban
                    <x-filament::input wire:model="code" placeholder="VD: MARKETING" />
                    @error('code') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Tên phòng ban
                    <x-filament::input wire:model="name" placeholder="VD: Phòng Marketing" />
                    @error('name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Loại đơn vị
                    <x-filament::input wire:model="unitType" placeholder="department" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Thứ tự hiển thị
                    <x-filament::input type="number" wire:model="sortOrder" min="0" />
                </label>
                <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Đơn vị cha
                    <select wire:model="parentId" class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900">
                        <option value="">Không có đơn vị cha</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit['id'] }}">{{ $unit['name'] }}</option>
                        @endforeach
                    </select>
                </label>
                @if ($editingUnitId)
                    <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                        UUID quản lý
                        <x-filament::input wire:model="managerUserId" placeholder="Để trống nếu chưa phân công" />
                    </label>
                @endif
                <div class="flex items-end gap-3">
                    <x-filament::button type="submit" icon="heroicon-o-check">
                        {{ $editingUnitId ? 'Lưu thay đổi' : 'Tạo phòng ban' }}
                    </x-filament::button>
                    @if ($editingUnitId)
                        <x-filament::button type="button" color="gray" wire:click="cancelEdit">Hủy</x-filament::button>
                    @endif
                </div>
            </form>
        </x-filament::section>
    @endif

    <x-filament::section heading="Cơ cấu tổ chức" description="Danh sách đơn vị thuộc organization hiện tại.">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[48rem] divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-white/5 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Mã</th>
                        <th class="px-4 py-3">Tên</th>
                        <th class="px-4 py-3">Loại</th>
                        <th class="px-4 py-3">Đơn vị cha</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3"><span class="sr-only">Thao tác</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($units as $unit)
                        <tr wire:key="unit-{{ $unit['id'] }}" class="text-gray-700 dark:text-gray-200">
                            <td class="px-4 py-4 font-mono text-xs font-semibold">{{ $unit['code'] }}</td>
                            <td class="px-4 py-4 font-medium">{{ $unit['name'] }}</td>
                            <td class="px-4 py-4 text-gray-500">{{ $unit['unit_type'] }}</td>
                            <td class="px-4 py-4 text-gray-500">{{ $this->parentName($unit['parent_id']) }}</td>
                            <td class="px-4 py-4"><x-filament::badge :color="$unit['status'] === 'active' ? 'success' : 'gray'">{{ $unit['status'] === 'active' ? 'Hoạt động' : 'Ngừng hoạt động' }}</x-filament::badge></td>
                            <td class="px-4 py-4 text-right">
                                @if ($this->canManage())
                                    <x-filament::button size="sm" color="gray" wire:click="edit('{{ $unit['id'] }}')">Sửa</x-filament::button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">Chưa có phòng ban.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
