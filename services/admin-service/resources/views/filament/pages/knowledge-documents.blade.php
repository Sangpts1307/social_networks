<x-filament-panels::page>
    @if ($this->hasDocumentPermission('learning.taxonomy.manage'))
    <x-filament::section heading="Taxonomy tri thức" description="Tạo topic và category nền tảng trước khi tạo tài liệu hoặc khóa học.">
        <div class="grid gap-3 lg:grid-cols-4"><label class="grid gap-2 text-sm font-medium">Mã topic <x-filament::input wire:model="newTopicCode" placeholder="engineering" /></label><label class="grid gap-2 text-sm font-medium">Tên topic <x-filament::input wire:model="newTopicName" placeholder="Kỹ thuật" /></label><label class="grid gap-2 text-sm font-medium">Mã category <x-filament::input wire:model="newCategoryCode" placeholder="backend" /></label><label class="grid gap-2 text-sm font-medium">Tên category <x-filament::input wire:model="newCategoryName" placeholder="Backend" /></label></div>
        <div class="mt-4"><x-filament::button wire:click="saveTaxonomy" icon="heroicon-o-plus">Tạo taxonomy</x-filament::button></div>
    </x-filament::section>
    @endif
    @if ($this->hasDocumentPermission('learning.documents.create'))
    <x-filament::section heading="Tạo tài liệu" description="Tạo metadata và phiên bản nháp đầu tiên. Tệp PDF, DOCX hoặc XLSX được lưu private trong MinIO.">
        <div class="grid gap-3 lg:grid-cols-2">
            <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">Tiêu đề <x-filament::input wire:model="documentTitle" placeholder="Tên tài liệu" /></label>
            <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">Topic <select wire:model.live="topicId"><option value="">Chọn topic</option>@foreach ($topics as $topic)<option value="{{ $topic['id'] }}">{{ $topic['name'] }} · {{ $topic['code'] }}</option>@endforeach</select></label>
            <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">Category <select wire:model="categoryId"><option value="">Không chọn</option>@foreach ($this->categoriesForSelectedTopic() as $category)<option value="{{ $category['id'] }}">{{ $category['name'] }} · {{ $category['code'] }}</option>@endforeach</select></label>
            <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">Phòng ban sở hữu <select wire:model="documentOrgUnitId"><option value="">Không gắn phòng ban</option>@foreach ($orgUnits as $unit)<option value="{{ $unit['id'] }}">{{ $unit['name'] }}</option>@endforeach</select></label>
            <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">Tóm tắt <x-filament::input wire:model="summary" placeholder="Tóm tắt ngắn" /></label>
        </div>
        <div class="mt-4"><x-filament::button wire:click="save" icon="heroicon-o-plus">Tạo bản nháp</x-filament::button></div>
    </x-filament::section>
    @endif

    @if ($this->hasDocumentPermission('learning.documents.upload'))
    <x-filament::section heading="Tải tệp lên" description="Tệp được gắn vào phiên bản nháp hiện tại. Khi đã có tệp, lần tải tiếp theo tạo phiên bản mới bất biến.">
        @if (session('knowledge_documents_upload_error'))<p class="mb-4 text-sm text-danger-600">{{ session('knowledge_documents_upload_error') }}</p>@endif
        @if (session('knowledge_documents_upload_success'))<p class="mb-4 text-sm text-success-600">{{ session('knowledge_documents_upload_success') }}</p>@endif
        <form method="POST" action="{{ route('admin.knowledge-documents.upload') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid gap-3 lg:grid-cols-3">
                <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white">Tài liệu
                    <select name="document_id" required><option value="">Chọn tài liệu</option>@foreach ($documents as $document)<option value="{{ $document['id'] }}">{{ $document['title'] }}</option>@endforeach</select>
                    @error('document_id')<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                </label>
                <label class="grid gap-2 text-sm font-medium text-gray-950 dark:text-white lg:col-span-2">Tệp gốc (PDF, DOCX hoặc XLSX; tối đa 50 MB)
                    <input name="document_file" type="file" required accept=".pdf,.docx,.xlsx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" />
                    @error('document_file')<span class="text-sm text-danger-600">{{ $message }}</span>@enderror
                </label>
            </div>
            <div class="mt-4"><x-filament::button type="submit" icon="heroicon-o-arrow-up-tray">Tải tệp lên</x-filament::button></div>
        </form>
    </x-filament::section>
    @endif

    <x-filament::section heading="Bộ lọc tài liệu" description="Quản lý metadata, taxonomy và vòng đời xuất bản. File binary vẫn thuộc object storage của Learning service.">
        <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-end">
            <label class="grid w-full gap-2 text-sm font-medium text-gray-950 dark:text-white lg:max-w-md">
                Tìm kiếm
                <x-filament::input wire:model.live.debounce.300ms="search" placeholder="Tên tài liệu hoặc mã tài liệu" />
            </label>
            <label class="grid w-full gap-2 text-sm font-medium text-gray-950 dark:text-white sm:max-w-56">
                Trạng thái
                <select wire:model.live="status">
                    <option value="all">Tất cả</option>
                    <option value="draft">Bản nháp</option>
                    <option value="published">Đã xuất bản</option>
                    <option value="archived">Đã lưu trữ</option>
                </select>
            </label>
            <label class="grid w-full gap-2 text-sm font-medium text-gray-950 dark:text-white sm:max-w-56">
                Phạm vi
                <select wire:model.live="scope">
                    <option value="all">Toàn tổ chức</option>
                    <option value="internal">Nội bộ</option>
                </select>
            </label>
            <x-filament::button color="gray" wire:click="resetFilters" icon="heroicon-o-arrow-path">Làm mới</x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section heading="Thư viện tài liệu" description="Duyệt, xuất bản, lưu trữ và theo dõi phiên bản private trong Learning service.">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[54rem] divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-white/5 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Tài liệu</th>
                        <th class="px-4 py-3">Taxonomy</th>
                        <th class="px-4 py-3">Phiên bản</th>
                        <th class="px-4 py-3">Phạm vi</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr class="border-b border-gray-100 align-top dark:border-white/10">
                            <td class="px-4 py-3"><p class="font-medium">{{ $document['title'] }}</p><p class="mt-1 max-w-lg text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($document['summary'] ?? '', 100) }}</p></td>
                            <td class="px-4 py-3">{{ $document['topic_name'] }}<br><span class="text-xs text-gray-500">{{ $document['category_name'] ?? '—' }}</span></td>
                            <td class="px-4 py-3"><p>v{{ $document['current_version_number'] ?? '—' }}</p><p class="mt-1 text-xs text-gray-500">{{ $document['current_filename'] ?? 'Chưa có tệp' }}</p><p class="mt-1 text-xs text-gray-500">{{ $document['current_version_approval_status'] ?? '—' }} · {{ $document['current_file_scan_status'] ?? '—' }}</p></td>
                            <td class="px-4 py-3">{{ collect($orgUnits)->firstWhere('id', $document['org_unit_id'] ?? null)['name'] ?? 'Nội bộ' }}</td>
                            <td class="px-4 py-3"><x-filament::badge :color="$document['document_status'] === 'published' ? 'success' : ($document['document_status'] === 'archived' ? 'gray' : 'warning')">{{ $document['document_status'] }}</x-filament::badge></td>
                            <td class="px-4 py-3 text-right"><div class="flex flex-wrap justify-end gap-2">
                                @if ($this->hasDocumentPermission('learning.documents.access.manage'))<x-filament::button size="sm" color="gray" wire:click="editAccess('{{ $document['id'] }}')">Quyền truy cập</x-filament::button>@endif
                                @if ($document['current_file_id'] && $document['current_version_approval_status'] === 'draft' && $this->hasDocumentPermission('learning.documents.review'))<x-filament::button size="sm" color="gray" wire:click="reviewDocument('{{ $document['id'] }}', 'approve')">Duyệt</x-filament::button>@endif
                                @if ($document['current_file_id'] && $document['current_version_approval_status'] === 'approved' && $this->hasDocumentPermission('learning.documents.publish'))<x-filament::button size="sm" wire:click="updateLifecycle('{{ $document['id'] }}', 'publish')">Xuất bản</x-filament::button>@endif
                                @if ($document['document_status'] === 'published' && $this->hasDocumentPermission('learning.documents.archive'))<x-filament::button size="sm" color="gray" wire:click="updateLifecycle('{{ $document['id'] }}', 'archive')">Lưu trữ</x-filament::button>@endif
                                @if ($document['document_status'] === 'archived' && $this->hasDocumentPermission('learning.documents.archive'))<x-filament::button size="sm" color="gray" wire:click="updateLifecycle('{{ $document['id'] }}', 'restore')">Khôi phục nháp</x-filament::button>@endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-500">Chưa có tài liệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
    @if ($accessDocumentId)
    <x-filament::section heading="Quyền truy cập tài liệu" description="Quyền từ role vẫn phải phù hợp scope. Có luật Allow thì chỉ đối tượng được Allow mới truy cập; Deny được ưu tiên. Giữ một luật manage cho người quản lý để tiếp tục chỉnh quyền.">
        @foreach ($accessRules as $index => $rule)
            <div class="mb-3 flex flex-wrap items-center gap-2" wire:key="access-rule-{{ $index }}">
                <select aria-label="Hiệu lực" wire:model="accessRules.{{ $index }}.effect"><option value="allow">Cho phép</option><option value="deny">Từ chối</option></select>
                <select aria-label="Loại đối tượng" wire:model.live="accessRules.{{ $index }}.principal_type"><option value="organization">Toàn tổ chức</option><option value="department">Phòng ban</option><option value="role">Role</option><option value="user">Người dùng</option></select>
                @if ($rule['principal_type'] === 'department')
                    <select aria-label="Phòng ban được áp dụng" wire:model="accessRules.{{ $index }}.principal_id"><option value="">Chọn phòng ban</option>@foreach ($orgUnits as $unit)<option value="{{ $unit['id'] }}">{{ $unit['name'] }}</option>@endforeach</select>
                @elseif ($rule['principal_type'] === 'role')
                    <select aria-label="Vai trò được áp dụng" wire:model="accessRules.{{ $index }}.principal_id"><option value="">Chọn vai trò</option>@foreach ($accessRoles as $role)<option value="{{ $role['id'] }}">{{ $role['name'] }}</option>@endforeach</select>
                @elseif ($rule['principal_type'] === 'user')
                    <select aria-label="Người dùng được áp dụng" wire:model="accessRules.{{ $index }}.principal_id"><option value="">Chọn người dùng</option>@foreach ($accessUsers as $person)<option value="{{ $person['id'] }}">{{ $person['display_name'] }} · {{ $person['email'] }}</option>@endforeach</select>
                @endif
                <select aria-label="Mức truy cập" wire:model="accessRules.{{ $index }}.access_level"><option value="view">Chỉ xem</option><option value="download">Xem và tải</option><option value="manage">Quản lý, xem và tải</option></select>
                <input aria-label="Bắt đầu (ISO 8601 có múi giờ)" placeholder="Bắt đầu: 2026-10-19T08:00:00+07:00" wire:model="accessRules.{{ $index }}.starts_at">
                <input aria-label="Kết thúc (ISO 8601 có múi giờ)" placeholder="Kết thúc: 2026-11-19T18:00:00+07:00" wire:model="accessRules.{{ $index }}.ends_at">
                <x-filament::button color="gray" size="sm" wire:click="removeAccessRule({{ $index }})">Bỏ luật</x-filament::button>
            </div>
        @endforeach
        <p class="my-3 text-sm">Không có luật Allow: giữ phạm vi mặc định trong tổ chức. Mốc thời gian dùng ISO 8601 có múi giờ; để trống nếu không giới hạn.</p>
        <x-filament::button color="gray" wire:click="addAccessRule">Thêm luật</x-filament::button>
        <x-filament::button wire:click="saveAccessRules">Lưu quyền truy cập</x-filament::button>
    </x-filament::section>
    @endif
</x-filament-panels::page>
