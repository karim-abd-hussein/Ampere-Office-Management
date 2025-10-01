<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // إذا عندك قيد FK باسمه المختلف، احذفه أولاً ثم أعده (اختياري حسب سكيمتك)
        // DB::statement('ALTER TABLE `invoices` DROP FOREIGN KEY invoices_collector_id_foreign');

        DB::statement('ALTER TABLE `invoices` MODIFY `collector_id` BIGINT UNSIGNED NULL');

        // ثم إن احتجت FK يسمح بـ NULL، أعد إنشاءه:
        // Schema::table('invoices', function (Blueprint $table) {
        //     $table->foreign('collector_id')->references('id')->on('collectors')->nullOnDelete();
        // });
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `invoices` MODIFY `collector_id` BIGINT UNSIGNED NOT NULL');
    }
};
