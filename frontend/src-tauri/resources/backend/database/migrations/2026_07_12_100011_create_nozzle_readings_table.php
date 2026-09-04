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
        Schema::create('nozzle_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nozzle_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('opening_reading', 12, 3);
            $table->decimal('closing_reading', 12, 3);
            $table->decimal('liters_sold', 12, 3);
            $table->decimal('price_per_liter', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->dateTime('recorded_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nozzle_readings');
    }
};
