<x-filament-panels::page>


    <x-filament::section class="admin-table-card">
        <div x-on:click.outside="showFilters = false; showColumns = false" x-on:keydown.escape.window="showFilters = false; showColumns = false" x-init="new MutationObserver(() => columns.forEach((visible, index) => $refs.table.querySelectorAll('tr').forEach(row => { if (row.children[index]) row.children[index].style.display = visible ? '' : 'none' }))).observe($refs.table, { childList: true, subtree: true })" x-data='{showFilters: false, showColumns: false, columns: [true, true, true, true, true, true], labels: ["Đề xuất", "Nguồn", "Loại", "Confidence", "Trạng thái", "Thao tác"]}'>
            <div class="admin-table-toolbar flex flex-wrap items-center justify-end gap-2 border-b border-gray-200 dark:border-white/10">
                <x-filament::input.wrapper class="w-full sm:max-w-xs" prefix-icon="heroicon-o-magnifying-glass"><x-filament::input wire:model.live.debounce.300ms="search" placeholder="Nguồn, concept hoặc mã đề xuất" aria-label="Tìm kiếm" /></x-filament::input.wrapper>
                <div class="relative"><x-filament::icon-button icon="heroicon-o-funnel" color="gray" x-bind:class="showFilters ? 'admin-tool-active' : ''" x-on:click="showFilters = !showFilters; showColumns = false" tooltip="Bộ lọc" aria-label="Bộ lọc" />
                    <div x-cloak x-show="showFilters" class="absolute right-0 z-20 mt-2 w-72 space-y-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-gray-900">
                        <div class="flex items-center justify-between"><strong>Bộ lọc</strong><button type="button" wire:click="resetFilters" class="text-sm text-danger-600">Đặt lại</button></div>
                        <label class="grid gap-2 text-sm">Trạng thái
                <select wire:model.live="status">
                    <option value="all">Tất cả</option>
                    <option value="pending">Chờ duyệt</option>
                    <option value="approved">Đã duyệt</option>
                    <option value="rejected">Đã từ chối</option>
                </select></label><label class="grid gap-2 text-sm">Loại đề xuất
                <select wire:model.live="scope">
                    <option value="all">Tất cả loại</option>
                    <option value="concept">Concept</option>
                    <option value="relation">Relation</option>
                    <option value="source">Nguồn tri thức</option>
                </select></label>
                    </div>
                </div>
                <div class="relative"><x-filament::icon-button icon="heroicon-o-view-columns" color="gray" x-bind:class="showColumns ? 'admin-tool-active' : ''" x-on:click="showColumns = !showColumns; showFilters = false" tooltip="Chọn cột" aria-label="Chọn cột" />
                    <div x-cloak x-show="showColumns" class="absolute right-0 z-20 mt-2 w-56 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-gray-900">
                        <div class="mb-3 flex items-center justify-between"><strong>Cột</strong><button type="button" x-on:click="columns = columns.map(() => true)" class="text-sm text-danger-600">Đặt lại</button></div>
                        <template x-for="(label, index) in labels" :key="index"><label class="flex items-center gap-2 py-2 text-sm"><input type="checkbox" x-model="columns[index]" :disabled="index === 0" class="rounded border-gray-300"><span x-text="label"></span></label></template>
                    </div>
                </div>
                <x-filament::icon-button icon="heroicon-o-arrow-path" color="gray" wire:click="resetFilters" tooltip="Làm mới" aria-label="Làm mới danh sách" />
            </div>
        <div class="overflow-x-auto" x-effect="columns.forEach((visible, index) => $refs.table.querySelectorAll('tr').forEach(row => { if (row.children[index]) row.children[index].style.display = visible ? '' : 'none' }))">
            <table x-ref="table" class="w-full min-w-[54rem] divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
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
        </div>
    </x-filament::section>
</x-filament-panels::page>
