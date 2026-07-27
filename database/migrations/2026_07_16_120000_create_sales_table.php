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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // cashier
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete(); // optional customer
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->string('payment_status')->default('pending');
            
            // Add check constraint for payment_status
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                // For MySQL, PostgreSQL, etc.
                $table->check("payment_status IN ('pending', 'paid', 'partially_paid', 'refunded')", 'sales_payment_status_check');
            }
            $table->timestamp('sale_date')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};