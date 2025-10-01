<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            // نحاول حذف الـ FK القديم إن وُجد (بدون كسر المايغريشن لو ما كان موجود)
            try { $table->dropForeign(['generator_id']); } catch (\Throwable $e) {}

            // نخلي الحقل يقبل NULL
            $table->unsignedBigInteger('generator_id')->nullable()->change();

            // نعيد ربط FK مع nullOnDelete (لو انحذف المولد يتحط NULL)
            $table->foreign('generator_id')
                ->references('id')->on('generators')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            // فك الربط الحالي
            try { $table->dropForeign(['generator_id']); } catch (\Throwable $e) {}

            // نرجعه NOT NULL (لو عندك سجلات NULL لازم تنظفها قبل الرجوع)
            $table->unsignedBigInteger('generator_id')->nullable(false)->change();

            // نعيد ربط FK كما كان (اخترت CASCADE كافتراضي؛ غيّرها لو بدك)
            $table->foreign('generator_id')
                ->references('id')->on('generators')
                ->cascadeOnDelete();
        });
    }
};
