<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            RbacSeeder::class,
            AdminUserSeeder::class,
            EnterprisePositionsSeeder::class,
            EmployeeBirthdaySeeder::class,
        ]);
    }
}
