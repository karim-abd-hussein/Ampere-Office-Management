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
       Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('subscriber_id')->constrained()->onDelete('cascade');
    $table->foreignId('generator_id')->constrained()->onDelete('cascade');
    $table->foreignId('collector_id')->constrained()->onDelete('cascade');
    $table->foreignId('cycle_id')->constrained()->onDelete('cascade');

    $table->decimal('old_reading', 10, 2)->default(0); // القراءة السابقة
    $table->decimal('new_reading', 10, 2)->nullable(); // القراءة الجديدة
    $table->decimal('consumption', 10, 2)->nullable(); // كمية الاستهلاك

    $table->decimal('unit_price_used', 10, 2); // السعر المستخدم: من الزبون أو المولدة
    $table->decimal('calculated_total', 12, 2)->nullable(); // الاستهلاك × السعر
    $table->decimal('final_amount', 12, 2)->nullable(); // السعر النهائي يلي دفعه الزبون

    $table->timestamp('issued_at')->useCurrent(); // تاريخ إنشاء الفاتورة
    $table->timestamps();

    // Index لتحسين الأداء
    $table->index(['subscriber_id', 'generator_id', 'collector_id', 'cycle_id']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
