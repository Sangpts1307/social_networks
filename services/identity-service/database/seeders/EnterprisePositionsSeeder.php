<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrganizationEmailDomain;
use App\Models\OrgUnit;
use App\Models\OrgUnitMembership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserRoleAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EnterprisePositionsSeeder extends Seeder
{
    private const DEMO_PASSWORD = '12345678';

    public function run(): void
    {
        $organization = Organization::query()->where('code', 'DEMO-ORG')->firstOrFail();
        $hq = OrgUnit::query()->where('organization_id', $organization->id)->where('code', 'HQ')->firstOrFail();

        OrganizationEmailDomain::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'domain' => 'gmail.com'],
            ['is_verified' => true, 'is_primary' => false],
        );

        // 1. Khởi tạo hệ thống Phòng ban (Departments) & Nhóm (Teams) cho doanh nghiệp ~100 nhân sự
        $departments = [
            'EXEC' => ['name' => 'Ban Giám đốc', 'unit_type' => 'department', 'sort_order' => 5],
            'KNOWLEDGE' => ['name' => 'Khối Tri thức và Học tập', 'unit_type' => 'department', 'sort_order' => 10],
            'HR' => ['name' => 'Phòng Nhân sự & Hành chính', 'unit_type' => 'department', 'sort_order' => 15],
            'FIN' => ['name' => 'Phòng Tài chính - Kế toán', 'unit_type' => 'department', 'sort_order' => 20],
            'SALES' => ['name' => 'Khối Kinh doanh & CSKH', 'unit_type' => 'department', 'sort_order' => 25],
            'MKT' => ['name' => 'Phòng Marketing & Truyền thông', 'unit_type' => 'department', 'sort_order' => 30],
            'ENG' => ['name' => 'Khối Kỹ thuật & Sản phẩm', 'unit_type' => 'department', 'sort_order' => 35],
            'QA' => ['name' => 'Phòng Đảm bảo Chất lượng (QA)', 'unit_type' => 'department', 'sort_order' => 40],
        ];

        $unitMap = ['HQ' => $hq];

        foreach ($departments as $code => $info) {
            $unitMap[$code] = OrgUnit::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'code' => $code],
                [
                    'parent_id' => $hq->id,
                    'name' => $info['name'],
                    'unit_type' => $info['unit_type'],
                    'status' => 'active',
                    'sort_order' => $info['sort_order'],
                ],
            );
        }

        $teams = [
            'HR-REC' => ['parent' => 'HR', 'name' => 'Bộ phận Tuyển dụng & Đào tạo', 'sort_order' => 16],
            'HR-CB' => ['parent' => 'HR', 'name' => 'Bộ phận C&B và Hành chính', 'sort_order' => 17],
            'SALES-B2B' => ['parent' => 'SALES', 'name' => 'Nhóm Kinh doanh Doanh nghiệp (B2B)', 'sort_order' => 26],
            'SALES-B2C' => ['parent' => 'SALES', 'name' => 'Nhóm Kinh doanh Bán lẻ (B2C)', 'sort_order' => 27],
            'SALES-CS' => ['parent' => 'SALES', 'name' => 'Bộ phận Chăm sóc Khách hàng (CSKH)', 'sort_order' => 28],
            'MKT-CONTENT' => ['parent' => 'MKT', 'name' => 'Nhóm Content & Social Media', 'sort_order' => 31],
            'MKT-PERF' => ['parent' => 'MKT', 'name' => 'Nhóm Digital & Performance Ads', 'sort_order' => 32],
            'ENG-PROD' => ['parent' => 'ENG', 'name' => 'Nhóm Quản lý Sản phẩm & UI/UX', 'sort_order' => 36],
            'ENG-BE' => ['parent' => 'ENG', 'name' => 'Nhóm Kỹ thuật Backend', 'sort_order' => 37],
            'ENG-FE' => ['parent' => 'ENG', 'name' => 'Nhóm Kỹ thuật Frontend & Mobile', 'sort_order' => 38],
            'ENG-DEVOPS' => ['parent' => 'ENG', 'name' => 'Bộ phận Hạ tầng, DevOps & IT Nội bộ', 'sort_order' => 39],
        ];

        foreach ($teams as $code => $info) {
            $unitMap[$code] = OrgUnit::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'code' => $code],
                [
                    'parent_id' => $unitMap[$info['parent']]->id,
                    'name' => $info['name'],
                    'unit_type' => 'team',
                    'status' => 'active',
                    'sort_order' => $info['sort_order'],
                ],
            );
        }

        // 2. Khởi tạo các Role & Quyền theo vị trí công việc trong doanh nghiệp ~100 người
        $rolesConfig = [
            'executive' => [
                'name' => 'Ban Giám đốc (C-Level)',
                'description' => 'Điều hành chiến lược, xem toàn bộ cơ cấu nhân sự, duyệt thông báo và tài liệu cấp công ty.',
                'is_system' => false,
                'permissions' => [
                    'identity.profile.read_self', 'identity.profile.update_self',
                    'identity.users.read', 'identity.org_units.read', 'identity.roles.read', 'identity.audit.read',
                    'social.announcement.publish', 'social.news.publish', 'social.posts.pin', 'social.channels.manage', 'chat.groups.create',
                    'notification.broadcasts.manage',
                    'learning.documents.read', 'learning.documents.review', 'learning.documents.publish',
                ],
            ],
            'department_manager' => [
                'name' => 'Quản lý thành viên phòng ban',
                'description' => 'Trưởng/Phó phòng quản lý nhân sự trực thuộc, tạo kênh, ghim bài, duyệt tài liệu và điều phối công việc.',
                'is_system' => false,
                'permissions' => [
                    'identity.profile.read_self', 'identity.profile.update_self',
                    'identity.users.read', 'identity.users.update', 'identity.users.unlock', 'identity.org_units.read',
                    'social.news.publish', 'social.announcement.publish', 'social.posts.pin', 'social.channels.manage', 'chat.groups.create',
                    'learning.documents.read', 'learning.documents.create', 'learning.documents.upload',
                    'learning.documents.update', 'learning.documents.review', 'learning.documents.publish',
                ],
            ],
            'hr_specialist' => [
                'name' => 'Chuyên viên Nhân sự - Hành chính',
                'description' => 'Phụ trách tuyển dụng, C&B, tạo và cập nhật hồ sơ nhân sự, truyền thông nội bộ và ghim thông báo.',
                'is_system' => false,
                'permissions' => [
                    'identity.profile.read_self', 'identity.profile.update_self',
                    'identity.users.read', 'identity.users.create', 'identity.users.update', 'identity.users.unlock',
                    'identity.org_units.read', 'identity.roles.read',
                    'social.news.publish', 'social.announcement.publish', 'social.posts.pin', 'social.channels.manage', 'chat.groups.create',
                    'learning.documents.read', 'learning.documents.create', 'learning.documents.upload',
                ],
            ],
            'chief_accountant' => [
                'name' => 'Kế toán trưởng',
                'description' => 'Quản lý phòng Tài chính - Kế toán, kiểm soát quy trình tài chính và phê duyệt tài liệu kế toán.',
                'is_system' => false,
                'permissions' => [
                    'identity.profile.read_self', 'identity.profile.update_self',
                    'identity.users.read', 'identity.org_units.read', 'identity.audit.read',
                    'social.news.publish', 'social.posts.pin', 'social.channels.manage', 'chat.groups.create',
                    'learning.documents.read', 'learning.documents.create', 'learning.documents.upload',
                    'learning.documents.update', 'learning.documents.review', 'learning.documents.publish',
                ],
            ],
            'accountant' => [
                'name' => 'Kế toán viên (Tổng hợp / Thuế / Công nợ)',
                'description' => 'Thực hiện nghiệp vụ kế toán, thanh toán, lưu trữ chứng từ và quy trình tài chính.',
                'is_system' => false,
                'permissions' => [
                    'identity.profile.read_self', 'identity.profile.update_self',
                    'identity.org_units.read',
                    'learning.documents.read', 'learning.documents.create', 'learning.documents.upload',
                ],
            ],
            'sales_manager' => [
                'name' => 'Trưởng phòng Kinh doanh',
                'description' => 'Quản lý khối Kinh doanh & CSKH, tạo kênh chiến dịch, ghim mục tiêu và xuất bản tài liệu bán hàng.',
                'is_system' => false,
                'permissions' => [
                    'identity.profile.read_self', 'identity.profile.update_self',
                    'identity.users.read', 'identity.users.update', 'identity.org_units.read',
                    'social.news.publish', 'social.announcement.publish', 'social.posts.pin', 'social.channels.manage', 'chat.groups.create',
                    'learning.documents.read', 'learning.documents.create', 'learning.documents.upload',
                    'learning.documents.update', 'learning.documents.review', 'learning.documents.publish',
                ],
            ],
            'sales_executive' => [
                'name' => 'Chuyên viên Kinh doanh (Sales Executive)',
                'description' => 'Tư vấn khách hàng B2B/B2C, phát triển doanh thu và đóng góp kịch bản bán hàng.',
                'is_system' => false,
                'permissions' => [
                    'identity.profile.read_self', 'identity.profile.update_self',
                    'identity.org_units.read',
                    'learning.documents.read', 'learning.documents.create', 'learning.documents.upload',
                ],
            ],
            'customer_success' => [
                'name' => 'Chuyên viên Chăm sóc Khách hàng (CSKH)',
                'description' => 'Hỗ trợ khách hàng sau bán, giải đáp thắc mắc và đóng góp kho tri thức hỏi đáp Q&A.',
                'is_system' => false,
                'permissions' => [
                    'identity.profile.read_self', 'identity.profile.update_self',
                    'identity.org_units.read',
                    'learning.documents.read', 'learning.documents.create',
                ],
            ],
            'marketing_manager' => [
                'name' => 'Trưởng phòng Marketing',
                'description' => 'Quản lý chiến dịch thương hiệu, quản trị kênh truyền thông nội bộ và xuất bản tin tức.',
                'is_system' => false,
                'permissions' => [
                    'identity.profile.read_self', 'identity.profile.update_self',
                    'identity.users.read', 'identity.org_units.read',
                    'social.channels.manage', 'chat.groups.create', 'social.news.publish', 'social.announcement.publish', 'social.posts.pin',
                    'learning.documents.read', 'learning.documents.create', 'learning.documents.upload',
                    'learning.documents.update', 'learning.documents.publish',
                ],
            ],
            'marketing_specialist' => [
                'name' => 'Chuyên viên Marketing (Content / Digital / Design)',
                'description' => 'Sáng tạo nội dung, vận hành quảng cáo, thiết kế ấn phẩm và đăng bản tin nội bộ.',
                'is_system' => false,
                'permissions' => [
                    'identity.profile.read_self', 'identity.profile.update_self',
                    'identity.org_units.read',
                    'social.news.publish', 'social.posts.pin',
                    'learning.documents.read', 'learning.documents.create', 'learning.documents.upload',
                ],
            ],
            'product_manager' => [
                'name' => 'Quản lý Sản phẩm (Product Manager / PO)',
                'description' => 'Định hướng lộ trình sản phẩm, tạo kênh sản phẩm, quản lý tài liệu đặc tả (PRD) và khóa học.',
                'is_system' => false,
                'permissions' => [
                    'identity.profile.read_self', 'identity.profile.update_self',
                    'identity.users.read', 'identity.org_units.read',
                    'social.news.publish', 'social.posts.pin', 'social.channels.manage', 'chat.groups.create',
                    'learning.courses.manage', 'learning.taxonomy.manage',
                    'learning.documents.read', 'learning.documents.create', 'learning.documents.upload',
                    'learning.documents.update', 'learning.documents.review', 'learning.documents.publish',
                ],
            ],
            'software_engineer' => [
                'name' => 'Kỹ sư Phần mềm (Backend / Frontend / Mobile)',
                'description' => 'Phát triển tính năng sản phẩm, đóng góp tài liệu kiến trúc và hướng dẫn kỹ thuật.',
                'is_system' => false,
                'permissions' => [
                    'identity.profile.read_self', 'identity.profile.update_self',
                    'identity.org_units.read',
                    'learning.documents.read', 'learning.documents.create', 'learning.documents.upload', 'learning.documents.update',
                ],
            ],
            'it_support' => [
                'name' => 'IT Helpdesk & Quản trị Hạ tầng',
                'description' => 'Hỗ trợ thiết bị, mạng nội bộ, mở khóa tài khoản nhân viên và kiểm tra nhật ký hệ thống.',
                'is_system' => false,
                'permissions' => [
                    'identity.profile.read_self', 'identity.profile.update_self',
                    'identity.users.read', 'identity.users.unlock', 'identity.org_units.read', 'identity.audit.read',
                    'learning.documents.read', 'learning.documents.create', 'learning.documents.upload',
                ],
            ],
            'document_editor' => [
                'name' => 'Biên tập tài liệu',
                'description' => 'Mẫu scope; chọn phạm vi khi gán cho người dùng.',
                'is_system' => false,
                'permissions' => [
                    'learning.documents.read', 'learning.documents.create', 'learning.documents.upload', 'learning.documents.update',
                ],
            ],
            'document_reviewer' => [
                'name' => 'Duyệt tài liệu',
                'description' => 'Mẫu scope; chọn phạm vi khi gán cho người dùng.',
                'is_system' => false,
                'permissions' => [
                    'learning.documents.read', 'learning.documents.review', 'learning.documents.publish', 'learning.documents.archive',
                ],
            ],
        ];

        $roleMap = [];
        foreach (Role::query()->get() as $existingRole) {
            $roleMap[$existingRole->code] = $existingRole;
        }

        foreach ($rolesConfig as $code => $cfg) {
            $role = Role::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $cfg['name'],
                    'description' => $cfg['description'],
                    'is_system' => $cfg['is_system'],
                ],
            );
            $roleMap[$code] = $role;

            $permissionIds = Permission::query()->whereIn('code', $cfg['permissions'])->pluck('id');
            foreach ($permissionIds as $permId) {
                RolePermission::query()->firstOrCreate([
                    'role_id' => $role->id,
                    'permission_id' => $permId,
                ]);
            }
        }

        // 3. Khởi tạo danh sách Nhân sự ở các vị trí tiêu biểu
        $staffPositions = [
            [
                'id' => '30000000-0000-4000-8000-000000000006',
                'email' => 'ceo@gmail.com',
                'employee_code' => 'EMP-0006',
                'display_name' => 'Trần Minh Quân',
                'unit_code' => 'EXEC',
                'role_code' => 'executive',
                'scope_type' => 'organization',
                'job_title' => 'Giám đốc Điều hành (CEO)',
                'phone' => '0901000006',
                'skills' => ['Quản trị chiến lược', 'Điều hành doanh nghiệp', 'OKRs'],
                'manages_units' => ['EXEC'],
                'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000007',
                'email' => 'coo@gmail.com',
                'employee_code' => 'EMP-0007',
                'display_name' => 'Lê Thu Hà',
                'unit_code' => 'EXEC',
                'role_code' => 'executive',
                'scope_type' => 'organization',
                'job_title' => 'Giám đốc Vận hành (COO)',
                'phone' => '0901000007',
                'skills' => ['Vận hành', 'Tối ưu quy trình', 'Quản trị rủi ro'],
                'manages_units' => [],
                'avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000008',
                'email' => 'recruiter@gmail.com',
                'employee_code' => 'EMP-0008',
                'display_name' => 'Phạm Ngọc Ánh',
                'unit_code' => 'HR-REC',
                'role_code' => 'hr_specialist',
                'scope_type' => 'organization',
                'job_title' => 'Chuyên viên Tuyển dụng & Đào tạo',
                'phone' => '0901000008',
                'skills' => ['Talent Acquisition', 'Onboarding', 'Đào tạo nội bộ'],
                'manages_units' => ['HR-REC'],
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000009',
                'email' => 'cnb_admin@gmail.com',
                'employee_code' => 'EMP-0009',
                'display_name' => 'Đỗ Hoàng Yến',
                'unit_code' => 'HR-CB',
                'role_code' => 'hr_specialist',
                'scope_type' => 'organization',
                'job_title' => 'Chuyên viên C&B và Hành chính',
                'phone' => '0901000009',
                'skills' => ['Chấm công tính lương', 'Bảo hiểm xã hội', 'Hành chính văn phòng'],
                'manages_units' => ['HR-CB'],
                'avatar' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000010',
                'email' => 'chief_accountant@gmail.com',
                'employee_code' => 'EMP-0010',
                'display_name' => 'Vũ Thị Mai Hương',
                'unit_code' => 'FIN',
                'role_code' => 'chief_accountant',
                'scope_type' => 'org_unit',
                'job_title' => 'Kế toán trưởng',
                'phone' => '0901000010',
                'skills' => ['Báo cáo tài chính', 'Quản trị dòng tiền', 'Luật thuế'],
                'manages_units' => ['FIN'],
                'avatar' => 'https://images.unsplash.com/photo-1567532939604-b6b5b0db2604?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000011',
                'email' => 'accountant@gmail.com',
                'employee_code' => 'EMP-0011',
                'display_name' => 'Ngô Thanh Tùng',
                'unit_code' => 'FIN',
                'role_code' => 'accountant',
                'scope_type' => 'org_unit',
                'job_title' => 'Kế toán tổng hợp & Công nợ',
                'phone' => '0901000011',
                'skills' => ['Kế toán tổng hợp', 'MISA', 'Đối soát công nợ'],
                'manages_units' => [],
                'avatar' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000012',
                'email' => 'sales_manager@gmail.com',
                'employee_code' => 'EMP-0012',
                'display_name' => 'Hoàng Gia Bảo',
                'unit_code' => 'SALES',
                'role_code' => 'sales_manager',
                'scope_type' => 'org_unit',
                'job_title' => 'Trưởng phòng Kinh doanh (Head of Sales)',
                'phone' => '0901000012',
                'skills' => ['Chiến lược kinh doanh', 'Đàm phán B2B', 'CRM'],
                'manages_units' => ['SALES', 'SALES-B2B'],
                'avatar' => 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000013',
                'email' => 'sales_b2b@gmail.com',
                'employee_code' => 'EMP-0013',
                'display_name' => 'Đặng Quốc Khánh',
                'unit_code' => 'SALES-B2B',
                'role_code' => 'sales_executive',
                'scope_type' => 'org_unit',
                'job_title' => 'Chuyên viên Kinh doanh B2B (Account Executive)',
                'phone' => '0901000013',
                'skills' => ['Tư vấn giải pháp', 'Chăm sóc Key Account', 'Thuyết trình'],
                'manages_units' => [],
                'avatar' => 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000014',
                'email' => 'cskh@gmail.com',
                'employee_code' => 'EMP-0014',
                'display_name' => 'Bùi Phương Thảo',
                'unit_code' => 'SALES-CS',
                'role_code' => 'customer_success',
                'scope_type' => 'org_unit',
                'job_title' => 'Trưởng nhóm Chăm sóc Khách hàng (CSKH)',
                'phone' => '0901000014',
                'skills' => ['Customer Success', 'Xử lý khiếu nại', 'Quản lý SLA'],
                'manages_units' => ['SALES-CS'],
                'avatar' => 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000015',
                'email' => 'marketing_manager@gmail.com',
                'employee_code' => 'EMP-0015',
                'display_name' => 'Trịnh Khánh Linh',
                'unit_code' => 'MKT',
                'role_code' => 'marketing_manager',
                'scope_type' => 'organization',
                'job_title' => 'Trưởng phòng Marketing (Marketing Manager)',
                'phone' => '0901000015',
                'skills' => ['Brand Marketing', 'Truyền thông tích hợp', 'Growth Hacking'],
                'manages_units' => ['MKT', 'MKT-CONTENT', 'MKT-PERF'],
                'avatar' => 'https://images.unsplash.com/photo-1573496799652-408c2ac9fe98?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000016',
                'email' => 'content_mkt@gmail.com',
                'employee_code' => 'EMP-0016',
                'display_name' => 'Lý Minh Khôi',
                'unit_code' => 'MKT-CONTENT',
                'role_code' => 'marketing_specialist',
                'scope_type' => 'organization',
                'job_title' => 'Chuyên viên Content & Social Media',
                'phone' => '0901000016',
                'skills' => ['Copywriting', 'SEO Content', 'Thiết kế truyền thông'],
                'manages_units' => [],
                'avatar' => 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000017',
                'email' => 'product_manager@gmail.com',
                'employee_code' => 'EMP-0017',
                'display_name' => 'Phan Tuấn Kiệt',
                'unit_code' => 'ENG-PROD',
                'role_code' => 'product_manager',
                'scope_type' => 'organization',
                'job_title' => 'Quản lý Sản phẩm (Product Manager)',
                'phone' => '0901000017',
                'skills' => ['Product Roadmap', 'Agile/Scrum', 'Phân tích nghiệp vụ (BA)'],
                'manages_units' => ['ENG-PROD'],
                'avatar' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000018',
                'email' => 'tech_lead@gmail.com',
                'employee_code' => 'EMP-0018',
                'display_name' => 'Võ Thành Luân',
                'unit_code' => 'ENG',
                'role_code' => 'department_manager',
                'scope_type' => 'org_unit',
                'job_title' => 'Trưởng phòng Kỹ thuật (Head of Engineering)',
                'phone' => '0901000018',
                'skills' => ['System Architecture', 'Microservices', 'Tech Leadership'],
                'manages_units' => ['ENG', 'ENG-BE', 'ENG-FE'],
                'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80',
            ],
            [
                'id' => '30000000-0000-4000-8000-000000000019',
                'email' => 'it_support@gmail.com',
                'employee_code' => 'EMP-0019',
                'display_name' => 'Hồ Trọng Nghĩa',
                'unit_code' => 'ENG-DEVOPS',
                'role_code' => 'it_support',
                'scope_type' => 'organization',
                'job_title' => 'Chuyên viên DevOps & IT Helpdesk',
                'phone' => '0901000019',
                'skills' => ['Docker/Kubernetes', 'Quản trị mạng', 'Bảo mật hệ thống'],
                'manages_units' => ['ENG-DEVOPS'],
                'avatar' => 'https://images.unsplash.com/photo-1628157582853-a796fa650a6a?w=150&auto=format&fit=crop&q=80',
            ],
        ];

        $passwordHash = Hash::make(self::DEMO_PASSWORD);

        foreach ($staffPositions as $data) {
            $unit = $unitMap[$data['unit_code']];

            $user = User::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'email' => $data['email']],
                [
                    'id' => $data['id'],
                    'primary_org_unit_id' => $unit->id,
                    'employee_code' => $data['employee_code'],
                    'display_name' => $data['display_name'],
                    'password_hash' => $passwordHash,
                    'status' => 'active',
                    'must_change_password_at' => null,
                    'failed_login_count' => 0,
                    'locked_at' => null,
                    'locked_until' => null,
                ],
            );

            UserProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'job_title' => $data['job_title'],
                    'phone' => $data['phone'],
                    'skills' => $data['skills'],
                    'avatar_key' => $data['avatar'] ?? null,
                ],
            );

            OrgUnitMembership::query()->updateOrCreate(
                ['org_unit_id' => $unit->id, 'user_id' => $user->id],
                [
                    'organization_id' => $organization->id,
                    'membership_type' => ! empty($data['manages_units']) ? 'manager' : 'member',
                    'is_primary' => true,
                    'joined_at' => now(),
                ],
            );

            $role = $roleMap[$data['role_code']];
            $isOrgUnitScope = $data['scope_type'] === 'org_unit';

            UserRoleAssignment::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'org_unit_id' => $isOrgUnitScope ? $unit->id : null,
                    'scope_type' => $data['scope_type'],
                ],
                [
                    'include_descendants' => $isOrgUnitScope,
                    'assigned_by' => null,
                    'assigned_at' => now(),
                ],
            );

            foreach ($data['manages_units'] as $managedCode) {
                if (isset($unitMap[$managedCode])) {
                    $unitMap[$managedCode]->update(['manager_user_id' => $user->id]);
                }
            }
        }

        // Gán người quản lý cho các phòng ban có sẵn từ AdminUserSeeder
        $hrManager = User::query()->where('organization_id', $organization->id)->where('email', 'hr_manager@gmail.com')->first();
        if ($hrManager && isset($unitMap['HR'])) {
            $unitMap['HR']->update(['manager_user_id' => $hrManager->id]);
        }

        $teamLeader = User::query()->where('organization_id', $organization->id)->where('email', 'team_leader@gmail.com')->first();
        if ($teamLeader && isset($unitMap['KNOWLEDGE'])) {
            $unitMap['KNOWLEDGE']->update(['manager_user_id' => $teamLeader->id]);
        }

        $qaExpert = User::query()->where('organization_id', $organization->id)->where('email', 'qa_expert@gmail.com')->first();
        if ($qaExpert && isset($unitMap['QA'])) {
            $unitMap['QA']->update(['manager_user_id' => $qaExpert->id]);
        }
    }
}
