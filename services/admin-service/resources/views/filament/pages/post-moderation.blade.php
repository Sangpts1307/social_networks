<x-filament-panels::page>
    <div class="space-y-4">
        <!-- TOP NAVIGATION TABS & ACTIONS -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-filament::tabs>
                <x-filament::tabs.item
                    :active="$activeTab === 'posts'"
                    wire:click="switchTab('posts')"
                    :badge="$summary['total_posts'] ?? 0"
                    icon="heroicon-m-chat-bubble-left-right"
                >
                    Bài viết mạng xã hội
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="$activeTab === 'news'"
                    wire:click="switchTab('news')"
                    :badge="$summary['total_news'] ?? 0"
                    icon="heroicon-m-newspaper"
                >
                    Tin tức nội bộ
                </x-filament::tabs.item>
            </x-filament::tabs>

            <div class="flex items-center gap-2">
                <x-filament::button color="primary" icon="heroicon-o-shield-check" wire:click="scanAllWithAi">
                    AI Quét toàn bộ
                </x-filament::button>
            </div>
        </div>

        <!-- MAIN TABLE CARD -->
        <x-filament::section class="admin-table-card">
            <!-- TOOLBAR: SEARCH & MENUS (ALIGNED RIGHT) -->
            <div class="admin-table-toolbar flex flex-wrap items-center justify-end gap-2 border-b border-gray-200 dark:border-white/10">
                <x-filament::input.wrapper class="w-full sm:max-w-xs" prefix-icon="heroicon-o-magnifying-glass">
                    <x-filament::input wire:model.live.debounce.300ms="search" placeholder="Tìm theo nội dung, tiêu đề, từ khóa..." aria-label="Tìm kiếm bài viết" />
                </x-filament::input.wrapper>

                <!-- Filter Menu -->
                <div class="relative">
                    <x-filament::icon-button
                        icon="heroicon-o-funnel"
                        :color="$openMenu === 'filters' ? 'primary' : 'gray'"
                        wire:click="toggleMenu('filters')"
                        tooltip="Bộ lọc"
                        aria-label="Bộ lọc"
                    />
                    @if ($statusFilter !== 'all' || $channelFilter !== '')
                        <span class="absolute -right-1 -top-2 rounded-full bg-primary-100 px-1 text-xs text-primary-700 dark:bg-primary-900/50 dark:text-primary-300">
                            {{ (int) ($statusFilter !== 'all') + (int) ($channelFilter !== '') }}
                        </span>
                    @endif
                    @if ($openMenu === 'filters')
                        <div class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-gray-900">
                            <div class="mb-4 flex items-center justify-between">
                                <strong>Bộ lọc</strong>
                                <button type="button" wire:click="resetFilters" class="text-sm text-danger-600">Đặt lại</button>
                            </div>
                            <label class="mb-4 block text-sm">
                                <span class="mb-1.5 block font-medium">Trạng thái</span>
                                <select wire:model.live="statusFilter" class="block w-full min-w-0 max-w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                                    <option value="all">Tất cả ({{ $summary['total_all'] ?? 0 }})</option>
                                    <option value="queue">Hàng đợi cần duyệt ({{ $summary['total_queue'] ?? 0 }})</option>
                                    <option value="flagged">AI Cảnh báo rủi ro ({{ $summary['total_flagged'] ?? 0 }})</option>
                                    <option value="reported">Bị báo cáo vi phạm ({{ $summary['total_reported'] ?? 0 }})</option>
                                    <option value="hidden">Đang tạm ẩn ({{ $summary['total_hidden'] ?? 0 }})</option>
                                    <option value="clear">Đã duyệt an toàn</option>
                                </select>
                            </label>
                            @if ($activeTab === 'posts' && count($channels) > 0)
                                <label class="block text-sm">
                                    <span class="mb-1.5 block font-medium">Kênh thảo luận</span>
                                    <select wire:model.live="channelFilter" class="block w-full min-w-0 max-w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                                        <option value="">Tất cả kênh</option>
                                        @foreach ($channels as $ch)
                                            <option value="{{ $ch['id'] }}">#{{ $ch['code'] ?? $ch['name'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Column Toggle Menu -->
                <div class="relative">
                    <x-filament::icon-button
                        icon="heroicon-o-view-columns"
                        :color="$openMenu === 'columns' ? 'primary' : 'gray'"
                        wire:click="toggleMenu('columns')"
                        tooltip="Chọn cột"
                        aria-label="Chọn cột"
                    />
                    @if ($openMenu === 'columns')
                        <div class="absolute right-0 z-20 mt-2 w-56 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-gray-900">
                            <div class="mb-3 flex items-center justify-between">
                                <strong>Cột</strong>
                                <button type="button" wire:click="resetColumns" class="text-sm text-danger-600">Đặt lại</button>
                            </div>
                            @foreach ([
                                'content' => 'Bài viết',
                                'channel' => 'Kênh đăng',
                                'status' => 'Trạng thái & AI Guard',
                                'actions' => 'Kiểm duyệt',
                            ] as $key => $label)
                                <label class="flex items-center gap-2 py-2 text-sm">
                                    <input type="checkbox" wire:model.live="visibleColumns.{{ $key }}" @disabled($key === 'content') class="rounded border-gray-300">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Refresh Button -->
                <x-filament::icon-button icon="heroicon-o-arrow-path" color="gray" wire:click="refreshPosts" tooltip="Làm mới" aria-label="Làm mới danh sách" />
            </div>

            <!-- POSTS TABLE -->
            <div class="overflow-x-auto">
                <table class="w-full min-w-[46rem] table-fixed divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-white/5 dark:text-gray-300">
                        <tr>
                            <th class="w-2/5 px-4 py-3">Bài viết</th>
                            @if ($visibleColumns['channel'])
                                <th class="px-4 py-3 whitespace-nowrap">Kênh đăng</th>
                            @endif
                            @if ($visibleColumns['status'])
                                <th class="px-4 py-3 min-w-[12rem]">Trạng thái & AI Guard</th>
                            @endif
                            @if ($visibleColumns['actions'])
                                <th class="px-4 py-3 whitespace-nowrap text-right">Kiểm duyệt</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($posts as $post)
                            @php
                                $hasAiAlert = !empty($post['ai_alert']);
                                $hasReports = ($post['open_reports_count'] ?? 0) > 0;
                                $isHidden = $post['publication_status'] === 'hidden';
                                $isRemoved = $post['publication_status'] === 'removed';
                                $isFlagged = $post['moderation_status'] === 'flagged';
                                $authorName = $this->getAuthorName($post['author_user_id'] ?? null);
                            @endphp
                            <tr wire:key="post-row-{{ $post['id'] }}" class="align-top text-gray-700 dark:text-gray-200 hover:bg-gray-50/50 dark:hover:bg-white/5">
                                
                                <!-- Bài viết: Tác giả & Tên bài viết (max 2 dòng) -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-start gap-2.5">
                                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 font-semibold text-xs text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">
                                            {{ mb_substr($authorName, 0, 1) }}
                                        </div>
                                        <div class="min-w-0 flex-1 space-y-0.5">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="font-medium text-gray-900 text-xs dark:text-white">
                                                    {{ $authorName }}
                                                </span>
                                                @if (!empty($post['post_type']) && $post['post_type'] !== 'update')
                                                    <span class="rounded bg-gray-100 px-1.5 py-0.2 text-[10px] font-mono text-gray-600 dark:bg-white/10 dark:text-gray-300 uppercase">
                                                        {{ $post['post_type'] }}
                                                    </span>
                                                @endif
                                            </div>

                                            <p class="max-w-full overflow-hidden text-ellipsis whitespace-nowrap font-semibold text-gray-900 hover:text-primary-600 cursor-pointer text-xs leading-snug dark:text-white"
                                                wire:click="openDetail('{{ $post['id'] }}')" title="{{ $post['title'] ?: 'Bài viết không có tiêu đề' }}">
                                                {{ $post['title'] ?: 'Bài viết không có tiêu đề' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Channel -->
                                @if ($visibleColumns['channel'])
                                    <td class="whitespace-nowrap px-4 py-3.5">
                                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-mono font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">
                                            #{{ $post['channel_code'] ?? $post['channel_name'] ?? 'general' }}
                                        </span>
                                    </td>
                                @endif

                                <!-- Status & AI Guard -->
                                @if ($visibleColumns['status'])
                                    <td class="px-4 py-3.5">
                                        <div class="flex flex-col gap-1 items-start">
                                            @if ($hasAiAlert)
                                                <x-filament::badge color="danger" icon="heroicon-o-shield-exclamation">
                                                    {{ $post['ai_alert'] }}
                                                </x-filament::badge>
                                            @endif

                                            @if ($hasReports)
                                                <x-filament::badge color="warning" icon="heroicon-o-flag">
                                                    Bị báo cáo: {{ $post['open_reports_count'] }} lượt
                                                </x-filament::badge>
                                            @endif

                                            @if ($isHidden)
                                                <x-filament::badge color="gray">
                                                    Tạm ẩn bởi kiểm duyệt
                                                </x-filament::badge>
                                            @elseif ($isRemoved)
                                                <x-filament::badge color="danger">
                                                    Đã gỡ bỏ
                                                </x-filament::badge>
                                            @elseif (!$hasAiAlert && !$hasReports && !$isFlagged)
                                                <x-filament::badge color="success" icon="heroicon-o-check-circle">
                                                    Bình thường
                                                </x-filament::badge>
                                            @endif

                                            @if (!empty($post['comments_locked_at']))
                                                <x-filament::badge color="gray" icon="heroicon-o-lock-closed">
                                                    Đã khóa bình luận
                                                </x-filament::badge>
                                            @endif
                                        </div>
                                    </td>
                                @endif

                                <!-- Actions -->
                                @if ($visibleColumns['actions'])
                                    <td class="whitespace-nowrap px-4 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <x-filament::button size="xs" color="gray" wire:click="openDetail('{{ $post['id'] }}')">
                                                Chi tiết
                                            </x-filament::button>

                                            @if ($isHidden || $isRemoved)
                                                <x-filament::button size="xs" color="success" wire:click="quickRestore('{{ $post['id'] }}')">
                                                    Khôi phục
                                                </x-filament::button>
                                            @else
                                                <x-filament::button size="xs" color="warning" wire:click="quickHide('{{ $post['id'] }}')">
                                                    Tạm ẩn
                                                </x-filament::button>
                                            @endif

                                            @if ($isFlagged || $hasReports || $hasAiAlert)
                                                <x-filament::icon-button size="sm" color="success" icon="heroicon-o-check-circle" wire:click="quickApprove('{{ $post['id'] }}')" tooltip="Duyệt an toàn" />
                                            @endif

                                            <x-filament::icon-button size="sm" icon="heroicon-o-trash" color="gray" wire:click="openAction('{{ $post['id'] }}', 'remove')" tooltip="Gỡ bỏ bài viết" aria-label="Gỡ bỏ" />
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 1 + (int)$visibleColumns['channel'] + (int)$visibleColumns['status'] + (int)$visibleColumns['actions'] }}" class="px-4 py-12 text-center text-gray-500">
                                    Không có bài viết nào phù hợp với bộ lọc.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION FOOTER (BELOW TABLE, MATCHING USERS/DEPARTMENTS SCREENS) -->
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 p-4 text-sm text-gray-500 dark:text-gray-400">
                <span>{{ $totalPosts }} bài viết · Trang {{ $pageNumber }}/{{ $lastPage }}</span>
                <div class="flex gap-2">
                    <x-filament::button size="sm" color="gray" wire:click="goToPage({{ $pageNumber - 1 }})" :disabled="$pageNumber <= 1">
                        Trước
                    </x-filament::button>
                    <x-filament::button size="sm" color="gray" wire:click="goToPage({{ $pageNumber + 1 }})" :disabled="$pageNumber >= $lastPage">
                        Sau
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <!-- ========================================== -->
        <!-- MODAL: CHI TIẾT BÀI VIẾT & BÁO CÁO        -->
        <!-- ========================================== -->
        @if ($showDetailModal && $selectedPost)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/50 backdrop-blur-xs" wire:click.self="closeDetail" x-on:keydown.escape.window="$wire.closeDetail()">
                <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-white/10 dark:bg-gray-900">
                    
                    <!-- Modal Header -->
                    <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-100 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Chi tiết bài viết & Kiểm duyệt</h3>
                            <p class="mt-0.5 text-xs text-gray-500">ID: <span class="font-mono">{{ $selectedPost['id'] }}</span></p>
                        </div>
                        <x-filament::icon-button icon="heroicon-o-x-mark" color="gray" wire:click="closeDetail" tooltip="Đóng" aria-label="Đóng" />
                    </div>

                    <!-- Modal Body -->
                    <div class="space-y-5 p-5 text-xs">
                        <!-- Author & Meta Info -->
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3.5 dark:bg-white/5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 font-semibold text-xs text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">
                                    {{ mb_substr($this->getAuthorName($selectedPost['author_user_id'] ?? null), 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white text-xs">{{ $this->getAuthorName($selectedPost['author_user_id'] ?? null) }}</h4>
                                    <p class="mt-0.5 text-gray-500">Kênh: <span class="font-mono font-medium">#{{ $selectedPost['channel_code'] ?? 'general' }}</span> · {{ \Carbon\Carbon::parse($selectedPost['created_at'])->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>

                            <x-filament::badge :color="$selectedPost['publication_status'] === 'hidden' ? 'gray' : 'success'">
                                {{ $selectedPost['publication_status'] === 'hidden' ? 'Đã tạm ẩn' : 'Đã đăng' }}
                            </x-filament::badge>
                        </div>

                        <!-- Full Post Content -->
                        <div class="space-y-2 text-left">
                            @if (!empty($selectedPost['title']))
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white text-left">{{ $selectedPost['title'] }}</h4>
                            @endif
                            <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-4 text-xs leading-relaxed text-gray-800 whitespace-pre-line text-left dark:border-white/10 dark:bg-gray-800 dark:text-gray-200">{{ trim($selectedPost['body_markdown'] ?: $selectedPost['body_plaintext']) }}</div>
                        </div>

                        <!-- AI Guard Analysis Box -->
                        @if (!empty($selectedPost['ai_alert']) || !empty($selectedPost['ai_risk_score']))
                            <div class="rounded-lg border border-rose-200 bg-rose-50/60 p-3.5 text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/20 dark:text-rose-200 space-y-2 text-left">
                                <div class="flex items-center gap-2 font-semibold text-rose-700 dark:text-rose-400">
                                    <x-filament::icon icon="heroicon-o-shield-exclamation" class="h-4 w-4" />
                                    <span>Phát hiện từ AI Guard</span>
                                </div>
                                <p class="text-xs text-rose-800 dark:text-rose-300">
                                    {{ $selectedPost['ai_alert'] ?? 'Phát hiện dấu hiệu bất thường.' }}
                                </p>
                                @if (isset($selectedPost['reports']))
                                    @foreach ($selectedPost['reports'] as $rep)
                                        @if (data_get($rep, 'source') === 'ai')
                                            <div class="rounded-md border border-rose-200 bg-white/70 p-2 text-[11px] dark:border-rose-900/30 dark:bg-black/20">
                                                <div><strong>Chi tiết:</strong> {{ data_get($rep, 'report_detail') }}</div>
                                                <div class="mt-1 text-gray-500">Mức độ rủi ro: <span class="font-mono font-semibold text-rose-600">{{ round((float) (data_get($rep, 'risk_score') ?? 0.8) * 100) }}%</span></div>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        @endif

                        <!-- User Reports Section -->
                        @if (!empty($selectedPost['reports']))
                            @php
                                $userReps = collect($selectedPost['reports'])->filter(fn($r) => data_get($r, 'source') === 'user');
                            @endphp
                            @if ($userReps->count() > 0)
                                <div class="space-y-2 text-left">
                                    <h4 class="flex items-center gap-1.5 font-semibold text-gray-900 dark:text-white">
                                        <x-filament::icon icon="heroicon-o-flag" class="h-4 w-4 text-amber-500" />
                                        <span>Báo cáo từ người dùng ({{ $userReps->count() }} lượt)</span>
                                    </h4>
                                    <div class="space-y-1.5">
                                        @foreach ($userReps as $uRep)
                                            <div class="flex items-start justify-between rounded-lg border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-800">
                                                <div>
                                                    <span class="font-medium text-gray-900 dark:text-white">Lý do: {{ data_get($uRep, 'report_reason_code') }}</span>
                                                    @if (!empty(data_get($uRep, 'report_detail')))
                                                        <p class="mt-0.5 text-gray-600 dark:text-gray-400">{{ data_get($uRep, 'report_detail') }}</p>
                                                    @endif
                                                    <span class="mt-1 block text-[10px] text-gray-400">{{ \Carbon\Carbon::parse(data_get($uRep, 'created_at'))->format('d/m/Y H:i') }}</span>
                                                </div>
                                                <x-filament::badge :color="data_get($uRep, 'status') === 'resolved' ? 'success' : 'warning'">
                                                    {{ data_get($uRep, 'status') === 'resolved' ? 'Đã xử lý' : 'Đang mở' }}
                                                </x-filament::badge>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 bg-gray-50/50 p-4 dark:border-white/10 dark:bg-gray-900">
                        <div class="flex items-center gap-2">
                            <x-filament::button size="sm" color="gray" wire:click="openAction('{{ $selectedPost['id'] }}', '{{ !empty($selectedPost['comments_locked_at']) ? 'unlock_comments' : 'lock_comments' }}')">
                                {{ !empty($selectedPost['comments_locked_at']) ? 'Mở khóa bình luận' : 'Khóa bình luận' }}
                            </x-filament::button>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($selectedPost['publication_status'] === 'hidden' || $selectedPost['publication_status'] === 'removed')
                                <x-filament::button size="sm" color="success" wire:click="quickRestore('{{ $selectedPost['id'] }}')">
                                    Khôi phục
                                </x-filament::button>
                            @else
                                <x-filament::button size="sm" color="warning" wire:click="quickHide('{{ $selectedPost['id'] }}')">
                                    Tạm ẩn
                                </x-filament::button>
                            @endif

                            <x-filament::button size="sm" color="primary" wire:click="quickApprove('{{ $selectedPost['id'] }}')">
                                Phê duyệt an toàn
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ========================================== -->
        <!-- MODAL: HÀNH ĐỘNG KIỂM DUYỆT (CONFIRM)     -->
        <!-- ========================================== -->
        @if ($showActionModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/50 backdrop-blur-xs">
                <div class="w-full max-w-lg space-y-4 rounded-xl border border-gray-200 bg-white p-5 text-xs shadow-xl dark:border-white/10 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-white/10">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                            Xác nhận hành động kiểm duyệt
                        </h3>
                        <x-filament::icon-button icon="heroicon-o-x-mark" color="gray" wire:click="closeAction" tooltip="Đóng" aria-label="Đóng" />
                    </div>

                    <div class="space-y-3.5">
                        <label class="block">
                            <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Loại hành động</span>
                            <select wire:model.live="actionType" class="block w-full min-w-0 max-w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-xs shadow-sm outline-none transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200">
                                <option value="hide">Tạm ẩn bài viết</option>
                                <option value="remove">Gỡ bỏ bài viết khỏi hệ thống</option>
                                <option value="restore">Khôi phục bài viết về trạng thái đăng</option>
                                <option value="approve">Phê duyệt an toàn & Đóng báo cáo</option>
                                <option value="dismiss">Bác bỏ báo cáo (Không vi phạm)</option>
                                <option value="lock_comments">Khóa tính năng bình luận</option>
                                <option value="unlock_comments">Mở khóa tính năng bình luận</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Mã lý do</span>
                            <select wire:model="actionReason" class="block w-full min-w-0 max-w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-xs shadow-sm outline-none transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200">
                                <option value="violates_policy">Vi phạm điều khoản / chính sách cộng đồng</option>
                                <option value="sensitive_content">Chứa nội dung nhạy cảm / không phù hợp</option>
                                <option value="secret_exposure">Lộ thông tin bảo mật / API Key / Mật khẩu</option>
                                <option value="harassment">Quấy rối / Công kích cá nhân</option>
                                <option value="spam">Spam / Cờ bạc / Lừa đảo</option>
                                <option value="false_positive">Báo cáo sai / Đã xác minh an toàn</option>
                                <option value="resolved_safe">Đã khắc phục / Phục hồi</option>
                                <option value="moderation_review">Kiểm duyệt định kỳ</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Ghi chú quyết định kiểm duyệt</span>
                            <textarea wire:model="actionNote" rows="3" placeholder="Nhập lý do chi tiết hoặc hướng dẫn bổ sung..."
                                class="block w-full min-w-0 max-w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-xs shadow-sm outline-none transition focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200"></textarea>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-3 dark:border-white/10">
                        <x-filament::button color="gray" wire:click="closeAction">
                            Hủy bỏ
                        </x-filament::button>
                        <x-filament::button color="primary" wire:click="submitAction">
                            Xác nhận thực hiện
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
