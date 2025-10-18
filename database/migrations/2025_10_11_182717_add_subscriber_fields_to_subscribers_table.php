<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('subscriber_name')->nullable();
            $table->string('subscriber_phone')->nullable();
            $table->string('subscriber_meter_number')->nullable();
            $table->string('subscriber_box_number')->nullable();
            $table->boolean('subscriber_use_fixed_price')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'subscriber_name',
                'subscriber_phone',
                'subscriber_meter_number',
                'subscriber_box_number',
                'subscriber_use_fixed_price',
            ]);
        });
    }
};
