<x-filament-panels::page>
    <x-filament::section heading="Tìm kiếm người dùng" description="Dữ liệu người dùng và role được đọc từ Identity service.">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <x-filament::input.wrapper class="w-full sm:max-w-md">
                <x-filament::input wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên, email hoặc mã nhân viên" />
            </x-filament::input.wrapper>
            <x-filament::button wire:click="refreshUsers" color="gray" icon="heroicon-o-arrow-path">Làm mới</x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section heading="Danh sách người dùng">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[62rem] divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-white/5 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Người dùng</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user['id'] }}" class="align-top text-gray-700 dark:text-gray-200">
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-950 dark:text-white">{{ $user['display_name'] }}</div>
                                <div class="mt-1 text-sm text-gray-500">{{ $user['email'] }} · {{ $user['employee_code'] }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex max-w-sm flex-wrap gap-1.5">
                                    @forelse ($user['roles'] as $role)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">
                                            {{ $role['name'] }}
                                            @if ($this->canManageRoles() && $role['assignment_id'])
                                                <button type="button" class="text-danger-600 hover:text-danger-700" wire:click="removeRole('{{ $user['id'] }}', '{{ $role['assignment_id'] }}')" wire:confirm="Gỡ role này khỏi người dùng?" aria-label="Gỡ {{ $role['name'] }}">×</button>
                                            @endif
                                        </span>
                                    @empty
                                        <span class="text-gray-500">Chưa gán role</span>
                                    @endforelse
                                </div>
                                @if ($this->canManageRoles())
                                    <div class="mt-3 flex items-center gap-2">
                                        <select wire:model="roleSelections.{{ $user['id'] }}" class="min-w-0 rounded-lg border-gray-300 bg-white px-3 py-2 text-xs shadow-sm outline-none transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900">
                                            <option value="">Gán role…</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <x-filament::button size="sm" color="gray" wire:click="assignRole('{{ $user['id'] }}')">Gán</x-filament::button>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <x-filament::badge :color="$user['locked'] ? 'danger' : ($user['status'] === 'active' ? 'success' : 'warning')">
                                    {{ $user['locked'] ? 'Đã khóa' : ($user['status'] === 'active' ? 'Hoạt động' : 'Tạm dừng') }}
                                </x-filament::badge>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex max-w-xs flex-wrap justify-end gap-2">
                                    @if ($this->canUnlockUsers() && $user['locked'])
                                        <x-filament::button size="sm" color="success" wire:click="unlock('{{ $user['id'] }}')" wire:confirm="Mở khóa tài khoản này?">Mở khóa</x-filament::button>
                                    @endif
                                    @if ($this->canUpdateUsers())
                                        @if ($user['status'] === 'active')
                                            <x-filament::button size="sm" color="warning" wire:click="setStatus('{{ $user['id'] }}', 'suspended')" wire:confirm="Tạm dừng tài khoản này?">Tạm dừng</x-filament::button>
                                        @else
                                            <x-filament::button size="sm" color="success" wire:click="setStatus('{{ $user['id'] }}', 'active')" wire:confirm="Kích hoạt tài khoản này?">Kích hoạt</x-filament::button>
                                        @endif
                                        <x-filament::button size="sm" color="gray" wire:click="forcePasswordChange('{{ $user['id'] }}')" wire:confirm="Buộc người dùng đổi mật khẩu ở lần đăng nhập sau?">Buộc đổi mật khẩu</x-filament::button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-gray-500">Không có người dùng phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
