<?php

namespace Database\Seeders;

use App\Models\MasterOrganization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $organizations = [
            ['name' => 'FAMEUS MEDIA'],
            ['name' => 'THE SCHOOL AGENCY'],
        ];

        foreach ($organizations as $org) {
            MasterOrganization::firstOrCreate(
                ['name' => $org['name']],
                $org
            );
        }
    }
}
