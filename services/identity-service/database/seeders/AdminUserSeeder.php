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
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000002',
                'email' => 'hr_manager@gmail.com',
                'employee_code' => 'EMP-0002',
                'display_name' => 'Quản lý Nhân sự',
                'unit_id' => $headquarters->id,
                'role_code' => 'hr_manager',
                'job_title' => 'Quản lý nhân sự',
                'skills' => ['Nhân sự', 'Cơ cấu tổ chức'],
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000003',
                'email' => 'team_leader@gmail.com',
                'employee_code' => 'EMP-0003',
                'display_name' => 'Trưởng nhóm',
                'unit_id' => $knowledgeUnit->id,
                'role_code' => 'team_leader',
                'job_title' => 'Trưởng nhóm sản phẩm',
                'skills' => ['Quản lý nhóm', 'Sản phẩm'],
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000004',
                'email' => 'qa_expert@gmail.com',
                'employee_code' => 'EMP-0004',
                'display_name' => 'Chuyên gia QA',
                'unit_id' => $knowledgeUnit->id,
                'role_code' => 'qa_expert',
                'job_title' => 'Chuyên gia tri thức',
                'skills' => ['Knowledge Graph', 'Quality Assurance', 'AI'],
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000005',
                'email' => 'member@gmail.com',
                'employee_code' => 'EMP-0005',
                'display_name' => 'Thành viên',
                'unit_id' => $knowledgeUnit->id,
                'role_code' => 'member',
                'job_title' => 'Kỹ sư phần mềm',
                'skills' => ['Vue', 'Laravel', 'Tìm kiếm tri thức'],
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
                ['job_title' => $data['job_title'], 'skills' => $data['skills']],
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
