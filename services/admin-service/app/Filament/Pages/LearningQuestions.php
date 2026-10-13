<?php

namespace App\Filament\Pages;

use App\Auth\RemoteIdentityUser;
use App\Services\LearningGatewayClient;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class LearningQuestions extends ModulePlaceholder
{
    protected string $view = 'filament.pages.learning-questions';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $navigationLabel = 'Hỏi đáp';

    protected static \UnitEnum|string|null $navigationGroup = 'Nội dung học tập';

    protected static ?int $navigationSort = 20;

    protected static string $moduleDescription = 'Base quản trị câu hỏi, câu trả lời và luồng kiểm duyệt nội dung học tập.';

    protected static string $moduleOwner = 'learning-service';

    protected static array $moduleCapabilities = ['Danh sách câu hỏi/câu trả lời', 'Ẩn/khôi phục nội dung', 'Duyệt câu trả lời nổi bật', 'Lịch sử moderation'];

    /** @var array<int, array<string, mixed>> */
    public array $questions = [];

    public function mount(): void
    {
        $this->refreshQuestions();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof RemoteIdentityUser && $user->can('learning.questions.moderate');
    }

    public function updatedSearch(): void
    {
        $this->refreshQuestions();
    }

    public function updatedStatus(): void
    {
        $this->refreshQuestions();
    }

    public function moderate(string $questionId, string $action): void
    {
        $response = app(LearningGatewayClient::class)->send('POST', "questions/{$questionId}/moderation", $this->token(), ['action' => $action]);
        Notification::make()->title($response === null ? 'Không thể cập nhật câu hỏi.' : 'Đã cập nhật trạng thái câu hỏi.')->{$response === null ? 'danger' : 'success'}()->send();
        $this->refreshQuestions();
    }

    public function resetFilters(): void
    {
        parent::resetFilters();
        $this->refreshQuestions();
    }

    private function refreshQuestions(): void
    {
        $query = [];
        if ($this->search !== '') {
            $query['q'] = $this->search;
        }
        if ($this->status !== 'all') {
            $query['status'] = $this->status;
        }
        $path = 'questions'.($query === [] ? '' : '?'.http_build_query($query));
        $this->questions = app(LearningGatewayClient::class)->get($path, $this->token())['data'] ?? [];
    }

    private function token(): string
    {
        return (string) session('admin.identity_token');
    }
}
