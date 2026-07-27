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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('tank_id')->constrained()->restrictOnDelete();
            $table->string('vehicle_reg_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->decimal('invoiced_liters', 12, 3);
            $table->decimal('deep_reading_before', 12, 3)->nullable();
            $table->decimal('deep_reading_after', 12, 3)->nullable();
            $table->decimal('actual_received_liters', 12, 3);
            $table->decimal('shortage_from_order', 12, 3)->default(0);
            $table->decimal('shortage_from_delivery', 12, 3)->default(0);
            $table->dateTime('received_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
