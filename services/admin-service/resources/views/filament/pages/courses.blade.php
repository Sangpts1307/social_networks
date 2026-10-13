<x-filament-panels::page>
    <x-filament::section heading="Tạo khóa học" description="Tạo bản nháp chương trình học. Module, lesson, enrollment và evaluation sẽ tiếp tục mở rộng trên cùng Learning service.">
        <div class="grid gap-3 lg:grid-cols-2">
            <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">Tên khóa học <x-filament::input wire:model="courseTitle" placeholder="Tên khóa học" /></label>
            <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">Topic <select wire:model="topicId"><option value="">Không chọn</option>@foreach ($topics as $topic)<option value="{{ $topic['id'] }}">{{ $topic['name'] }}</option>@endforeach</select></label>
            <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white lg:col-span-2">Mô tả <textarea wire:model="courseDescription" rows="3" placeholder="Mục tiêu và nội dung khóa học"></textarea></label>
        </div>
        <div class="mt-4"><x-filament::button wire:click="saveCourse" icon="heroicon-o-plus">Tạo bản nháp</x-filament::button></div>
    </x-filament::section>

    <x-filament::section heading="Bộ lọc khóa học" description="Lọc danh sách và vận hành vòng đời xuất bản.">
        <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-end">
            <label class="grid w-full gap-2 text-sm font-medium text-gray-950 dark:text-white lg:max-w-md">
                Tìm kiếm
                <x-filament::input wire:model.live.debounce.300ms="search" placeholder="Tên hoặc mã khóa học" />
            </label>
            <label class="grid w-full gap-2 text-sm font-medium text-gray-950 dark:text-white sm:max-w-56">
                Trạng thái
                <select wire:model.live="status">
                    <option value="all">Tất cả</option>
                    <option value="draft">Bản nháp</option>
                    <option value="published">Đã xuất bản</option>
                    <option value="closed">Đã đóng</option>
                </select>
            </label>
            <label class="grid w-full gap-2 text-sm font-medium text-gray-950 dark:text-white sm:max-w-56">
                Phạm vi
                <select wire:model.live="scope">
                    <option value="all">Toàn tổ chức</option>
                    <option value="assigned">Được phân công</option>
                </select>
            </label>
            <x-filament::button color="gray" wire:click="resetFilters" icon="heroicon-o-arrow-path">Làm mới</x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section heading="Danh sách khóa học" description="Xuất bản, đóng hoặc đưa khóa học trở lại bản nháp.">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[54rem] divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-white/5 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Khóa học</th>
                        <th class="px-4 py-3">Chương trình</th>
                        <th class="px-4 py-3">Phụ trách</th>
                        <th class="px-4 py-3">Học viên</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr class="border-b border-gray-100 dark:border-white/10"><td class="px-4 py-3 font-medium">{{ $course['title'] }}</td><td class="px-4 py-3">{{ $course['topic_name'] ?? '—' }}</td><td class="px-4 py-3 font-mono text-xs">{{ \Illuminate\Support\Str::limit($course['id'], 12, '…') }}</td><td class="px-4 py-3">—</td><td class="px-4 py-3"><x-filament::badge :color="$course['status'] === 'published' ? 'success' : ($course['status'] === 'closed' ? 'gray' : 'warning')">{{ $course['status'] }}</x-filament::badge></td><td class="px-4 py-3 text-right"><div class="flex justify-end gap-2">@if($course['status'] === 'draft')<x-filament::button size="sm" wire:click="updateCourseLifecycle('{{ $course['id'] }}', 'publish')">Xuất bản</x-filament::button>@elseif($course['status'] === 'published')<x-filament::button size="sm" color="gray" wire:click="updateCourseLifecycle('{{ $course['id'] }}', 'close')">Đóng</x-filament::button>@else<x-filament::button size="sm" color="gray" wire:click="updateCourseLifecycle('{{ $course['id'] }}', 'restore')">Khôi phục nháp</x-filament::button>@endif</div></td></tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-500">Chưa có khóa học.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
