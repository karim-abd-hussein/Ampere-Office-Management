<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // نحاول نحذف الـ UNIQUE سواء باسم Laravel الافتراضي أو باسم مختصر "meter_number"
        try {
            Schema::table('subscribers', function (Blueprint $table) {
                // الاسم الافتراضي عند Laravel
                $table->dropUnique('subscribers_meter_number_unique');
            });
        } catch (\Throwable $e) {
            try {
                // بعض الجداول يظهر اسم الفهرس "meter_number" فقط
                DB::statement('ALTER TABLE `subscribers` DROP INDEX `meter_number`');
            } catch (\Throwable $e2) {
                // ولا يهمك إذا كان محذوف أصلاً
            }
        }

        // نضيف Index عادي (غير مميز) لتحسين البحث على الحقل
        try {
            Schema::table('subscribers', function (Blueprint $table) {
                $table->index('meter_number'); // الاسم المتوقّع: subscribers_meter_number_index
            });
        } catch (\Throwable $e) {
            // تجاهل إذا كان موجود
        }
    }

    public function down(): void
    {
        // نشيل الـ index العادي ونرجّع UNIQUE
        try {
            Schema::table('subscribers', function (Blueprint $table) {
                $table->dropIndex('subscribers_meter_number_index');
            });
        } catch (\Throwable $e) {
            try {
                DB::statement('ALTER TABLE `subscribers` DROP INDEX `subscribers_meter_number_index`');
            } catch (\Throwable $e2) {
                // تجاهل
            }
        }

        try {
            Schema::table('subscribers', function (Blueprint $table) {
                $table->unique('meter_number');
            });
        } catch (\Throwable $e) {
            // لو صار عندك قيَم مكررة، down رح يفشل — هذا طبيعي
        }
    }
};
