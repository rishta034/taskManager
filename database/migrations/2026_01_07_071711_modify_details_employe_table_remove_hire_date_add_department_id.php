<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyDetailsEmployeTableRemoveHireDateAddDepartmentId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('details_employe', function (Blueprint $table) {
            $table->dropColumn('hire_date');
            $table->dropColumn('department');
            $table->foreignId('department_id')->nullable()->after('address')->constrained('master_department')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('details_employe', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
            $table->string('department')->nullable()->after('address');
            $table->date('hire_date')->nullable()->after('department');
        });
    }
}
