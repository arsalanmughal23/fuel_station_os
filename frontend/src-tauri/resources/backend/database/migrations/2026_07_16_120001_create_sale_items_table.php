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
        Schema::create("sale_items", function (Blueprint $table) {
            $table->id();
            $table->foreignId("sale_id")->constrained()->restrictOnDelete();
            $table->foreignId("product_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("nozzle_reading_id")->nullable()->constrained()->nullOnDelete();
            $table->string("unit");
            $table->decimal("quantity", 12, 3);
            $table->decimal("unit_price", 12, 4);
            $table->decimal("amount", 12, 2); // ERD v4: amount (not total_price)
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() === "sqlite") {
            DB::statement("
                CREATE TRIGGER sale_items_xor_check
                BEFORE INSERT ON sale_items
                BEGIN
                    SELECT CASE
                        WHEN (NEW.product_id IS NULL AND NEW.nozzle_reading_id IS NULL)
                          OR (NEW.product_id IS NOT NULL AND NEW.nozzle_reading_id IS NOT NULL)
                        THEN RAISE(ABORT, \"sale_items must have exactly one of product_id or nozzle_reading_id\")
                    END;
                END
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === "sqlite") {
            DB::statement("DROP TRIGGER IF EXISTS sale_items_xor_check");
        }

        Schema::dropIfExists("sale_items");
    }
};
