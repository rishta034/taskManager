<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateTasksStatusAndPriorityEnums extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update status enum to include new values
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'not_started', 'issue_in_working') DEFAULT 'not_started'");
        
        // Update priority enum to include 'critical'
        DB::statement("ALTER TABLE tasks MODIFY COLUMN priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert status enum
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending'");
        
        // Revert priority enum
        DB::statement("ALTER TABLE tasks MODIFY COLUMN priority ENUM('low', 'medium', 'high') DEFAULT 'medium'");
    }
}
