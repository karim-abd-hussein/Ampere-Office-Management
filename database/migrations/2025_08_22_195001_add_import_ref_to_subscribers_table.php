<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            // رقم الاستيراد: اسم ملف الإكسل المستورد منه
            $table->string('import_ref', 255)
                ->nullable()
                ->after('subscription_date')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn('import_ref');
        });
    }
};
