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
        Schema::create('restaurant_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'void'])->default('draft');
            $table->decimal('subtotal_sales', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_total', 10, 2);
            $table->integer('orders_count');
            $table->string('pdf_path')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'period_start', 'period_end']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_invoices');
    }
};
