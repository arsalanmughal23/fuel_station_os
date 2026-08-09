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
        Schema::create("stock_transactions", function (Blueprint $table) {
            $table->id();
            $table->morphs("stockable");
            $table->string("unit")->nullable(); // unit of measurement (ltr, piece, box, kg, ml)
            $table->decimal("quantity", 12, 3); // positive=in, negative=out
            $table->decimal("balance_after", 12, 3);
            $table->foreignId("user_id")->constrained()->restrictOnDelete(); // who performed the transaction
            $table->foreignId("delivery_id")->nullable()->constrained()->restrictOnDelete(); // nullable - tank stock-in source
            $table->foreignId("nozzle_reading_id")->nullable()->constrained()->restrictOnDelete(); // nullable - tank stock-out source
            $table->foreignId("sale_item_id")->nullable()->constrained()->restrictOnDelete(); // nullable - product stock-out source
            $table->foreignId("stock_adjustment_id")->nullable()->constrained()->restrictOnDelete(); // nullable - adjustment source, either stockable type
            $table->foreignId("reversed_transaction_id")->nullable()->constrained("stock_transactions")->restrictOnDelete(); // nullable - for reversals
            $table->string("remarks")->nullable();
            $table->timestamps();

            $table->index(["user_id", "created_at"]);
            $table->index(["stockable_type", "stockable_id", "created_at"], "st_stockable_idx");

            // Add check constraint for unit
            if (Schema::getConnection()->getDriverName() !== "sqlite") {
                $table->check("unit IN (\"ltr\", \"piece\", \"box\", \"kg\", \"ml\")", "stock_transactions_unit_check");
            }
        });

        if (Schema::getConnection()->getDriverName() === "sqlite") {
            DB::statement("
                CREATE TRIGGER stock_transactions_prevent_update
                BEFORE UPDATE ON stock_transactions
                BEGIN
                    SELECT RAISE(ABORT, \"stock_transactions is append-only\");
                END
            ");

            DB::statement("
                CREATE TRIGGER stock_transactions_prevent_delete
                BEFORE DELETE ON stock_transactions
                BEGIN
                    SELECT RAISE(ABORT, \"stock_transactions is append-only\");
                END
            ");

            // Trigger to maintain tanks.calculated_stock
            DB::statement("
                CREATE TRIGGER update_tank_stock_after_insert
                AFTER INSERT ON stock_transactions
                WHEN NEW.stockable_type = \"App\\\\Models\\\\Tank\"
                BEGIN
                    UPDATE tanks
                    SET calculated_stock = (
                        SELECT COALESCE(SUM(quantity), 0)
                        FROM stock_transactions
                        WHERE stockable_type = \"App\\\\Models\\\\Tank\"
                        AND stockable_id = NEW.stockable_id
                    )
                    WHERE id = NEW.stockable_id;
                END
            ");

            // XOR constraint: exactly one of delivery_id, nozzle_reading_id, sale_item_id, stock_adjustment_id, reversed_transaction_id must be set
            DB::statement("
                CREATE TRIGGER stock_transactions_xor_check
                BEFORE INSERT ON stock_transactions
                BEGIN
                    SELECT CASE
                        WHEN (
                            (NEW.delivery_id IS NULL) +
                            (NEW.nozzle_reading_id IS NULL) +
                            (NEW.sale_item_id IS NULL) +
                            (NEW.stock_adjustment_id IS NULL) +
                            (NEW.reversed_transaction_id IS NULL)
                        ) != 4
                        THEN RAISE(ABORT, \"stock_transactions must have exactly one of delivery_id, nozzle_reading_id, sale_item_id, stock_adjustment_id, or reversed_transaction_id\")
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
            DB::statement("DROP TRIGGER IF EXISTS stock_transactions_prevent_update");
            DB::statement("DROP TRIGGER IF EXISTS stock_transactions_prevent_delete");
            DB::statement("DROP TRIGGER IF EXISTS update_tank_stock_after_insert");
            DB::statement("DROP TRIGGER IF EXISTS stock_transactions_xor_check");
        }

        Schema::dropIfExists("stock_transactions");
    }
};
