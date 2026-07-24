<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `project_members`
                MODIFY COLUMN `joined_via` ENUM('invite', 'request', 'direct', 'code')
                DEFAULT 'direct'"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `project_members`
                MODIFY COLUMN `joined_via` ENUM('invite', 'request', 'direct')
                DEFAULT 'direct'"
            );
        }
    }
};
