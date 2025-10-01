<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
            $table->decimal('ampere', 10, 2)->default(0);
            $table->decimal('price_per_amp', 10, 2)->default(0);
            $table->decimal('fixed_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'cycle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_invoices');
    }
};
