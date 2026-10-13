<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\UserNotificationPreference;
use Illuminate\Database\Seeder;

class NotificationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $organizationId = '10000000-0000-4000-8000-000000000001';
        $memberUserId = '30000000-0000-4000-8000-000000000003';

        NotificationTemplate::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'code' => 'system.welcome', 'channel' => 'in_app', 'locale' => 'vi-VN', 'version' => 1],
            ['id' => '90000000-0000-4000-8000-000000000001', 'title_template' => 'Chào mừng {{name}}', 'body_template' => 'Bạn đã sẵn sàng khám phá nền tảng nội bộ.', 'status' => 'published'],
        );
        UserNotificationPreference::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'user_id' => $memberUserId, 'notification_type' => '*'],
            ['id' => '90000000-0000-4000-8000-000000000002', 'in_app_enabled' => true, 'push_enabled' => true, 'email_enabled' => false],
        );
        Notification::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'recipient_user_id' => $memberUserId, 'notification_type' => 'system_welcome'],
            ['id' => '90000000-0000-4000-8000-000000000003', 'priority' => 'normal', 'title' => 'Chào mừng đến nền tảng', 'body' => 'Dữ liệu demo của Notification Service.', 'source_service' => 'notification'],
        );
    }
}
