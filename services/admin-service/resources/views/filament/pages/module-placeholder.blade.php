<x-filament-panels::page>
    <x-filament::section :heading="$this->getTitle()" :description="$this->description()">
        <div class="grid gap-5 lg:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-white/10 dark:bg-white/5 lg:col-span-2">
                <p class="text-sm font-medium text-gray-950 dark:text-white">Base module đã sẵn sàng</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Chưa kết nối dữ liệu thật. Khi service sở hữu nghiệp vụ phát hành Admin API, module này sẽ gọi qua API Gateway — không truy cập database trực tiếp.</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($this->capabilities() as $capability)
                        <div class="flex items-center gap-3 rounded-lg bg-white px-4 py-3 text-sm text-gray-700 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:text-gray-200 dark:ring-white/10">
                            <x-filament::icon icon="heroicon-o-check-circle" class="size-5 text-primary-500" />
                            <span>{{ $capability }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-xl border border-dashed border-gray-300 p-5 dark:border-white/15">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Service sở hữu</p>
                <p class="mt-2 font-mono text-sm font-semibold text-gray-950 dark:text-white">{{ $this->owner() }}</p>
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Trạng thái: chờ contract Admin API và permission policy riêng của service.</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
