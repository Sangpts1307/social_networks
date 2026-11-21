<?php

namespace App\Filament\Pages;

use App\Auth\RemoteIdentityUser;
use App\Services\IdentityGatewayClient;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogs extends ModulePlaceholder
{
    protected string $view = 'filament.pages.audit-logs';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Nhật ký kiểm toán';

    protected static \UnitEnum|string|null $navigationGroup = 'Vận hành';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Xuất báo cáo Log (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    public function getSubheading(): ?string
    {
        return 'Ghi vết toàn bộ hành vi quản trị, đăng nhập bất thường, kiểm duyệt nội dung và thay đổi dữ liệu đa phân hệ';
    }

    protected static ?int $navigationSort = 50;

    protected static string $moduleDescription = 'Trung tâm giám sát và nhật ký kiểm toán toàn hệ thống: ghi nhận đăng nhập, phân quyền, kiểm duyệt nội dung, AI scanner và cấu hình.';

    protected static string $moduleOwner = 'Hệ thống kiểm toán tập trung (Centralized Audit)';

    protected static array $moduleCapabilities = [
        'Giám sát hành vi quản trị & bảo mật',
        'Phát hiện sự cố và hành động nguy cơ cao (High Risk)',
        'Tra cứu đa chiều theo Actor, Module, IP, Thời gian',
        'Chi tiết thay đổi Payload JSON & Request Correlation ID',
        'Trích xuất báo cáo kiểm toán định dạng CSV',
    ];

    public string $search = '';

    public string $moduleFilter = 'all';

    public string $riskFilter = 'all';

    public string $statusFilter = 'all';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $pageNumber = 1;

    public int $perPage = 15;

    public int $lastPage = 1;

    public int $totalLogs = 0;

    /** @var array<int, array<string, mixed>> */
    public array $logs = [];

    /** @var array<string, int> */
    public array $summary = [
        'total' => 0,
        'high_risk' => 0,
        'medium_risk' => 0,
        'today' => 0,
        'actors_count' => 0,
    ];

    /** @var array<string, mixed>|null */
    public ?array $selectedLog = null;

    public bool $showDetailModal = false;

    public ?string $openMenu = null;

    public array $visibleColumns = [
        'time' => true,
        'actor' => true,
        'action' => true,
        'module' => true,
        'ip_device' => true,
        'risk' => true,
        'actions' => true,
    ];

    public function mount(): void
    {
        $this->refreshLogs();
    }

    public static function canAccess(): bool
    {
        return Filament::auth()->user() instanceof RemoteIdentityUser
            && Filament::auth()->user()->can('identity.audit.read');
    }

    public function getTitle(): string
    {
        return 'Nhật ký Kiểm toán Hệ thống (Audit Logs)';
    }

    public function toggleMenu(string $menu): void
    {
        $this->openMenu = $this->openMenu === $menu ? null : $menu;
    }

    public function closeMenu(): void
    {
        $this->openMenu = null;
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'moduleFilter', 'riskFilter', 'statusFilter', 'dateFrom', 'dateTo');
        $this->pageNumber = 1;
        $this->openMenu = null;
        $this->refreshLogs();
    }

    public function updatedSearch(): void
    {
        $this->pageNumber = 1;
        $this->refreshLogs();
    }

    public function updatedModuleFilter(): void
    {
        $this->pageNumber = 1;
        $this->refreshLogs();
    }

    public function updatedRiskFilter(): void
    {
        $this->pageNumber = 1;
        $this->refreshLogs();
    }

    public function updatedStatusFilter(): void
    {
        $this->pageNumber = 1;
        $this->refreshLogs();
    }

    public function updatedDateFrom(): void
    {
        $this->pageNumber = 1;
        $this->refreshLogs();
    }

    public function updatedDateTo(): void
    {
        $this->pageNumber = 1;
        $this->refreshLogs();
    }

    public function goToPage(int $page): void
    {
        $this->pageNumber = max(1, min($page, $this->lastPage));
        $this->refreshLogs();
    }

    public function refreshLogs(): void
    {
        $params = array_filter([
            'q' => $this->search ?: null,
            'module' => $this->moduleFilter !== 'all' ? $this->moduleFilter : null,
            'risk_level' => $this->riskFilter !== 'all' ? $this->riskFilter : null,
            'status' => $this->statusFilter !== 'all' ? $this->statusFilter : null,
            'date_from' => $this->dateFrom ?: null,
            'date_to' => $this->dateTo ?: null,
            'page' => $this->pageNumber,
            'per_page' => $this->perPage,
        ]);

        $queryString = http_build_query($params);
        $response = $this->client()->get('audit-logs'.($queryString ? '?'.$queryString : ''), $this->token());

        $this->logs = $response['data'] ?? [];
        $this->lastPage = max(1, (int) ($response['meta']['last_page'] ?? 1));
        $this->totalLogs = (int) ($response['meta']['total'] ?? count($this->logs));

        if (! empty($response['summary'])) {
            $this->summary = $response['summary'];
        }

        if ($this->pageNumber > $this->lastPage) {
            $this->pageNumber = $this->lastPage;
            $this->refreshLogs();
        }
    }

    public function openDetail(string $id): void
    {
        $log = collect($this->logs)->firstWhere('id', $id);
        if ($log) {
            $this->selectedLog = $log;
            $this->showDetailModal = true;
        }
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedLog = null;
    }

    public function exportCsv(): StreamedResponse
    {
        $logsToExport = $this->logs;

        return response()->streamDownload(function () use ($logsToExport) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($handle, [
                'ID',
                'Thời gian',
                'Tác tử',
                'Email',
                'Mô-đun',
                'Hành động',
                'Mã sự kiện',
                'Thực thể',
                'ID Thực thể',
                'Địa chỉ IP',
                'Thiết bị / Trình duyệt',
                'Mức độ rủi ro',
                'Trạng thái',
                'Chi tiết Payload',
            ]);

            foreach ($logsToExport as $row) {
                fputcsv($handle, [
                    $row['id'] ?? '',
                    $row['created_at'] ?? '',
                    $row['actor_name'] ?? 'Hệ thống',
                    $row['actor_email'] ?? 'N/A',
                    $row['module'] ?? 'system',
                    $row['action'] ?? '',
                    $row['event_type'] ?? '',
                    $row['entity_type'] ?? '',
                    $row['entity_id'] ?? '',
                    $row['ip_address'] ?? '127.0.0.1',
                    $row['user_agent'] ?? '',
                    $row['risk_level'] ?? 'normal',
                    $row['status'] ?? 'success',
                    json_encode($row['payload'] ?? [], JSON_UNESCAPED_UNICODE),
                ]);
            }
            fclose($handle);
        }, 'audit_logs_'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function token(): string
    {
        return (string) session('admin.identity_token');
    }

    private function client(): IdentityGatewayClient
    {
        return app(IdentityGatewayClient::class);
    }

    private function notify(bool $success, string $message): void
    {
        $notification = Notification::make()->title($message);
        $success ? $notification->success() : $notification->danger();
        $notification->send();
    }
}
