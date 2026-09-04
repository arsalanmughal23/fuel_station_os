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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category');
            $table->string('unit')->nullable()->comment('Unit of product (ltr, piece, box, kg, ml)');
            $table->decimal('unit_price', 12, 4);
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->timestamps();

            // Add check constraint for unit
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                // For MySQL, PostgreSQL, etc.
                $table->check("unit IN ('ltr', 'piece', 'box', 'kg', 'ml')", 'products_unit_check');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
