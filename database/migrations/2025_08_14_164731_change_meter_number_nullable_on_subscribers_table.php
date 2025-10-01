<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // نخلي رقم العداد يقبل NULL. لا تعيد إنشاء الـ UNIQUE — بيبقى كما هو.
        Schema::table('subscribers', function (Blueprint $table) {
            $table->string('meter_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        // نرجّعه NOT NULL إذا بدك ترجع
        Schema::table('subscribers', function (Blueprint $table) {
            $table->string('meter_number')->nullable(false)->change();
        });
    }
};
