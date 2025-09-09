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
        Schema::table('orders', function (Blueprint $table) {
            // Add indexes for performance optimization
            $table->index('status');
            $table->index('pickup_window_end');
            $table->index('completed_at');

            // Composite index for provider orders filtering
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['pickup_window_end']);
            $table->dropIndex(['completed_at']);
            $table->dropIndex(['status', 'created_at']);
        });
    }
};
