<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // لو أصلاً عملنا التحويل قبل هيك، ما نعيده
        if (! Schema::hasColumn('notifications', 'num_id')) {
            // 1) اشطب الـ PRIMARY KEY القديم
            DB::statement('ALTER TABLE `notifications` DROP PRIMARY KEY');

            // 2) أضف عمود رقمي Auto-Increment كـ PRIMARY KEY
            //    (MySQL/MariaDB بتعبي القيم تلقائياً للصفوف الموجودة)
            DB::statement('ALTER TABLE `notifications` ADD COLUMN `num_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');

            // 3) خليه الـ uuid (حقل id القديم) فريد اختياريًا لسلامة البيانات
            try {
                DB::statement('ALTER TABLE `notifications` ADD UNIQUE KEY `notifications_uuid_unique` (`id`)');
            } catch (\Throwable $e) {
                // يمكن يكون موجود
            }
        }
    }

    public function down(): void
    {
        // رجوع للوضع القديم (uuid هو الـ PK)
        if (Schema::hasColumn('notifications', 'num_id')) {
            try {
                DB::statement('ALTER TABLE `notifications` DROP PRIMARY KEY');
            } catch (\Throwable $e) {}

            // احذف المفتاح الفريد على uuid إن وُجد
            try {
                DB::statement('ALTER TABLE `notifications` DROP INDEX `notifications_uuid_unique`');
            } catch (\Throwable $e) {}

            // ارجِع الـ PK على عمود id (الـ uuid)
            DB::statement('ALTER TABLE `notifications` ADD PRIMARY KEY (`id`)');

            // احذف العمود الرقمي
            DB::statement('ALTER TABLE `notifications` DROP COLUMN `num_id`');
        }
    }
};
