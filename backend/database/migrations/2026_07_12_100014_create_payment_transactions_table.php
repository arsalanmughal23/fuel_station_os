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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->string('category')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->nullable();
            $table->foreignId('nozzle_reading_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reversed_transaction_id')->nullable()->constrained('payment_transactions')->restrictOnDelete();
            $table->string('status');
            $table->string('remarks')->nullable();
            $table->dateTime('transacted_at');

            $table->index(['account_id', 'transacted_at']);
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement('
                CREATE TRIGGER payment_transactions_prevent_update
                BEFORE UPDATE ON payment_transactions
                BEGIN
                    SELECT RAISE(ABORT, \'payment_transactions is append-only\');
                END
            ');

            DB::statement('
                CREATE TRIGGER payment_transactions_prevent_delete
                BEFORE DELETE ON payment_transactions
                BEGIN
                    SELECT RAISE(ABORT, \'payment_transactions is append-only\');
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
            DB::statement('DROP TRIGGER IF EXISTS payment_transactions_prevent_update');
            DB::statement('DROP TRIGGER IF EXISTS payment_transactions_prevent_delete');
        }

        Schema::dropIfExists('payment_transactions');
    }
};
