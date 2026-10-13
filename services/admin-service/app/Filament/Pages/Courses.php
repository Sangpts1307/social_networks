<?php

namespace App\Filament\Pages;

use App\Auth\RemoteIdentityUser;
use App\Services\LearningGatewayClient;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class Courses extends ModulePlaceholder
{
    protected string $view = 'filament.pages.courses';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Khóa học';

    protected static \UnitEnum|string|null $navigationGroup = 'Nội dung học tập';

    protected static ?int $navigationSort = 22;

    protected static string $moduleDescription = 'Base vận hành khóa học, chương trình, lớp học và trạng thái xuất bản.';

    protected static string $moduleOwner = 'learning-service';

    protected static array $moduleCapabilities = ['Danh sách khóa học', 'Kiểm duyệt nội dung', 'Xuất bản/đóng khóa học', 'Theo dõi enrollment'];

    /** @var array<int, array<string, mixed>> */
    public array $courses = [];

    /** @var array<int, array<string, mixed>> */
    public array $topics = [];

    public string $courseTitle = '';

    public ?string $topicId = null;

    public ?string $courseDescription = null;

    public function mount(): void
    {
        $this->refreshCourses();
        $this->topics = app(LearningGatewayClient::class)->get('taxonomy', $this->token())['data']['topics'] ?? [];
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof RemoteIdentityUser && $user->can('learning.courses.manage');
    }

    public function saveCourse(): void
    {
        $this->validate(['courseTitle' => ['required', 'string', 'max:500'], 'topicId' => ['nullable', 'uuid'], 'courseDescription' => ['nullable', 'string', 'max:50000']]);
        $response = app(LearningGatewayClient::class)->send('POST', 'courses', $this->token(), ['title' => $this->courseTitle, 'topic_id' => $this->topicId, 'description_markdown' => $this->courseDescription]);
        if ($response === null) {
            Notification::make()->title('Không thể tạo khóa học.')->danger()->send();

            return;
        }
        $this->reset('courseTitle', 'topicId', 'courseDescription');
        $this->refreshCourses();
        Notification::make()->title('Đã tạo bản nháp khóa học.')->success()->send();
    }

    public function updateCourseLifecycle(string $courseId, string $action): void
    {
        $response = app(LearningGatewayClient::class)->send('POST', "courses/{$courseId}/lifecycle", $this->token(), ['action' => $action]);
        Notification::make()->title($response === null ? 'Không thể cập nhật khóa học.' : 'Đã cập nhật trạng thái khóa học.')->{$response === null ? 'danger' : 'success'}()->send();
        $this->refreshCourses();
    }

    public function updatedSearch(): void
    {
        $this->refreshCourses();
    }

    public function updatedStatus(): void
    {
        $this->refreshCourses();
    }

    public function resetFilters(): void
    {
        parent::resetFilters();
        $this->refreshCourses();
    }

    private function refreshCourses(): void
    {
        $this->courses = app(LearningGatewayClient::class)->get('courses', $this->token())['data'] ?? [];
        if ($this->status !== 'all') {
            $this->courses = array_values(array_filter($this->courses, fn (array $course): bool => $course['status'] === $this->status));
        }
        if ($this->search !== '') {
            $this->courses = array_values(array_filter($this->courses, fn (array $course): bool => str_contains(mb_strtolower($course['title']), mb_strtolower($this->search))));
        }
    }

    private function token(): string
    {
        return (string) session('admin.identity_token');
    }
}
