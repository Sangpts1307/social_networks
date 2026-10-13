<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrganizationEmailDomain;
use App\Models\OrganizationSetting;
use App\Models\OrgUnit;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(
            ['code' => 'DEMO-ORG'],
            [
                'id' => '10000000-0000-4000-8000-000000000001',
                'name' => 'Demo Internal Network',
                'legal_name' => 'Demo Internal Network Co., Ltd.',
                'status' => 'active',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'locale' => 'vi',
            ],
        );

        OrganizationEmailDomain::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'domain' => 'demo.internal'],
            ['is_verified' => true, 'is_primary' => true],
        );

        $root = OrgUnit::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'code' => 'HQ'],
            [
                'id' => '20000000-0000-4000-8000-000000000001',
                'name' => 'Trụ sở chính',
                'unit_type' => 'company',
                'status' => 'active',
                'sort_order' => 1,
            ],
        );

        OrgUnit::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'code' => 'KNOWLEDGE'],
            [
                'id' => '20000000-0000-4000-8000-000000000002',
                'parent_id' => $root->id,
                'name' => 'Khối Tri thức và Học tập',
                'unit_type' => 'department',
                'status' => 'active',
                'sort_order' => 10,
            ],
        );

        OrganizationSetting::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'setting_key' => 'knowledge_graph_demo'],
            ['setting_value' => ['enabled' => true, 'seed_version' => 1]],
        );
    }
}
