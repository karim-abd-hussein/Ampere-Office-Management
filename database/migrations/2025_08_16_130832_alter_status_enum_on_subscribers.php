<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // أضف القيمتين الجديدتين للـ ENUM
        DB::statement("
            ALTER TABLE `subscribers`
            MODIFY COLUMN `status`
            ENUM('active','disconnected','cancelled','changed_meter','changed_name')
            NOT NULL DEFAULT 'active'
        ");
    }

    public function down(): void
    {
        // رجوع للوضع السابق (لو احتجت)
        DB::statement("
            ALTER TABLE `subscribers`
            MODIFY COLUMN `status`
            ENUM('active','disconnected','cancelled')
            NOT NULL DEFAULT 'active'
        ");
    }
};
