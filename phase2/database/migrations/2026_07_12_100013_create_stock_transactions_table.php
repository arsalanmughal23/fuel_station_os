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
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tank_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_liters', 12, 3);
            $table->decimal('balance_after', 12, 3);
            $table->foreignId('delivery_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('nozzle_reading_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('stock_adjustment_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reversed_transaction_id')->nullable()->constrained('stock_transactions')->restrictOnDelete();
            $table->string('remarks')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tank_id', 'created_at']);
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement('
                CREATE TRIGGER stock_transactions_prevent_update
                BEFORE UPDATE ON stock_transactions
                BEGIN
                    SELECT RAISE(ABORT, \'stock_transactions is append-only\');
                END
            ');

            DB::statement('
                CREATE TRIGGER stock_transactions_prevent_delete
                BEFORE DELETE ON stock_transactions
                BEGIN
                    SELECT RAISE(ABORT, \'stock_transactions is append-only\');
                END
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS stock_transactions_prevent_update');
            DB::statement('DROP TRIGGER IF EXISTS stock_transactions_prevent_delete');
        }

        Schema::dropIfExists('stock_transactions');
    }
};
