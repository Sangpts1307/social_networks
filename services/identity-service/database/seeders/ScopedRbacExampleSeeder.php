<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrgUnit;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/** Optional examples: adds catalogs only; never modifies accounts or existing roles. */
class ScopedRbacExampleSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('code', 'DEMO-ORG')->firstOrFail();
        $root = OrgUnit::where('organization_id', $organization->id)->where('code', 'HQ')->firstOrFail();
        foreach (['HR' => 'Nhân sự', 'ENG' => 'Kỹ thuật', 'QA' => 'QA và Tri thức', 'SALES' => 'Kinh doanh', 'FIN' => 'Tài chính'] as $code => $name) {
            OrgUnit::firstOrCreate(['organization_id' => $organization->id, 'code' => $code], ['name' => $name, 'parent_id' => $root->id, 'unit_type' => 'department', 'status' => 'active', 'sort_order' => 20]);
        }
        $engineering = OrgUnit::where('organization_id', $organization->id)->where('code', 'ENG')->firstOrFail();
        foreach (['ENG-BE' => 'Backend', 'ENG-FE' => 'Frontend'] as $code => $name) {
            OrgUnit::firstOrCreate(['organization_id' => $organization->id, 'code' => $code], ['name' => $name, 'parent_id' => $engineering->id, 'unit_type' => 'team', 'status' => 'active', 'sort_order' => 30]);
        }
        $roles = [
            'department_manager' => ['Quản lý thành viên phòng ban', ['identity.users.read', 'identity.users.update', 'identity.users.unlock', 'identity.org_units.read']],
            'document_editor' => ['Biên tập tài liệu', ['learning.documents.read', 'learning.documents.create', 'learning.documents.upload', 'learning.documents.update']],
            'document_reviewer' => ['Duyệt tài liệu', ['learning.documents.read', 'learning.documents.review', 'learning.documents.publish', 'learning.documents.archive']],
        ];
        foreach ($roles as $code => [$name, $permissions]) {
            $role = Role::firstOrCreate(['code' => $code], ['name' => $name, 'description' => 'Mẫu scope; chọn phạm vi khi gán cho người dùng.', 'is_system' => false]);
            if ($role->wasRecentlyCreated) {
                $role->permissions()->sync(Permission::whereIn('code', $permissions)->pluck('id'));
            }
        }
    }
}
