<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddRoleAndGoogleFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'admin', 'user'])->default('user')->after('email');
            $table->string('google_id')->nullable()->after('password');
            $table->string('avatar')->nullable()->after('google_id');
        });
        
        // Make password nullable for Google OAuth users
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'google_id', 'avatar']);
        });
        
        // Restore password to NOT NULL
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL');
    }
}
