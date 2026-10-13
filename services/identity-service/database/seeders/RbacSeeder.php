<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['code' => 'social.channels.manage', 'name' => 'Quản lý kênh Social', 'resource' => 'social_channels', 'action' => 'manage'],
            ['code' => 'social.news.publish', 'name' => 'Đăng tin nội bộ', 'resource' => 'social_news', 'action' => 'publish'],
            ['code' => 'social.announcement.publish', 'name' => 'Đăng thông báo chính thức', 'resource' => 'social_announcement', 'action' => 'publish'],
            ['code' => 'social.moderation.manage', 'name' => 'Kiểm duyệt Social', 'resource' => 'social_moderation', 'action' => 'manage'],
            ['code' => 'identity.profile.read_self', 'name' => 'Xem hồ sơ của mình', 'resource' => 'profile', 'action' => 'read_self'],
            ['code' => 'identity.profile.update_self', 'name' => 'Sửa hồ sơ của mình', 'resource' => 'profile', 'action' => 'update_self'],
            ['code' => 'identity.users.read', 'name' => 'Xem người dùng', 'resource' => 'users', 'action' => 'read'],
            ['code' => 'identity.users.create', 'name' => 'Tạo người dùng', 'resource' => 'users', 'action' => 'create'],
            ['code' => 'identity.users.update', 'name' => 'Cập nhật người dùng', 'resource' => 'users', 'action' => 'update'],
            ['code' => 'identity.users.unlock', 'name' => 'Mở khóa tài khoản', 'resource' => 'users', 'action' => 'unlock'],
            ['code' => 'identity.org_units.read', 'name' => 'Xem đơn vị tổ chức', 'resource' => 'org_units', 'action' => 'read'],
            ['code' => 'identity.org_units.manage', 'name' => 'Quản lý đơn vị tổ chức', 'resource' => 'org_units', 'action' => 'manage'],
            ['code' => 'identity.roles.read', 'name' => 'Xem vai trò', 'resource' => 'roles', 'action' => 'read'],
            ['code' => 'identity.roles.manage', 'name' => 'Quản lý vai trò', 'resource' => 'roles', 'action' => 'manage'],
            ['code' => 'identity.audit.read', 'name' => 'Xem nhật ký kiểm toán', 'resource' => 'audit', 'action' => 'read'],
            ['code' => 'learning.taxonomy.manage', 'name' => 'Quản lý taxonomy tri thức', 'resource' => 'learning_taxonomy', 'action' => 'manage'],
            ['code' => 'learning.questions.moderate', 'name' => 'Kiểm duyệt hỏi đáp', 'resource' => 'learning_questions', 'action' => 'moderate'],
            ['code' => 'learning.documents.read', 'name' => 'Xem tài liệu tri thức', 'resource' => 'learning_documents', 'action' => 'read'],
            ['code' => 'learning.documents.create', 'name' => 'Tạo tài liệu tri thức', 'resource' => 'learning_documents', 'action' => 'create'],
            ['code' => 'learning.documents.upload', 'name' => 'Tải tệp tài liệu', 'resource' => 'learning_documents', 'action' => 'upload'],
            ['code' => 'learning.documents.update', 'name' => 'Cập nhật metadata tài liệu', 'resource' => 'learning_documents', 'action' => 'update'],
            ['code' => 'learning.documents.review', 'name' => 'Duyệt phiên bản tài liệu', 'resource' => 'learning_documents', 'action' => 'review'],
            ['code' => 'learning.documents.publish', 'name' => 'Xuất bản tài liệu', 'resource' => 'learning_documents', 'action' => 'publish'],
            ['code' => 'learning.documents.archive', 'name' => 'Lưu trữ hoặc khôi phục tài liệu', 'resource' => 'learning_documents', 'action' => 'archive'],
            ['code' => 'learning.documents.access.manage', 'name' => 'Quản lý quyền truy cập tài liệu', 'resource' => 'learning_documents', 'action' => 'access_manage'],
            ['code' => 'learning.courses.manage', 'name' => 'Quản lý khóa học', 'resource' => 'learning_courses', 'action' => 'manage'],
            ['code' => 'ai.curation.review', 'name' => 'Duyệt gợi ý tri thức AI', 'resource' => 'ai_curation', 'action' => 'review'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['code' => $permission['code']], $permission);
        }

        $roleDefinitions = [
            'super_admin' => ['Quản trị hệ thống', 'Toàn quyền quản trị tenant.', true, array_column($permissions, 'code')],
            'hr_manager' => ['Quản lý nhân sự', 'Quản lý nhân sự và đơn vị.', true, ['identity.profile.read_self', 'identity.profile.update_self', 'identity.users.read', 'identity.users.create', 'identity.users.update', 'identity.users.unlock', 'identity.org_units.read', 'identity.org_units.manage', 'identity.roles.read', 'learning.documents.read']],
            'team_leader' => ['Trưởng nhóm', 'Xem thành viên, cơ cấu tổ chức và đóng góp tài liệu.', true, ['identity.profile.read_self', 'identity.profile.update_self', 'identity.users.read', 'identity.org_units.read', 'learning.documents.read', 'learning.documents.create', 'learning.documents.upload', 'learning.documents.update']],
            'qa_expert' => ['Chuyên gia QA', 'Quản trị tri thức và duyệt tài liệu.', true, ['identity.profile.read_self', 'identity.profile.update_self', 'identity.users.read', 'learning.taxonomy.manage', 'learning.questions.moderate', 'learning.documents.read', 'learning.documents.create', 'learning.documents.upload', 'learning.documents.update', 'learning.documents.review', 'learning.documents.publish', 'learning.documents.archive', 'learning.documents.access.manage', 'ai.curation.review']],
            'member' => ['Thành viên', 'Quyền cơ bản và đọc toàn bộ tài liệu nội bộ đã xuất bản.', true, ['identity.profile.read_self', 'identity.profile.update_self', 'learning.documents.read']],
        ];

        foreach ($roleDefinitions as $code => [$name, $description, $isSystem, $permissionCodes]) {
            $role = Role::query()->firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'description' => $description, 'is_system' => $isSystem],
            );

            foreach (Permission::query()->whereIn('code', $permissionCodes)->get() as $permission) {
                RolePermission::query()->firstOrCreate([
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }
}
