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
       Schema::create('receipts', function (Blueprint $table) {
    $table->id();

    $table->foreignId('invoice_id')->constrained()->onDelete('cascade');

    $table->enum('type', ['user', 'collector'])->comment('من استلم الوصل؟ المستخدم أم الجابي');
    $table->timestamp('issued_at')->useCurrent(); // وقت إصدار الوصل

    $table->timestamps();

    // تحسينات الأداء
    $table->index(['invoice_id', 'type']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
