<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');                // اسم الشركة
            $table->string('phone')->nullable();   // رقم الهاتف

            // كان unsignedDecimal -> استبدال بـ decimal
            $table->decimal('ampere', 8, 2)->default(0);         // الأمبيرات
            $table->decimal('price_per_amp', 10, 2)->default(0); // سعر الأمبير
            $table->decimal('fixed_amount', 12, 2)->default(0);  // المبلغ الثابت

            $table->string('status')->default('active'); // active | disconnected | cancelled
            $table->text('notes')->nullable();           // ملاحظات
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
