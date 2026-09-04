<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->morphs('stockable');
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // who requested/performed the adjustment
            $table->foreignId('deep_reading_id')->nullable()->constrained()->nullOnDelete(); // nullable - tank-only, not applicable to products
            $table->decimal('quantity', 12, 3); // positive=in, negative=out
            $table->string('unit')->nullable()->comment('unit of measurement (ltr, piece, box, kg, ml)');
            $table->string('adjustment_type')->comment('adjustment for (correction, spillage, evaporation, theft, return, other)');
            $table->string('reason')->nullable();
            $table->dateTime('adjusted_at');
            $table->timestamps();

            // Add check constraint for adjustment_type
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->check("adjustment_type IN ('correction', 'spillage', 'evaporation', 'theft', 'return', 'other')", 'stock_adjustments_adjustment_type_check');
            }

            // Add check constraint for unit
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->check("unit IN ('ltr', 'piece', 'box', 'kg', 'ml')", 'stock_adjustments_unit_check');
            }

            // Add polymorphic index
            $table->index(['stockable_type', 'stockable_id'], 'sa_stockable_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS sa_stockable_idx');
        }

        Schema::dropIfExists('stock_adjustments');
    }
};
