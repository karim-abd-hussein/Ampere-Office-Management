<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('generators', function (Blueprint $table) {
            $table->id();

            // أساسيّات
            $table->string('name');
            $table->string('code')->unique();               // مثال: ALMZ-01

            // علاقات
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();   // إلى جدول areas
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // صاحب المولدة

            // حقول توافق الفورم
            $table->text('location')->nullable();
            $table->boolean('is_active')->default(true);

            // حالة وتسعير
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->decimal('price_per_kwh', 8, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generators');
    }
};
