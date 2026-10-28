<?php

namespace App\Filament\Pages;

use App\Auth\RemoteIdentityUser;
use App\Services\IdentityGatewayClient;
use App\Services\LearningGatewayClient;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class KnowledgeDocuments extends ModulePlaceholder
{
    protected string $view = 'filament.pages.knowledge-documents';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Tài liệu tri thức';

    protected static \UnitEnum|string|null $navigationGroup = 'Nội dung học tập';

    protected static ?int $navigationSort = 21;

    protected static string $moduleDescription = 'Base quản lý tài liệu, taxonomy, trạng thái xuất bản và vòng đời nội dung.';

    protected static string $moduleOwner = 'learning-service';

    protected static array $moduleCapabilities = ['Duyệt tài liệu', 'Quản lý taxonomy', 'Xuất bản/thu hồi', 'Theo dõi phiên bản'];

    /** @var array<int, array<string, mixed>> */
    public array $documents = [];

    /** @var array<int, array<string, mixed>> */
    public array $topics = [];

    /** @var array<int, array<string, mixed>> */
    public array $categories = [];

    public ?string $documentOrgUnitId = null;

    public array $orgUnits = [];

    public array $accessRoles = [];

    public array $accessUsers = [];

    public ?string $accessDocumentId = null;

    public array $accessRules = [];

    public function editAccess(string $documentId): void
    {
        $response = app(LearningGatewayClient::class)->get("documents/{$documentId}/access-rules", $this->token());
        if ($response === null) {
            Notification::make()->title('Không có quyền quản lý truy cập tài liệu này.')->danger()->send();

            return;
        }
        $identity = app(IdentityGatewayClient::class);
        $this->accessRoles = $identity->get('roles', $this->token())['data'] ?? [];
        $this->accessUsers = $identity->get('users?per_page=100', $this->token())['data'] ?? [];
        $this->accessDocumentId = $documentId;
        $this->accessRules = array_map(fn ($rule) => array_intersect_key($rule, array_flip(['principal_type', 'principal_id', 'access_level', 'effect', 'starts_at', 'ends_at'])), $response['data'] ?? []);
    }

    public function addAccessRule(): void
    {
        $this->accessRules[] = ['principal_type' => 'department', 'principal_id' => null, 'access_level' => 'view', 'effect' => 'allow', 'starts_at' => null, 'ends_at' => null];
    }

    public function removeAccessRule(int $index): void
    {
        unset($this->accessRules[$index]);
        $this->accessRules = array_values($this->accessRules);
    }

    public function saveAccessRules(): void
    {
        if (! $this->accessDocumentId) {
            return;
        }
        foreach ($this->accessRules as &$rule) {
            if ($rule['principal_type'] === 'organization') {
                $rule['principal_id'] = null;
            }
        }
        unset($rule);
        $response = app(LearningGatewayClient::class)->send('PUT', "documents/{$this->accessDocumentId}/access-rules", $this->token(), ['rules' => $this->accessRules]);
        Notification::make()->title($response === null ? 'Không thể lưu; kiểm tra đối tượng, thời hạn và quyền quản lý.' : 'Đã lưu quyền truy cập tài liệu.')->{$response === null ? 'danger' : 'success'}()->send();
        if ($response !== null) {
            $this->accessDocumentId = null;
            $this->refreshDocuments();
        }
    }

    public string $documentTitle = '';

    public ?string $topicId = null;

    public ?string $categoryId = null;

    public ?string $summary = null;

    public ?string $uploadDocumentId = null;

    public string $newTopicCode = '';

    public string $newTopicName = '';

    public string $newCategoryCode = '';

    public string $newCategoryName = '';

    public function mount(): void
    {
        $this->refreshDocuments();
        $this->refreshTaxonomy();
        $this->orgUnits = app(IdentityGatewayClient::class)->get('org-units', $this->token())['data'] ?? [];
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof RemoteIdentityUser && $user->can('learning.documents.read');
    }

    public function updatedSearch(): void
    {
        $this->refreshDocuments();
    }

    public function updatedStatus(): void
    {
        $this->refreshDocuments();
    }

    public function save(): void
    {
        $this->validate(['documentTitle' => ['required', 'string', 'max:500'], 'topicId' => ['required', 'uuid'], 'categoryId' => ['nullable', 'uuid'], 'summary' => ['nullable', 'string', 'max:5000']]);
        $response = app(LearningGatewayClient::class)->send('POST', 'documents', $this->token(), ['title' => $this->documentTitle, 'topic_id' => $this->topicId, 'category_id' => $this->categoryId, 'summary' => $this->summary, 'org_unit_id' => $this->documentOrgUnitId]);
        if ($response === null) {
            Notification::make()->title('Không thể tạo tài liệu.')->danger()->send();

            return;
        }
        $this->reset('documentTitle', 'topicId', 'categoryId', 'summary', 'documentOrgUnitId');
        $this->refreshDocuments();
        Notification::make()->title('Đã tạo bản nháp tài liệu.')->success()->send();
    }

    public function saveTaxonomy(): void
    {
        $this->validate(['newTopicCode' => ['required', 'regex:/^[a-z0-9][a-z0-9_-]*$/', 'max:80'], 'newTopicName' => ['required', 'string', 'max:255']]);
        $topic = app(LearningGatewayClient::class)->send('POST', 'topics', $this->token(), ['code' => $this->newTopicCode, 'name' => $this->newTopicName]);
        if ($topic === null) {
            Notification::make()->title('Không thể tạo topic.')->danger()->send();

            return;
        }
        if ($this->newCategoryCode !== '' || $this->newCategoryName !== '') {
            $this->validate(['newCategoryCode' => ['required', 'regex:/^[a-z0-9][a-z0-9_-]*$/', 'max:80'], 'newCategoryName' => ['required', 'string', 'max:255']]);
            app(LearningGatewayClient::class)->send('POST', 'categories', $this->token(), ['topic_id' => $topic['data']['id'], 'code' => $this->newCategoryCode, 'name' => $this->newCategoryName]);
        }
        $this->reset('newTopicCode', 'newTopicName', 'newCategoryCode', 'newCategoryName');
        $this->refreshTaxonomy();
        Notification::make()->title('Đã tạo taxonomy.')->success()->send();
    }

    public function updateLifecycle(string $documentId, string $action): void
    {
        $response = app(LearningGatewayClient::class)->send('POST', "documents/{$documentId}/lifecycle", $this->token(), ['action' => $action]);
        Notification::make()->title($response === null ? 'Không thể cập nhật tài liệu.' : 'Đã cập nhật vòng đời tài liệu.')->{$response === null ? 'danger' : 'success'}()->send();
        $this->refreshDocuments();
    }

    public function reviewDocument(string $documentId, string $action): void
    {
        $response = app(LearningGatewayClient::class)->send('POST', "documents/{$documentId}/review", $this->token(), ['action' => $action]);
        Notification::make()->title($response === null ? 'Không thể cập nhật trạng thái duyệt.' : 'Đã cập nhật trạng thái duyệt.')->{$response === null ? 'danger' : 'success'}()->send();
        $this->refreshDocuments();
    }

    public function hasDocumentPermission(string $permission): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof RemoteIdentityUser && $user->can($permission);
    }

    public function resetFilters(): void
    {
        parent::resetFilters();
        $this->refreshDocuments();
    }

    /** @return array<int, array<string, mixed>> */
    public function categoriesForSelectedTopic(): array
    {
        return array_values(array_filter($this->categories, fn (array $category): bool => $category['topic_id'] === $this->topicId));
    }

    private function refreshDocuments(): void
    {
        $this->documents = app(LearningGatewayClient::class)->get('documents', $this->token())['data'] ?? [];
        if ($this->status !== 'all') {
            $this->documents = array_values(array_filter($this->documents, fn (array $document): bool => $document['document_status'] === $this->status));
        }
        if ($this->search !== '') {
            $this->documents = array_values(array_filter($this->documents, fn (array $document): bool => str_contains(mb_strtolower($document['title']), mb_strtolower($this->search))));
        }
    }

    private function refreshTaxonomy(): void
    {
        if (! $this->hasDocumentPermission('learning.taxonomy.manage')) {
            $this->topics = app(LearningGatewayClient::class)->topics($this->token())['data'] ?? [];
            $this->categories = collect($this->topics)->pluck('categories')->flatten(1)->all();

            return;
        }
        $taxonomy = app(LearningGatewayClient::class)->get('taxonomy', $this->token())['data'] ?? [];
        $this->topics = $taxonomy['topics'] ?? [];
        $this->categories = $taxonomy['categories'] ?? [];
    }

    private function token(): string
    {
        return (string) session('admin.identity_token');
    }
}
