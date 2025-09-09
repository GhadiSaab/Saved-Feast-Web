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
            $table->enum('payment_method', ['CASH_ON_PICKUP', 'ONLINE'])->default('CASH_ON_PICKUP')->after('status');
            $table->decimal('commission_rate', 5, 2)->nullable()->after('payment_method');
            $table->decimal('commission_amount', 10, 2)->nullable()->after('commission_rate');
            $table->timestamp('completed_at')->nullable()->after('commission_amount');
            $table->timestamp('invoiced_at')->nullable()->after('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'commission_rate',
                'commission_amount',
                'completed_at',
                'invoiced_at',
            ]);
        });
    }
};
