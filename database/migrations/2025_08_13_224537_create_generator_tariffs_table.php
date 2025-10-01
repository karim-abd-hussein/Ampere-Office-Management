<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('generator_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generator_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('from_kwh')->default(0); // بداية الشريحة
            $table->unsignedInteger('to_kwh')->nullable();   // نهاية الشريحة (null = بلا حد أعلى)
            $table->decimal('price_per_kwh', 8, 2);          // سعر الكيلو داخل الشريحة
        });

        // تهيئة: تحويل price_per_kwh القديم لشريحة واحدة 0 → ∞
        $generators = DB::table('generators')->select('id', 'price_per_kwh')->get();
        foreach ($generators as $g) {
            DB::table('generator_tariffs')->insert([
                'generator_id'  => $g->id,
                'from_kwh'      => 0,
                'to_kwh'        => null,
                'price_per_kwh' => (float) ($g->price_per_kwh ?? 0),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('generator_tariffs');
    }
};
