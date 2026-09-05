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
        Schema::create('deep_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tank_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('deep_cm', 12, 3);
            $table->decimal('calibrated_volume_liters', 12, 3);
            $table->decimal('system_stock_at_reading', 12, 3);
            $table->decimal('variance_liters', 12, 3);
            $table->dateTime('recorded_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deep_readings');
    }
};
