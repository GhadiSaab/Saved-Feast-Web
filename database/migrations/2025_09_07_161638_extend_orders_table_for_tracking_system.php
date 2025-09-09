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
            // Update status enum to include new states
            $table->enum('status', [
                'PENDING',
                'ACCEPTED',
                'READY_FOR_PICKUP',
                'COMPLETED',
                'CANCELLED_BY_CUSTOMER',
                'CANCELLED_BY_RESTAURANT',
                'EXPIRED',
            ])->default('PENDING')->change();

            // Add pickup window fields
            $table->timestamp('pickup_window_start')->nullable()->after('pickup_time');
            $table->timestamp('pickup_window_end')->nullable()->after('pickup_window_start');

            // Add state transition timestamps
            $table->timestamp('accepted_at')->nullable()->after('pickup_window_end');
            $table->timestamp('ready_at')->nullable()->after('accepted_at');
            $table->timestamp('cancelled_at')->nullable()->after('ready_at');
            $table->timestamp('expired_at')->nullable()->after('cancelled_at');

            // Add cancellation fields
            $table->text('cancel_reason')->nullable()->after('expired_at');
            $table->enum('cancelled_by', ['customer', 'restaurant', 'system'])->nullable()->after('cancel_reason');

            // Add pickup code fields
            $table->text('pickup_code_encrypted')->nullable()->after('cancelled_by');
            $table->smallInteger('pickup_code_attempts')->default(0)->after('pickup_code_encrypted');
            $table->timestamp('pickup_code_last_sent_at')->nullable()->after('pickup_code_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert status enum to original
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending')->change();

            // Drop new columns
            $table->dropColumn([
                'pickup_window_start',
                'pickup_window_end',
                'accepted_at',
                'ready_at',
                'cancelled_at',
                'expired_at',
                'cancel_reason',
                'cancelled_by',
                'pickup_code_encrypted',
                'pickup_code_attempts',
                'pickup_code_last_sent_at',
            ]);
        });
    }
};
