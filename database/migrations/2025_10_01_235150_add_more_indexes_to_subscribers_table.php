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
        Schema::table('subscribers', function (Blueprint $table) {
            // Single column indexes
            $table->index('name');
            $table->index('phone');
            $table->index('status');
            $table->index('subscription_date');
            $table->index('custom_price_per_kwh');
            
            // Composite indexes for common query patterns
            $table->index(['status', 'subscription_date']);
            $table->index(['generator_id', 'subscription_date']);
            $table->index(['status', 'custom_price_per_kwh']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            // Drop single column indexes
            $table->dropIndex(['name']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['status']);
            $table->dropIndex(['subscription_date']);
            $table->dropIndex(['custom_price_per_kwh']);
            
            // Drop composite indexes
            $table->dropIndex(['status', 'subscription_date']);
            $table->dropIndex(['generator_id', 'subscription_date']);
            $table->dropIndex(['status', 'custom_price_per_kwh']);
            
            // Note: Don't drop the existing composite index from your original migration!
            // $table->index(['generator_id', 'status', 'subscription_date']); // Keep this one
        });
    }
};