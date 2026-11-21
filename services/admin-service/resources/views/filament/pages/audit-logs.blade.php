<x-filament-panels::page>
    {{-- 4 KPI STATS CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tổng sự kiện</span>
                <span class="p-1.5 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400">
                    <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="w-5 h-5" />
                </span>
            </div>
            <div class="mt-2 text-2xl font-black tracking-tight text-gray-900 dark:text-white font-mono">
                {{ number_format($summary['total'] ?? $totalLogs) }}
            </div>
            <span class="text-[11px] text-gray-400 dark:text-gray-500">Toàn bộ lịch sử hệ thống</span>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">Nguy cơ cao (High)</span>
                <span class="p-1.5 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400">
                    <x-filament::icon icon="heroicon-o-shield-exclamation" class="w-5 h-5" />
                </span>
            </div>
            <div class="mt-2 text-2xl font-black tracking-tight text-rose-600 dark:text-rose-400 font-mono">
                {{ number_format($summary['high_risk'] ?? 0) }}
            </div>
            <span class="text-[11px] text-rose-500/80">Cần ưu tiên rà soát</span>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Rủi ro trung bình</span>
                <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5" />
                </span>
            </div>
            <div class="mt-2 text-2xl font-black tracking-tight text-amber-600 dark:text-amber-400 font-mono">
                {{ number_format($summary['medium_risk'] ?? 0) }}
            </div>
            <span class="text-[11px] text-amber-500/80">Thay đổi cấu hình & quyền</span>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Ghi nhận hôm nay</span>
                <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5" />
                </span>
            </div>
            <div class="mt-2 text-2xl font-black tracking-tight text-emerald-600 dark:text-emerald-400 font-mono">
                {{ number_format($summary['today'] ?? 0) }}
            </div>
            <span class="text-[11px] text-emerald-500/80">Đang hoạt động ổn định</span>
        </div>
    </div>

    {{-- MAIN TABLE CARD --}}
    <x-filament::section class="admin-table-card">
        {{-- TABLE TOOLBAR --}}
        <div class="admin-table-toolbar flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 dark:border-white/10 pb-4">
            {{-- SEARCH BAR --}}
            <x-filament::input.wrapper class="w-full sm:max-w-md" prefix-icon="heroicon-o-magnifying-glass">
                <x-filament::input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Tìm theo hành động, tác tử, email, IP, mô-đun..."
                    aria-label="Tìm kiếm audit log"
                />
            </x-filament::input.wrapper>

            {{-- TOOLBAR ACTIONS --}}
            <div class="flex items-center gap-2">
                {{-- QUICK MODULE TABS --}}
                <div class="hidden xl:flex items-center gap-1 rounded-lg bg-gray-100 p-1 dark:bg-white/5">
                    @foreach (['all' => 'Tất cả', 'identity' => 'Identity', 'social' => 'Social', 'learning' => 'Learning', 'ai' => 'AI Engine'] as $modKey => $modLabel)
                        <button
                            type="button"
                            wire:click="$set('moduleFilter', '{{ $modKey }}')"
                            class="px-2.5 py-1 text-xs font-semibold rounded-md transition cursor-pointer {{ $moduleFilter === $modKey ? 'bg-white shadow-xs text-primary-600 dark:bg-gray-800 dark:text-primary-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
                        >
                            {{ $modLabel }}
                        </button>
                    @endforeach
                </div>

                {{-- FILTERS DROPDOWN --}}
                <div class="relative" x-data x-on:click.outside="$wire.openMenu === 'filters' && $wire.closeMenu()" x-on:keydown.escape.window="$wire.closeMenu()">
                    <x-filament::icon-button
                        icon="heroicon-o-funnel"
                        :color="$openMenu === 'filters' || $moduleFilter !== 'all' || $riskFilter !== 'all' || $statusFilter !== 'all' || $dateFrom || $dateTo ? 'warning' : 'gray'"
                        wire:click="toggleMenu('filters')"
                        tooltip="Bộ lọc nâng cao"
                        aria-label="Bộ lọc"
                    />
                    @if ($moduleFilter !== 'all' || $riskFilter !== 'all' || $statusFilter !== 'all' || $dateFrom || $dateTo)
                        <span class="absolute -right-1 -top-1 w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    @endif

                    @if ($openMenu === 'filters')
                        <div class="absolute right-0 z-30 mt-2 w-80 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-gray-900">
                            <div class="mb-3 flex items-center justify-between border-b border-gray-100 pb-2 dark:border-white/10">
                                <span class="font-bold text-xs uppercase tracking-wide text-gray-700 dark:text-gray-200">Bộ lọc kiểm toán</span>
                                <button type="button" wire:click="resetFilters" class="text-xs font-semibold text-danger-600 hover:underline">
                                    Đặt lại
                                </button>
                            </div>

                            <div class="space-y-3 text-xs">
                                <label class="block">
                                    <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Phân hệ / Mô-đun</span>
                                    <select wire:model.live="moduleFilter" class="w-full rounded-lg border-gray-300 bg-white px-2.5 py-1.5 text-xs shadow-xs focus:border-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                                        <option value="all">Tất cả các mô-đun</option>
                                        <option value="identity">Identity & Phân quyền (Auth/Users/Roles)</option>
                                        <option value="social">Mạng xã hội (Posts/Moderation)</option>
                                        <option value="learning">Đào tạo & Tri thức (Courses/Library/Q&A)</option>
                                        <option value="ai">AI Vector Engine & Trợ lý</option>
                                        <option value="system">Cấu hình Hệ thống (System Settings)</option>
                                        <option value="notification">Thông báo & Broadcast</option>
                                    </select>
                                </label>

                                <label class="block">
                                    <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Mức độ rủi ro</span>
                                    <select wire:model.live="riskFilter" class="w-full rounded-lg border-gray-300 bg-white px-2.5 py-1.5 text-xs shadow-xs focus:border-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                                        <option value="all">Tất cả mức độ</option>
                                        <option value="normal">Bình thường (Normal)</option>
                                        <option value="medium">Trung bình (Medium)</option>
                                        <option value="high">Nguy cơ cao (High Risk)</option>
                                    </select>
                                </label>

                                <label class="block">
                                    <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Trạng thái</span>
                                    <select wire:model.live="statusFilter" class="w-full rounded-lg border-gray-300 bg-white px-2.5 py-1.5 text-xs shadow-xs focus:border-primary-500 dark:border-white/10 dark:bg-gray-800 dark:text-white">
                                        <option value="all">Tất cả trạng thái</option>
                                        <option value="success">Thành công (Success)</option>
                                        <option value="failure">Thất bại (Failure)</option>
                                        <option value="blocked">Bị ngăn chặn (Blocked)</option>
                                    </select>
                                </label>

                                <div class="grid grid-cols-2 gap-2">
                                    <label class="block">
                                        <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Từ ngày</span>
                                        <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border-gray-300 bg-white px-2 py-1.5 text-xs shadow-xs dark:border-white/10 dark:bg-gray-800 dark:text-white">
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Đến ngày</span>
                                        <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border-gray-300 bg-white px-2 py-1.5 text-xs shadow-xs dark:border-white/10 dark:bg-gray-800 dark:text-white">
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- COLUMNS SELECTOR --}}
                <div class="relative" x-data x-on:click.outside="$wire.openMenu === 'columns' && $wire.closeMenu()" x-on:keydown.escape.window="$wire.closeMenu()">
                    <x-filament::icon-button
                        icon="heroicon-o-view-columns"
                        :color="$openMenu === 'columns' ? 'primary' : 'gray'"
                        wire:click="toggleMenu('columns')"
                        tooltip="Tuỳ chỉnh cột"
                        aria-label="Chọn cột"
                    />
                    @if ($openMenu === 'columns')
                        <div class="absolute right-0 z-30 mt-2 w-56 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-white/10 dark:bg-gray-900">
                            <span class="mb-2 block font-bold text-xs uppercase text-gray-700 dark:text-gray-200">Hiển thị cột</span>
                            <div class="space-y-1.5 text-xs">
                                @foreach ([
                                    'time' => 'Thời gian',
                                    'actor' => 'Tác tử (Actor)',
                                    'action' => 'Hành động',
                                    'module' => 'Mô-đun ảnh hưởng',
                                    'ip_device' => 'IP & Thiết bị',
                                    'risk' => 'Mức độ rủi ro',
                                ] as $colKey => $colName)
                                    <label class="flex items-center gap-2 py-1 text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input type="checkbox" wire:model.live="visibleColumns.{{ $colKey }}" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <span>{{ $colName }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- REFRESH BUTTON --}}
                <x-filament::icon-button
                    icon="heroicon-o-arrow-path"
                    color="gray"
                    wire:click="refreshLogs"
                    tooltip="Làm mới dữ liệu"
                    aria-label="Làm mới"
                />
            </div>
        </div>

        {{-- TABLE LIST --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[70rem] divide-y divide-gray-200 text-left text-xs whitespace-nowrap dark:divide-white/10">
                <thead class="bg-gray-50 uppercase tracking-wider font-bold text-[11px] text-gray-500 dark:bg-white/5 dark:text-gray-400">
                    <tr>
                        @if ($visibleColumns['time'])
                            <th class="px-4 py-3.5">Thời gian</th>
                        @endif
                        @if ($visibleColumns['actor'])
                            <th class="px-4 py-3.5">Tác tử (User / System)</th>
                        @endif
                        @if ($visibleColumns['action'])
                            <th class="px-4 py-3.5">Hành động & Chi tiết</th>
                        @endif
                        @if ($visibleColumns['module'])
                            <th class="px-4 py-3.5">Mô-đun ảnh hưởng</th>
                        @endif
                        @if ($visibleColumns['ip_device'])
                            <th class="px-4 py-3.5">Địa chỉ IP & Thiết bị</th>
                        @endif
                        @if ($visibleColumns['risk'])
                            <th class="px-4 py-3.5">Mức độ rủi ro</th>
                        @endif
                        <th class="px-4 py-3.5 text-right">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($logs as $log)
                        @php
                            $actionType = $log['action'] ?? 'UNKNOWN';
                            $risk = $log['risk_level'] ?? 'normal';
                            $module = $log['module'] ?? 'system';
                            $payload = $log['payload'] ?? [];
                            $desc = $payload['description'] ?? $payload['note'] ?? $payload['detail'] ?? null;
                            $createdAt = isset($log['created_at']) ? \Carbon\Carbon::parse($log['created_at']) : null;
                        @endphp
                        <tr class="hover:bg-gray-50/70 transition dark:hover:bg-white/[0.02]">
                            {{-- THỜI GIAN --}}
                            @if ($visibleColumns['time'])
                                <td class="px-4 py-3.5 font-mono text-gray-600 dark:text-gray-400">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ $createdAt ? $createdAt->format('d/m/Y H:i:s') : 'N/A' }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        {{ $createdAt ? $createdAt->diffForHumans() : '' }}
                                    </div>
                                </td>
                            @endif

                            {{-- TÁC TỬ --}}
                            @if ($visibleColumns['actor'])
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-[11px] {{ str_contains(strtolower($log['actor_name'] ?? ''), 'system') || str_contains(strtolower($log['actor_name'] ?? ''), 'ai') ? 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300' : 'bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300' }}">
                                            @if (str_contains(strtolower($log['actor_name'] ?? ''), 'system') || str_contains(strtolower($log['actor_name'] ?? ''), 'ai'))
                                                <x-filament::icon icon="heroicon-o-cpu-chip" class="w-4 h-4" />
                                            @else
                                                {{ mb_substr($log['actor_name'] ?? 'U', 0, 1) }}
                                            @endif
                                        </div>
                                        <div>
                                            <span class="font-bold text-gray-900 dark:text-white block leading-tight">
                                                {{ $log['actor_name'] ?: 'Hệ thống' }}
                                            </span>
                                            @if (!empty($log['actor_email']))
                                                <span class="text-[10px] text-gray-400 block font-mono">
                                                    {{ $log['actor_email'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            @endif

                            {{-- HÀNH ĐỘNG --}}
                            @if ($visibleColumns['action'])
                                <td class="px-4 py-3.5 max-w-sm">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        @if (in_array($actionType, ['AUTO_BLOCK_POST', 'ACCOUNT_LOCKED', 'REMOVE_POST']))
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold tracking-wide uppercase bg-rose-500/10 text-rose-600 border border-rose-500/20 dark:bg-rose-500/20 dark:text-rose-400">
                                                {{ $actionType }}
                                            </span>
                                        @elseif (in_array($actionType, ['UPDATE_CONFIG', 'HIDE_POST', 'SYNC_PERMISSIONS', 'UPDATE_PASSWORD']))
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold tracking-wide uppercase bg-amber-500/10 text-amber-600 border border-amber-500/20 dark:bg-amber-500/20 dark:text-amber-400">
                                                {{ $actionType }}
                                            </span>
                                        @elseif (in_array($actionType, ['CREATE_USER', 'PUBLISH_COURSE', 'APPROVE_DOCUMENT', 'APPROVE_POST']))
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold tracking-wide uppercase bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 dark:bg-emerald-500/20 dark:text-emerald-400">
                                                {{ $actionType }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold tracking-wide uppercase bg-blue-500/10 text-blue-600 border border-blue-500/20 dark:bg-blue-500/20 dark:text-blue-400">
                                                {{ $actionType }}
                                            </span>
                                        @endif

                                        @if ($desc)
                                            <span class="text-gray-700 dark:text-gray-300 font-medium truncate max-w-xs" title="{{ $desc }}">
                                                {{ $desc }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 font-mono text-[11px]">
                                                {{ $log['event_type'] }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            @endif

                            {{-- MÔ-ĐUN ẢNH HƯỞNG --}}
                            @if ($visibleColumns['module'])
                                <td class="px-4 py-3.5">
                                    @php
                                        $modColors = [
                                            'identity' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400',
                                            'social' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-500/10 dark:text-sky-400',
                                            'learning' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400',
                                            'ai' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400',
                                            'system' => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-300',
                                            'notification' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400',
                                        ];
                                        $color = $modColors[$module] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
                                        $labels = [
                                            'identity' => 'Identity & Auth',
                                            'social' => 'Post Moderation',
                                            'learning' => 'Learning & Library',
                                            'ai' => 'AI Vector Engine',
                                            'system' => 'System Settings',
                                            'notification' => 'Broadcast / Alert',
                                        ];
                                    @endphp
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $color }}">
                                        {{ $labels[$module] ?? strtoupper($module) }}
                                    </span>
                                </td>
                            @endif

                            {{-- IP & THIẾT BỊ --}}
                            @if ($visibleColumns['ip_device'])
                                <td class="px-4 py-3.5 font-mono text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center gap-1.5 font-semibold text-gray-800 dark:text-gray-200">
                                        <x-filament::icon icon="heroicon-o-globe-alt" class="w-3.5 h-3.5 text-gray-400" />
                                        <span>{{ $log['ip_address'] ?: '127.0.0.1' }}</span>
                                    </div>
                                    <div class="text-[10px] text-gray-400 truncate max-w-[12rem]" title="{{ $log['user_agent'] }}">
                                        {{ $log['user_agent'] ?: 'Daemon Worker' }}
                                    </div>
                                </td>
                            @endif

                            {{-- MỨC ĐỘ RỦI RO --}}
                            @if ($visibleColumns['risk'])
                                <td class="px-4 py-3.5">
                                    @if ($risk === 'high')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-rose-500/15 text-rose-600 font-bold text-[11px] border border-rose-500/30 dark:text-rose-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                            High Risk
                                        </span>
                                    @elseif ($risk === 'medium')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-500/15 text-amber-600 font-bold text-[11px] border border-amber-500/30 dark:text-amber-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Medium
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 font-bold text-[11px] border border-emerald-500/30 dark:text-emerald-400">
                                            Normal
                                        </span>
                                    @endif
                                </td>
                            @endif

                            {{-- ACTIONS --}}
                            <td class="px-4 py-3.5 text-right">
                                <x-filament::button
                                    size="xs"
                                    color="gray"
                                    icon="heroicon-o-eye"
                                    wire:click="openDetail('{{ $log['id'] }}')"
                                    tooltip="Xem chi tiết payload"
                                >
                                    Xem
                                </x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <x-filament::icon icon="heroicon-o-inbox" class="w-8 h-8 text-gray-300 dark:text-gray-600" />
                                    <span class="font-medium">Không tìm thấy nhật ký kiểm toán phù hợp.</span>
                                    <button type="button" wire:click="resetFilters" class="text-xs text-primary-600 hover:underline">
                                        Xóa các bộ lọc để xem lại toàn bộ
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION BAR --}}
        @if ($lastPage > 1)
            <div class="flex items-center justify-between border-t border-gray-200 dark:border-white/10 px-4 py-3 text-xs text-gray-500">
                <div>
                    Hiển thị trang <span class="font-bold text-gray-900 dark:text-white">{{ $pageNumber }}</span> / <span class="font-bold">{{ $lastPage }}</span> (Tổng {{ $totalLogs }} bản ghi)
                </div>
                <div class="flex items-center gap-1">
                    <x-filament::button
                        size="xs"
                        color="gray"
                        wire:click="goToPage({{ $pageNumber - 1 }})"
                        :disabled="$pageNumber <= 1"
                    >
                        Trước
                    </x-filament::button>

                    @for ($p = max(1, $pageNumber - 2); $p <= min($lastPage, $pageNumber + 2); $p++)
                        <button
                            type="button"
                            wire:click="goToPage({{ $p }})"
                            class="w-7 h-7 rounded text-xs font-semibold transition cursor-pointer {{ $pageNumber === $p ? 'bg-primary-600 text-white' : 'hover:bg-gray-100 text-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                        >
                            {{ $p }}
                        </button>
                    @endfor

                    <x-filament::button
                        size="xs"
                        color="gray"
                        wire:click="goToPage({{ $pageNumber + 1 }})"
                        :disabled="$pageNumber >= $lastPage"
                    >
                        Tiếp
                    </x-filament::button>
                </div>
            </div>
        @endif
    </x-filament::section>

    {{-- DETAIL MODAL / DRAWER --}}
    @if ($showDetailModal && $selectedLog)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs" wire:click.self="closeDetail" x-on:keydown.escape.window="$wire.closeDetail()">
            <div class="relative w-full max-w-2xl max-h-[90vh] flex flex-col rounded-2xl bg-white shadow-2xl border border-gray-200 overflow-hidden dark:bg-gray-900 dark:border-white/10">
                {{-- MODAL HEADER --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-gray-800/50">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-primary-500/10 text-primary-600 dark:text-primary-400">
                            <x-filament::icon icon="heroicon-o-document-text" class="w-5 h-5" />
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Chi tiết Nhật ký Kiểm toán</h3>
                            <span class="text-[11px] font-mono text-gray-400">ID: {{ $selectedLog['id'] }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="closeDetail"
                        class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 dark:hover:text-white transition"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                    </button>
                </div>

                {{-- MODAL BODY --}}
                <div class="p-6 space-y-4 overflow-y-auto text-xs">
                    {{-- METADATA GRID --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-3.5 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400">Thời gian</span>
                            <span class="block font-semibold text-gray-900 dark:text-white mt-0.5">
                                {{ isset($selectedLog['created_at']) ? \Carbon\Carbon::parse($selectedLog['created_at'])->format('d/m/Y H:i:s') : 'N/A' }}
                            </span>
                        </div>

                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400">Tác tử (Actor)</span>
                            <span class="block font-semibold text-gray-900 dark:text-white mt-0.5">
                                {{ $selectedLog['actor_name'] ?: 'Hệ thống' }}
                            </span>
                        </div>

                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400">Mô-đun</span>
                            <span class="block font-semibold text-gray-900 dark:text-white mt-0.5 uppercase">
                                {{ $selectedLog['module'] ?? 'system' }}
                            </span>
                        </div>

                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400">Hành động</span>
                            <span class="block font-semibold text-primary-600 dark:text-primary-400 mt-0.5">
                                {{ $selectedLog['action'] }}
                            </span>
                        </div>

                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400">Mức độ rủi ro</span>
                            <span class="block font-semibold mt-0.5 {{ ($selectedLog['risk_level'] ?? '') === 'high' ? 'text-rose-600' : (($selectedLog['risk_level'] ?? '') === 'medium' ? 'text-amber-600' : 'text-emerald-600') }}">
                                {{ ucfirst($selectedLog['risk_level'] ?? 'normal') }}
                            </span>
                        </div>

                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400">Trạng thái</span>
                            <span class="block font-semibold text-gray-900 dark:text-white mt-0.5 uppercase">
                                {{ $selectedLog['status'] ?? 'SUCCESS' }}
                            </span>
                        </div>

                        <div class="sm:col-span-3">
                            <span class="text-[10px] uppercase font-bold text-gray-400">Địa chỉ IP & Thiết bị</span>
                            <span class="block font-mono text-gray-700 dark:text-gray-300 mt-0.5 break-all">
                                {{ $selectedLog['ip_address'] ?? '127.0.0.1' }} — {{ $selectedLog['user_agent'] ?? 'N/A' }}
                            </span>
                        </div>

                        @if (!empty($selectedLog['request_id']))
                            <div class="sm:col-span-3">
                                <span class="text-[10px] uppercase font-bold text-gray-400">Correlation Request ID</span>
                                <span class="block font-mono text-gray-500 mt-0.5 text-[11px]">
                                    {{ $selectedLog['request_id'] }}
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- PAYLOAD JSON VIEWER --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="font-bold text-xs uppercase tracking-wide text-gray-700 dark:text-gray-300">Dữ liệu chi tiết (Payload JSON)</span>
                            <span class="text-[11px] text-gray-400 font-mono">application/json</span>
                        </div>
                        <pre class="p-4 rounded-xl bg-gray-900 text-emerald-400 font-mono text-xs overflow-x-auto border border-gray-800 leading-relaxed max-h-72 select-all">{{ json_encode($selectedLog['payload'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>

                {{-- MODAL FOOTER --}}
                <div class="flex items-center justify-end px-6 py-3 border-t border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-gray-800/50">
                    <x-filament::button color="gray" wire:click="closeDetail">
                        Đóng cửa sổ
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
