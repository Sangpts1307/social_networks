<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrgUnit;
use App\Models\OrgUnitMembership;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserRoleAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    private const DEMO_PASSWORD = '12345678';

    public function run(): void
    {
        $organization = Organization::query()->where('code', 'DEMO-ORG')->firstOrFail();
        $headquarters = OrgUnit::query()->where('organization_id', $organization->id)->where('code', 'HQ')->firstOrFail();
        $knowledgeUnit = OrgUnit::query()->where('organization_id', $organization->id)->where('code', 'KNOWLEDGE')->firstOrFail();

        $users = [
            [
                'id' => '30000000-0000-4000-8000-000000000001',
                'email' => 'admin@gmail.com',
                'employee_code' => 'EMP-0001',
                'display_name' => 'Nguyễn Quản Trị',
                'unit_id' => $headquarters->id,
                'role_code' => 'super_admin',
                'job_title' => 'Quản trị viên hệ thống',
                'skills' => ['Quản trị hệ thống', 'RBAC'],
                'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000002',
                'email' => 'hr_manager@gmail.com',
                'employee_code' => 'EMP-0002',
                'display_name' => 'Lê Thị Mai Phương',
                'unit_id' => $headquarters->id,
                'role_code' => 'hr_manager',
                'job_title' => 'Quản lý nhân sự & Văn hóa',
                'skills' => ['Nhân sự', 'Cơ cấu tổ chức', 'Văn hóa doanh nghiệp'],
                'avatar' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000003',
                'email' => 'team_leader@gmail.com',
                'employee_code' => 'EMP-0003',
                'display_name' => 'Trần Đức Thắng',
                'unit_id' => $knowledgeUnit->id,
                'role_code' => 'team_leader',
                'job_title' => 'Trưởng nhóm sản phẩm',
                'skills' => ['Quản lý nhóm', 'Sản phẩm'],
                'avatar' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000004',
                'email' => 'qa_expert@gmail.com',
                'employee_code' => 'EMP-0004',
                'display_name' => 'Nguyễn Minh Anh',
                'unit_id' => $knowledgeUnit->id,
                'role_code' => 'qa_expert',
                'job_title' => 'Chuyên gia QA & Tri thức',
                'skills' => ['Knowledge Graph', 'Quality Assurance', 'AI'],
                'avatar' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000005',
                'email' => 'member@gmail.com',
                'employee_code' => 'EMP-0005',
                'display_name' => 'Phạm Phát',
                'unit_id' => $knowledgeUnit->id,
                'role_code' => 'member',
                'job_title' => 'Kỹ sư phần mềm cao cấp',
                'skills' => ['Vue', 'Laravel', 'Tìm kiếm tri thức'],
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
            ],
        ];

        foreach ($users as $data) {
            // Local-only fixture: this deliberately resets all demo credentials.
            $user = User::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'email' => $data['email']],
                [
                    'id' => $data['id'],
                    'primary_org_unit_id' => $data['unit_id'],
                    'employee_code' => $data['employee_code'],
                    'display_name' => $data['display_name'],
                    'password_hash' => Hash::make(self::DEMO_PASSWORD),
                    'status' => 'active',
                    'must_change_password_at' => null,
                    'failed_login_count' => 0,
                    'locked_at' => null,
                    'locked_until' => null,
                ],
            );

            UserProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                ['job_title' => $data['job_title'], 'skills' => $data['skills'], 'avatar_key' => $data['avatar'] ?? null],
            );

            OrgUnitMembership::query()->updateOrCreate(
                ['org_unit_id' => $data['unit_id'], 'user_id' => $user->id],
                ['organization_id' => $organization->id, 'membership_type' => 'member', 'is_primary' => true, 'joined_at' => now()],
            );

            $role = Role::query()->where('code', $data['role_code'])->firstOrFail();
            UserRoleAssignment::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'user_id' => $user->id, 'role_id' => $role->id, 'org_unit_id' => null],
                ['assigned_by' => null, 'assigned_at' => now()],
            );
        }

        $admin = User::query()->where('email', 'admin@gmail.com')->firstOrFail();
        $headquarters->update(['manager_user_id' => $admin->id]);
    }
}
