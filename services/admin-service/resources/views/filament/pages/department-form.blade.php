<x-filament-panels::page><div class="max-w-3xl"><x-filament::section :heading="$this->getTitle()"><form wire:submit="save" class="grid gap-5 sm:grid-cols-2">
<label class="grid gap-2 text-sm font-medium">Mã phòng ban<x-filament::input wire:model="code" required />@error('code') <span class="text-danger-600">{{ $message }}</span> @enderror</label>
<label class="grid gap-2 text-sm font-medium">Tên phòng ban<x-filament::input wire:model="name" required />@error('name') <span class="text-danger-600">{{ $message }}</span> @enderror</label>
<label class="grid gap-2 text-sm font-medium">Loại đơn vị<x-filament::input wire:model="unitType" required /></label>
<label class="grid gap-2 text-sm font-medium">Thứ tự hiển thị<x-filament::input type="number" min="0" wire:model="sortOrder" /></label>
<label class="grid gap-2 text-sm font-medium">Đơn vị cha<select wire:model="parentId" class="rounded-lg border-gray-300 bg-white px-3 py-2 dark:bg-gray-900"><option value="">Không có</option>@foreach ($units as $unit)@if ($unit['id'] !== $recordId)<option value="{{ $unit['id'] }}">{{ $unit['name'] }}</option>@endif @endforeach</select></label>
<label class="grid gap-2 text-sm font-medium">Người quản lý<select wire:model="managerUserId" class="rounded-lg border-gray-300 bg-white px-3 py-2 dark:bg-gray-900"><option value="">Chưa phân công</option>@foreach ($users as $user)<option value="{{ $user['id'] }}">{{ $user['display_name'] }}</option>@endforeach</select></label>
<div class="flex gap-3 sm:col-span-2"><x-filament::button type="submit">Lưu phòng ban</x-filament::button><x-filament::button color="gray" tag="a" :href="\App\Filament\Pages\Departments::getUrl()">Huỷ</x-filament::button></div>
</form></x-filament::section></div></x-filament-panels::page>
