<x-filament-panels::page>
    <x-filament::section heading="Hàng đợi curation" description="Base reviewer cho đề xuất tri thức. AI chỉ đề xuất; mọi thay đổi chính thức luôn cần người duyệt.">
        <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-end">
            <label class="grid w-full gap-2 text-sm font-medium text-gray-950 dark:text-white lg:max-w-md">
                Tìm kiếm
                <x-filament::input wire:model.live.debounce.300ms="search" placeholder="Nguồn, concept hoặc mã đề xuất" />
            </label>
            <label class="grid w-full gap-2 text-sm font-medium text-gray-950 dark:text-white sm:max-w-56">
                Trạng thái
                <select wire:model.live="status">
                    <option value="all">Tất cả</option>
                    <option value="pending">Chờ duyệt</option>
                    <option value="approved">Đã duyệt</option>
                    <option value="rejected">Đã từ chối</option>
                </select>
            </label>
            <label class="grid w-full gap-2 text-sm font-medium text-gray-950 dark:text-white sm:max-w-56">
                Loại đề xuất
                <select wire:model.live="scope">
                    <option value="all">Tất cả loại</option>
                    <option value="concept">Concept</option>
                    <option value="relation">Relation</option>
                    <option value="source">Nguồn tri thức</option>
                </select>
            </label>
            <x-filament::button color="gray" wire:click="resetFilters" icon="heroicon-o-arrow-path">Làm mới</x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section heading="Đề xuất tri thức" description="Approve/reject, re-index và provenance sẽ được bật cùng Curation API và event contract.">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[54rem] divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-white/5 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Đề xuất</th>
                        <th class="px-4 py-3">Nguồn</th>
                        <th class="px-4 py-3">Loại</th>
                        <th class="px-4 py-3">Confidence</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="6" class="px-4 py-12 text-center text-gray-500">Chưa có proposal. Màn này chờ Curation API và event provenance.</td></tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
