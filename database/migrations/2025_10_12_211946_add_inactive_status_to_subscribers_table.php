<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add the new 'inactive' value to the ENUM
        DB::statement("
            ALTER TABLE `subscribers`
            MODIFY COLUMN `status`
            ENUM('active','disconnected','cancelled','changed_meter','changed_name','inactive')
            NOT NULL DEFAULT 'active'
        ");
    }

    public function down(): void
    {
        // Revert back to previous ENUM values
        DB::statement("
            ALTER TABLE `subscribers`
            MODIFY COLUMN `status`
            ENUM('active','disconnected','cancelled','changed_meter','changed_name')
            NOT NULL DEFAULT 'active'
        ");
    }
};