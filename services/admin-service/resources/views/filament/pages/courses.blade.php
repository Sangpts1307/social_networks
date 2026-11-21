<x-filament-panels::page>
    @if ($showCreateForm)
    <x-filament::section heading="Tạo khóa học" description="Tạo bản nháp chương trình học. Module, lesson, enrollment và evaluation sẽ tiếp tục mở rộng trên cùng Learning service.">
        <div class="grid gap-3 lg:grid-cols-2">
            <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">Tên khóa học <x-filament::input wire:model="courseTitle" placeholder="Tên khóa học" /></label>
            <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">Topic <select wire:model="topicId"><option value="">Không chọn</option>@foreach ($topics as $topic)<option value="{{ $topic['id'] }}">{{ $topic['name'] }}</option>@endforeach</select></label>
            <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white lg:col-span-2">Mô tả <textarea wire:model="courseDescription" rows="3" placeholder="Mục tiêu và nội dung khóa học"></textarea></label>
        </div>
        <div class="mt-4"><x-filament::button wire:click="saveCourse" icon="heroicon-o-plus">Tạo bản nháp</x-filament::button></div>
    </x-filament::section>
    @endif

    <x-filament::section class="admin-table-card">
        <div x-on:keydown.escape.window="showFilters = false; showColumns = false" x-init="new MutationObserver(() => columns.forEach((visible, index) => $refs.table.querySelectorAll('tr').forEach(row => { if (row.children[index]) row.children[index].style.display = visible ? '' : 'none' }))).observe($refs.table, { childList: true, subtree: true })" x-data='{showFilters: false, showColumns: false, columns: [true, true, true, true], labels: ["Khóa học", "Chương trình", "Trạng thái", "Thao tác"]}'>
            <div class="admin-table-toolbar flex flex-wrap items-center justify-end gap-2 border-b border-gray-200 dark:border-white/10">
                <x-filament::input.wrapper class="w-full sm:max-w-xs" prefix-icon="heroicon-o-magnifying-glass"><x-filament::input wire:model.live.debounce.300ms="search" placeholder="Tên hoặc mã khóa học" aria-label="Tìm kiếm" /></x-filament::input.wrapper>
                <div class="relative" x-on:click.outside="showFilters = false"><x-filament::icon-button icon="heroicon-o-funnel" color="gray" x-bind:class="showFilters ? 'admin-tool-active' : ''" x-on:click="showFilters = !showFilters; showColumns = false" tooltip="Bộ lọc" aria-label="Bộ lọc" />
                    <div x-cloak x-show="showFilters" class="absolute right-0 z-20 mt-2 w-72 space-y-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-gray-900">
                        <div class="flex items-center justify-between"><strong>Bộ lọc</strong><button type="button" wire:click="resetFilters" class="text-sm text-danger-600">Đặt lại</button></div>
                        <label class="grid gap-2 text-sm">Trạng thái
                <select wire:model.live="status">
                    <option value="all">Tất cả</option>
                    <option value="draft">Bản nháp</option>
                    <option value="published">Đã xuất bản</option>
                    <option value="closed">Đã đóng</option>
                </select></label><label class="grid gap-2 text-sm">Phạm vi
                <select wire:model.live="scope">
                    <option value="all">Toàn tổ chức</option>
                    <option value="assigned">Được phân công</option>
                </select></label>
                    </div>
                </div>
                <div class="relative" x-on:click.outside="showColumns = false"><x-filament::icon-button icon="heroicon-o-view-columns" color="gray" x-bind:class="showColumns ? 'admin-tool-active' : ''" x-on:click="showColumns = !showColumns; showFilters = false" tooltip="Chọn cột" aria-label="Chọn cột" />
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
                        <th class="px-4 py-3">Khóa học</th>
                        <th class="px-4 py-3">Chương trình</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr class="border-b border-gray-100 dark:border-white/10"><td class="px-4 py-3 font-medium">{{ $course['title'] }}</td><td class="px-4 py-3">{{ $course['topic_name'] ?? '—' }}</td><td class="px-4 py-3"><x-filament::badge :color="$course['status'] === 'published' ? 'success' : ($course['status'] === 'closed' ? 'gray' : 'warning')">{{ ['draft' => 'Bản nháp', 'published' => 'Đã xuất bản', 'closed' => 'Đã đóng'][$course['status']] ?? $course['status'] }}</x-filament::badge></td><td class="px-4 py-3 text-right"><div class="flex justify-end gap-2">@if($course['status'] === 'draft')<x-filament::button size="sm" wire:click="updateCourseLifecycle('{{ $course['id'] }}', 'publish')">Xuất bản</x-filament::button>@elseif($course['status'] === 'published')<x-filament::button size="sm" color="gray" wire:click="updateCourseLifecycle('{{ $course['id'] }}', 'close')">Đóng</x-filament::button>@else<x-filament::button size="sm" color="gray" wire:click="updateCourseLifecycle('{{ $course['id'] }}', 'restore')">Khôi phục nháp</x-filament::button>@endif</div></td></tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-12 text-center text-gray-500">Chưa có khóa học.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
