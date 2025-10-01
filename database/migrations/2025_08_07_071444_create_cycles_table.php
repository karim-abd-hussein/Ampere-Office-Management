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
        Schema::create('cycles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('generator_id')->constrained()->onDelete('cascade'); // الدورة تابعة لمولدة معينة
    $table->date('start_date');  // بداية الدورة
    $table->date('end_date');    // نهاية الدورة
    $table->decimal('unit_price_per_kwh', 10, 2); // سعر الكيلو في هاي الدورة
    $table->boolean('is_archived')->default(false); // اذا الدورة قديمة وخلصت
    $table->timestamps();

    // Index لتحسين الأداء
    $table->index(['generator_id', 'start_date', 'end_date']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cycles');
    }
};
