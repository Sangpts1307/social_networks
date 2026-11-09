<x-filament-panels::page>
    <div class="max-w-3xl">
        <x-filament::section :heading="$this->getTitle()" description="Thông tin tài khoản nhân sự">
            <form wire:submit="save" class="grid gap-5 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium">Họ tên <x-filament::input wire:model="displayName" required /> @error('displayName') <span class="text-danger-600">{{ $message }}</span> @enderror</label>
                <label class="grid gap-2 text-sm font-medium">Email công ty <x-filament::input type="email" wire:model="email" :disabled="(bool) $recordId" required /> @error('email') <span class="text-danger-600">{{ $message }}</span> @enderror</label>
                <label class="grid gap-2 text-sm font-medium">Mã nhân viên <x-filament::input wire:model="employeeCode" :disabled="(bool) $recordId" /> @error('employeeCode') <span class="text-danger-600">{{ $message }}</span> @enderror</label>
                <label class="grid gap-2 text-sm font-medium">Phòng ban <select wire:model="orgUnitId" required class="rounded-lg border-gray-300 bg-white px-3 py-2 dark:bg-gray-900"><option value="">Chọn phòng ban</option>@foreach ($orgUnits as $unit)<option value="{{ $unit['id'] }}">{{ $unit['name'] }}</option>@endforeach</select> @error('orgUnitId') <span class="text-danger-600">{{ $message }}</span> @enderror</label>
                @unless ($recordId)<label class="grid gap-2 text-sm font-medium sm:col-span-2">Mật khẩu ban đầu <x-filament::input type="password" wire:model="password" required /><span class="text-xs font-normal text-gray-500">Ít nhất 12 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt. Người dùng phải đổi mật khẩu ở lần đăng nhập đầu tiên.</span>@error('password') <span class="text-danger-600">{{ $message }}</span> @enderror</label>@endunless
                <div class="flex gap-3 sm:col-span-2"><x-filament::button type="submit">Lưu người dùng</x-filament::button><x-filament::button color="gray" tag="a" :href="\App\Filament\Pages\Users::getUrl()">Huỷ</x-filament::button></div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
