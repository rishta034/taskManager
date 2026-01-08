<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Admin User
        User::updateOrCreate(
            ['email' => 'admin@taskmanager.com'],
            [
                'name' => 'admin',
                'password' => Hash::make('1234'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Create Super Admin User (optional)
        User::updateOrCreate(
            ['email' => 'superadmin@taskmanager.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('1234'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]
        );

        // Create Regular User (optional)
        User::updateOrCreate(
            ['email' => 'user@taskmanager.com'],
            [
                'name' => 'User',
                'password' => Hash::make('1234'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );
    }
}

