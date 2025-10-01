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
Schema::create('collector_generator', function (Blueprint $table) {
    $table->foreignId('collector_id')->constrained()->onDelete('cascade');
    $table->foreignId('generator_id')->constrained()->onDelete('cascade');
    $table->timestamp('assigned_at')->useCurrent();

    $table->primary(['collector_id', 'generator_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collector_generator');
    }
};
