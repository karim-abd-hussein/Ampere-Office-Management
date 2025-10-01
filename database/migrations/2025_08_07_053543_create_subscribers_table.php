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
       Schema::create('subscribers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('phone')->nullable(); // للاتصال السريع
    $table->string('meter_number')->unique(); // رقم العداد الخاص
    $table->foreignId('generator_id')->constrained()->onDelete('cascade'); // ربط بالمولدة
    $table->decimal('custom_price_per_kwh', 8, 2)->nullable(); // سعر خاص اختياري
    $table->enum('status', ['active', 'disconnected', 'cancelled'])->default('active');
    $table->date('subscription_date'); // من امتى اشترك
    $table->timestamps();

    // ⚡ Index لتحسين الأداء في الفلترة
    $table->index(['generator_id', 'status', 'subscription_date']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
