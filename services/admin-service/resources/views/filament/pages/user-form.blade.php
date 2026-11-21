<x-filament-panels::page>
    <div class="grid max-w-4xl gap-6">
        <x-filament::section :heading="$this->getTitle()" description="Thông tin tài khoản nhân sự">
            <form wire:submit="save" class="grid gap-5 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium">Họ tên <x-filament::input wire:model="displayName" :disabled="! $this->canEdit()" required /> @error('displayName') <span class="text-danger-600">{{ $message }}</span> @enderror</label>
                @if ($recordId)
                    <label class="grid gap-2 text-sm font-medium">Mã nhân viên <x-filament::input wire:model="employeeCode" :disabled="! $this->canEdit()" /> @error('employeeCode') <span class="text-danger-600">{{ $message }}</span> @enderror</label>
                @else
                    <label class="grid gap-2 text-sm font-medium">Mã nhân viên <x-filament::input value="Tự động tạo khi lưu (NV01, NV02, …)" disabled /></label>
                @endif
                <label class="grid gap-2 text-sm font-medium">Giới tính
                    <x-filament::input.wrapper :disabled="! $this->canEdit()">
                        <x-filament::input.select wire:model="gender" :disabled="! $this->canEdit()">
                            <option value="">Chưa cập nhật</option><option value="male">Nam</option><option value="female">Nữ</option><option value="other">Khác</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                    @error('gender') <span class="text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="grid gap-2 text-sm font-medium">Ngày sinh
                    <x-filament::input type="date" wire:model="birthDate" :disabled="! $this->canEdit()" />
                    @error('birthDate') <span class="text-danger-600">{{ $message }}</span> @enderror
                </label>
                <div class="grid gap-2 text-sm font-medium" x-data="{ open: false, query: '', units: @js(collect($orgUnits)->map(fn ($unit) => ['id' => $unit['id'], 'name' => $unit['name']])->values()), selectedId: $wire.entangle('orgUnitId') }" @click.outside="open = false">
                    <span>Phòng ban</span>
                    <div class="relative">
                        <button type="button" @disabled(! $this->canEdit()) @click="open = !open; if (open) { query = ''; $nextTick(() => $refs.unitSearch.focus()) }" :aria-expanded="open.toString()" aria-controls="unit-options" class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-left font-normal disabled:opacity-70 dark:border-white/10 dark:bg-gray-900">
                            <span x-text="units.find(unit => unit.id === selectedId)?.name || 'Chọn phòng ban'" :class="selectedId ? '' : 'text-gray-500'"></span>
                            <span aria-hidden="true">⌄</span>
                        </button>
                        <div id="unit-options" x-show="open" x-cloak class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-white/10 dark:bg-gray-900" style="max-height: 16rem; overflow-y: scroll; overscroll-behavior: contain; scrollbar-width: thin;" role="listbox">
                            <div class="sticky top-0 bg-white p-2 dark:bg-gray-900">
                                <input x-ref="unitSearch" type="search" x-model="query" @keydown.escape="open = false" placeholder="Tìm phòng ban…" aria-label="Tìm phòng ban" class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 font-normal dark:border-white/10 dark:bg-gray-900" />
                            </div>
                            <template x-for="unit in units.filter(unit => unit.name.toLocaleLowerCase().includes(query.toLocaleLowerCase()))" :key="unit.id">
                                <button type="button" role="option" :aria-selected="(selectedId === unit.id).toString()" @click="selectedId = unit.id; open = false" class="block w-full px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-white/10" x-text="unit.name"></button>
                            </template>
                            <p x-show="!units.some(unit => unit.name.toLocaleLowerCase().includes(query.toLocaleLowerCase()))" class="px-3 py-2 text-gray-500">Không tìm thấy phòng ban</p>
                        </div>
                    </div>
                    @error('orgUnitId') <span class="text-danger-600">{{ $message }}</span> @enderror
                </div>
                @if ($this->canManageRoles())
                    <div class="grid gap-2 text-sm font-medium" x-data="{ open: false, query: '', roles: @js(collect($availableRoles)->map(fn ($role) => ['id' => $role['id'], 'name' => $role['name']])->values()), selectedId: $wire.entangle('selectedRoleId') }" @click.outside="open = false">
                        <span>Role</span>
                        <div class="relative">
                            <button type="button" @click="open = !open; if (open) { query = ''; $nextTick(() => $refs.roleSearch.focus()) }" :aria-expanded="open.toString()" aria-controls="role-options" class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-left font-normal dark:border-white/10 dark:bg-gray-900">
                                <span x-text="roles.find(role => role.id === selectedId)?.name || 'Chọn role'" :class="selectedId ? '' : 'text-gray-500'"></span>
                                <span aria-hidden="true">⌄</span>
                            </button>
                            <div id="role-options" x-show="open" x-cloak class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-white/10 dark:bg-gray-900" style="max-height: 16rem; overflow-y: scroll; overscroll-behavior: contain; scrollbar-width: thin;" role="listbox">
                                <div class="sticky top-0 bg-white p-2 dark:bg-gray-900">
                                    <input x-ref="roleSearch" type="search" x-model="query" @keydown.escape="open = false" placeholder="Tìm role…" aria-label="Tìm role" class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 font-normal dark:border-white/10 dark:bg-gray-900" />
                                </div>
                                <template x-for="role in roles.filter(role => role.name.toLocaleLowerCase().includes(query.toLocaleLowerCase()))" :key="role.id">
                                    <button type="button" role="option" :aria-selected="(selectedId === role.id).toString()" @click="selectedId = role.id; query = role.name; open = false" class="block w-full px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-white/10" x-text="role.name"></button>
                                </template>
                                <p x-show="!roles.some(role => role.name.toLocaleLowerCase().includes(query.toLocaleLowerCase()))" class="px-3 py-2 text-gray-500">Không tìm thấy role</p>
                            </div>
                        </div>
                        @error('selectedRoleId') <span class="text-danger-600">{{ $message }}</span> @enderror
                    </div>
                @else
                    <label class="grid gap-2 text-sm font-medium">Role
                        <x-filament::input :value="collect($userRoles)->pluck('name')->join(', ') ?: 'Chưa gán role'" disabled />
                    </label>
                @endif
                <label class="grid gap-2 text-sm font-medium">Email công ty <x-filament::input type="email" wire:model="email" :disabled="(bool) $recordId" required /> @error('email') <span class="text-danger-600">{{ $message }}</span> @enderror</label>
                <label class="grid gap-2 text-sm font-medium">{{ $recordId ? 'Mật khẩu mới (để trống nếu không đổi)' : 'Mật khẩu ban đầu' }} <x-filament::input type="password" wire:model="password" :disabled="! $this->canEdit()" /> @error('password') <span class="text-danger-600">{{ $message }}</span> @enderror</label>
                <div class="flex gap-3 sm:col-span-2">
                    @if ($this->canEdit())
                        <x-filament::button type="submit">Lưu người dùng</x-filament::button>
                    @endif
                    <x-filament::button color="gray" tag="a" :href="\App\Filament\Pages\Users::getUrl()">{{ $this->canEdit() ? 'Huỷ' : 'Quay lại' }}</x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
