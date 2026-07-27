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
        Schema::create('tanks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_type_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->decimal('capacity_liters', 12, 3);
            $table->decimal('calculated_stock', 12, 3)->default(0);
            $table->timestamps();

            $table->unique(['fuel_type_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tanks');
    }
};
