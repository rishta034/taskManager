<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get or create a default user
        $user = User::firstOrCreate(
            ['email' => 'admin@taskmanager.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
            ]
        );

        $tasks = [
            [
                'title' => 'Design new dashboard layout',
                'description' => 'Create a modern and responsive dashboard design with improved UX',
                'status' => 'in_progress',
                'priority' => 'high',
                'due_date' => now()->addDays(5),
                'user_id' => $user->id,
            ],
            [
                'title' => 'Implement user authentication',
                'description' => 'Add login and registration functionality with secure password hashing',
                'status' => 'completed',
                'priority' => 'high',
                'due_date' => now()->subDays(2),
                'user_id' => $user->id,
            ],
            [
                'title' => 'Write API documentation',
                'description' => 'Document all API endpoints and their usage examples',
                'status' => 'pending',
                'priority' => 'medium',
                'due_date' => now()->addDays(10),
                'user_id' => $user->id,
            ],
            [
                'title' => 'Fix responsive design issues',
                'description' => 'Ensure the dashboard works well on mobile and tablet devices',
                'status' => 'pending',
                'priority' => 'medium',
                'due_date' => now()->addDays(7),
                'user_id' => $user->id,
            ],
            [
                'title' => 'Add task filtering options',
                'description' => 'Implement filter by status, priority, and date range',
                'status' => 'in_progress',
                'priority' => 'low',
                'due_date' => now()->addDays(14),
                'user_id' => $user->id,
            ],
            [
                'title' => 'Optimize database queries',
                'description' => 'Review and optimize slow database queries for better performance',
                'status' => 'pending',
                'priority' => 'low',
                'due_date' => now()->addDays(20),
                'user_id' => $user->id,
            ],
        ];

        foreach ($tasks as $task) {
            Task::create($task);
        }
    }
}
