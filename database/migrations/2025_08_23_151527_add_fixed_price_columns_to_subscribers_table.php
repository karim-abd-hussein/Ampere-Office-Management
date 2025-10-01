<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            if (! Schema::hasColumn('subscribers', 'use_fixed_price')) {
                $table->boolean('use_fixed_price')->default(false)->after('status');
            }
            if (! Schema::hasColumn('subscribers', 'fixed_kwh_price')) {
                // 10,4: يدعم 4 منازل عشرية (مثلاً 125.5000)
                $table->decimal('fixed_kwh_price', 10, 4)->nullable()->after('use_fixed_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            if (Schema::hasColumn('subscribers', 'fixed_kwh_price')) {
                $table->dropColumn('fixed_kwh_price');
            }
            if (Schema::hasColumn('subscribers', 'use_fixed_price')) {
                $table->dropColumn('use_fixed_price');
            }
        });
    }
};
