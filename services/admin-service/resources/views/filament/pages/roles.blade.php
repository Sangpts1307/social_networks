<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-3">
        <x-filament::section heading="Vai trò">
            <div class="grid gap-2">
                @forelse ($roles as $role)
                    <button
                        type="button"
                        wire:click="selectRole('{{ $role['id'] }}')"
                        @class([
                            'flex w-full items-center justify-between rounded-lg px-4 py-3 text-left text-sm font-medium transition',
                            'bg-primary-500 text-white shadow-sm' => $selectedRoleId === $role['id'],
                            'bg-gray-50 text-gray-950 hover:bg-gray-100 dark:bg-white/5 dark:text-white dark:hover:bg-white/10' => $selectedRoleId !== $role['id'],
                        ])
                    >
                        <span>{{ $role['name'] }}</span>
                        <span class="text-xs opacity-75">{{ $role['code'] }}</span>
                    </button>
                @empty
                    <p class="text-sm text-gray-500">Chưa có role.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section class="xl:col-span-2" heading="Ma trận permission" description="Chọn role để xem và cập nhật permission tương ứng.">
            @if ($selectedRoleId)
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
