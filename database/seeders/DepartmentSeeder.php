<?php

namespace Database\Seeders;

use App\Models\MasterDepartment;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $departments = [
            ['name' => 'IT'],
            ['name' => 'HR'],
            ['name' => 'Finance'],
            ['name' => 'Marketing'],
            ['name' => 'Sales'],
            ['name' => 'Operations'],
        ];

        foreach ($departments as $department) {
            MasterDepartment::firstOrCreate(
                ['name' => $department['name']],
                $department
            );
        }
    }
}
