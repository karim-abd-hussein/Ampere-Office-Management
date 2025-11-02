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
        Schema::table('companies', function (Blueprint $table) {
            // Add generator_id column and foreign key
            $table->foreignId('generator_id')
                ->nullable() // optional but usually useful
                ->after('id') // position in the table
                ->constrained('generators') // references id on generators table
                ->onDelete('cascade'); // delete company if its generator is deleted (optional)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['generator_id']);
            $table->dropColumn('generator_id');
        });
    }
};
