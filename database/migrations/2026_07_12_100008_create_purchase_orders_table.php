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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('fuel_type_id')->constrained()->restrictOnDelete();
            $table->decimal('ordered_liters', 12, 3);
            $table->decimal('price_per_liter', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('invoice_number')->nullable();
            $table->string('status');

            // Add check constraint for status
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                // For MySQL, PostgreSQL, etc.
                $table->check("status IN ('pending', 'partially_received', 'received', 'cancelled')", 'purchase_orders_status_check');
            }
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
