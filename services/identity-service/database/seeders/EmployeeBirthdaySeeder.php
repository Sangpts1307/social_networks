<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeBirthdaySeeder extends Seeder
{
    public function run(): void
    {
        $birthdays = [
            'tech_lead@gmail.com' => '1990-11-16',
            'hr_manager@gmail.com' => '1992-11-20',
            'ceo@gmail.com' => '1984-11-25',
            'sales_manager@gmail.com' => '1991-11-28',
        ];

        $organization = Organization::where('code', 'DEMO-ORG')->firstOrFail();
        $users = User::where('organization_id', $organization->id)->whereIn('email', array_keys($birthdays))->get();
        foreach ($users as $user) {
            $user->profile()->whereNull('birth_date')->update(['birth_date' => $birthdays[$user->email]]);
        }
    }
}
