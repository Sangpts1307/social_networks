<?php
namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class ApiExpansionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('code', 'super_admin')->first();
        foreach ([
            ['learning.rewards.manage', 'Cấu hình và duyệt thưởng Tri thức', 'learning_rewards'],
            ['social.posts.pin', 'Ghim bài viết', 'social_posts'],
            ['notification.broadcasts.manage', 'Quản lý thông báo gửi hàng loạt', 'notification_broadcasts'],
        ] as [$code, $name, $resource]) {
            $permission = Permission::firstOrCreate(['code' => $code], ['name' => $name, 'resource' => $resource, 'action' => 'manage']);
            if ($role) RolePermission::firstOrCreate(['role_id' => $role->id, 'permission_id' => $permission->id]);
        }
    }
}
