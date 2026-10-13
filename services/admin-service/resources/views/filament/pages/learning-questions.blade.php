<x-filament-panels::page>
    <x-filament::section heading="Bộ lọc hỏi đáp" description="Dữ liệu được đọc từ Learning service qua Gateway; mọi thao tác moderation có audit event.">
        <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-end">
            <label class="grid w-full gap-2 text-sm font-medium text-gray-950 dark:text-white lg:max-w-md">
                Tìm kiếm
                <x-filament::input wire:model.live.debounce.300ms="search" placeholder="Tiêu đề, nội dung hoặc tác giả" />
            </label>
            <label class="grid w-full gap-2 text-sm font-medium text-gray-950 dark:text-white sm:max-w-56">
                Trạng thái
                <select wire:model.live="status">
                    <option value="all">Tất cả</option>
                    <option value="open">Đang mở</option>
                    <option value="closed">Đã đóng</option>
                    <option value="hidden">Đã ẩn</option>
                </select>
            </label>
            <label class="grid w-full gap-2 text-sm font-medium text-gray-950 dark:text-white sm:max-w-56">
                Phạm vi
                <select wire:model.live="scope">
                    <option value="all">Toàn tổ chức</option>
                    <option value="mine">Do tôi phụ trách</option>
                </select>
            </label>
            <x-filament::button color="gray" wire:click="resetFilters" icon="heroicon-o-arrow-path">Làm mới</x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section heading="Hàng đợi hỏi đáp" description="Ẩn, khôi phục, đóng và mở lại câu hỏi. Mỗi thao tác đều lưu lịch sử moderation.">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[54rem] divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-white/5 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Câu hỏi</th>
                        <th class="px-4 py-3">Taxonomy</th>
                        <th class="px-4 py-3">Tác giả</th>
                        <th class="px-4 py-3">Trả lời</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($questions as $question)
                        <tr class="border-b border-gray-100 align-top dark:border-white/10">
                            <td class="px-4 py-3"><p class="font-medium">{{ $question['title'] }}</p><p class="mt-1 max-w-lg text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($question['body_plaintext'], 110) }}</p></td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $question['topic_name'] ?? '—' }}<br><span class="text-xs">{{ $question['category_name'] ?? '' }}</span></td>
                            <td class="px-4 py-3 font-mono text-xs">{{ \Illuminate\Support\Str::limit($question['author_user_id'], 12, '…') }}</td>
                            <td class="px-4 py-3">{{ $question['answer_count'] }}</td>
                            <td class="px-4 py-3"><x-filament::badge :color="$question['question_status'] === 'hidden' ? 'danger' : ($question['question_status'] === 'closed' ? 'gray' : 'success')">{{ $question['question_status'] }}</x-filament::badge></td>
                            <td class="px-4 py-3 text-right"><div class="flex justify-end gap-2">
                                @if ($question['question_status'] === 'hidden')
                                    <x-filament::button size="sm" color="gray" wire:click="moderate('{{ $question['id'] }}', 'restore')">Khôi phục</x-filament::button>
                                @elseif ($question['question_status'] === 'closed')
                                    <x-filament::button size="sm" color="gray" wire:click="moderate('{{ $question['id'] }}', 'reopen')">Mở lại</x-filament::button>
                                    <x-filament::button size="sm" color="danger" wire:click="moderate('{{ $question['id'] }}', 'hide')">Ẩn</x-filament::button>
                                @else
                                    <x-filament::button size="sm" color="gray" wire:click="moderate('{{ $question['id'] }}', 'close')">Đóng</x-filament::button>
                                    <x-filament::button size="sm" color="danger" wire:click="moderate('{{ $question['id'] }}', 'hide')">Ẩn</x-filament::button>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-500">Chưa có câu hỏi phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
