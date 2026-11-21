<x-filament-panels::page>
    @if ($showRoleForm && $this->canManage())
        <x-filament::section
            :heading="$editingRoleId ? 'Sửa thông tin vai trò' : 'Tạo vai trò mới'"
            description="Sau khi tạo vai trò, bạn có thể chọn và phân quyền chi tiết ở bảng Ma trận permission bên dưới."
        >
            <form wire:submit="saveRole" class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Tên vai trò
                    <x-filament::input wire:model.blur="roleName" placeholder="Ví dụ: Trưởng phòng Tài chính" required />
                    @error('roleName')
                        <span class="text-xs text-danger-600">{{ $message }}</span>
                    @enderror
                </label>

                <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    Mã định danh (tự động hoặc nhập tay)
                    <x-filament::input wire:model="roleCode" placeholder="Ví dụ: finance_manager" required />
                    @error('roleCode')
                        <span class="text-xs text-danger-600">{{ $message }}</span>
                    @enderror
                </label>

                <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white sm:col-span-2">
                    Mô tả
                    <textarea wire:model="roleDescription" rows="2" placeholder="Mô tả phạm vi nhiệm vụ của vai trò..."></textarea>
                    @error('roleDescription')
                        <span class="text-xs text-danger-600">{{ $message }}</span>
                    @enderror
                </label>

                <div class="flex flex-wrap gap-3 sm:col-span-2">
                    <x-filament::button type="submit" icon="heroicon-o-check">
                        {{ $editingRoleId ? 'Cập nhật vai trò' : 'Tạo vai trò' }}
                    </x-filament::button>
                    <x-filament::button type="button" color="gray" wire:click="cancelRoleForm">
                        Huỷ
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <x-filament::section heading="Vai trò">
            <div class="grid gap-2">
                @forelse ($roles as $role)
                    <button
                        type="button"
                        wire:key="role-{{ $role['id'] }}"
                        wire:click="selectRole('{{ $role['id'] }}')"
                        @class([
                            'flex w-full items-center rounded-lg px-4 py-3 text-left text-sm font-medium transition',
                            'bg-primary-500 text-white shadow-sm' => $selectedRoleId === $role['id'],
                            'bg-gray-50 text-gray-950 hover:bg-gray-100 dark:bg-white/5 dark:text-white dark:hover:bg-white/10' => $selectedRoleId !== $role['id'],
                        ])
                    >
                        <span>{{ $role['name'] }}</span>
                    </button>
                @empty
                    <p class="text-sm text-gray-500">Chưa có role.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section class="xl:col-span-2" heading="Ma trận permission" description="Chọn role để xem và cập nhật permission tương ứng.">
            @php($currentRole = $this->selectedRole())
            @if ($selectedRoleId && $currentRole)
                <div class="mb-5 flex flex-wrap items-start justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $currentRole['name'] }}</h3>
                            @if ($currentRole['is_system'] ?? false)
                                <x-filament::badge color="gray" size="sm">Hệ thống</x-filament::badge>
                            @endif
                        </div>
                        @if (! empty($currentRole['description']))
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $currentRole['description'] }}</p>
                        @endif
                    </div>

                    @if ($this->canManage() && ! ($currentRole['is_system'] ?? false))
                        <div class="flex items-center gap-2">
                            <x-filament::button size="sm" color="gray" icon="heroicon-o-pencil-square" wire:click="openEditRole('{{ $currentRole['id'] }}')">
                                Sửa vai trò
                            </x-filament::button>
                            <x-filament::button size="sm" color="danger" icon="heroicon-o-trash" wire:click="deleteRole('{{ $currentRole['id'] }}')" wire:confirm="Xoá vai trò '{{ $currentRole['name'] }}'?">
                                Xoá
                            </x-filament::button>
                        </div>
                    @endif
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($permissions as $permission)
                        <label class="flex min-w-0 cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 transition hover:border-primary-400 dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-500">
                            <input
                                type="checkbox"
                                value="{{ $permission['id'] }}"
                                wire:model="permissionIds"
                                @disabled(! $this->canManage())
                                class="mt-0.5 size-4 shrink-0 rounded border-gray-300 text-primary-600 focus:ring-primary-600 dark:border-gray-600"
                            />
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-950 dark:text-white">{{ $permission['name'] }}</span>
                                <span class="mt-1 block break-all font-mono text-xs text-gray-500">{{ $permission['code'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @if ($this->canManage())
                    <div class="mt-6 border-t border-gray-200 pt-4 dark:border-white/10">
                        <x-filament::button wire:click="savePermissions" wire:confirm="Lưu ma trận permission của role này?" icon="heroicon-o-check">
                            Lưu permission
                        </x-filament::button>
                    </div>
                @endif
            @else
                <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-white/15">
                    <p class="text-sm font-medium text-gray-950 dark:text-white">Chưa chọn role</p>
                    <p class="mt-1 text-sm text-gray-500">Chọn một role ở cột bên trái để xem permission.</p>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
