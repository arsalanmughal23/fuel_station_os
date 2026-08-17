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
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reversed_transaction_id')->nullable()->constrained('payment_transactions')->restrictOnDelete();
            $table->string('status');

            // Add check constraints for enum fields
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                // For MySQL, PostgreSQL, etc.
                $table->check("type IN ('income', 'expense')", 'payment_transactions_type_check');
                $table->check("category IN ('fuel_purchase', 'fuel_sale', 'salary', 'utility', 'maintenance', 'other') OR category IS NULL", 'payment_transactions_category_check');
                $table->check("payment_method IN ('cash', 'bank_transfer', 'cheque', 'card') OR payment_method IS NULL", 'payment_transactions_payment_method_check');
                $table->check("status IN ('pending', 'completed', 'failed', 'cancelled')", 'payment_transactions_status_check');
            }
            $table->string('remarks')->nullable();
            $table->dateTime('transacted_at');
            $table->timestamps();

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

            DB::statement('
                CREATE TRIGGER payment_transactions_update_account_balance
                AFTER INSERT ON payment_transactions
                BEGIN
                    UPDATE accounts
                    SET current_balance = (
                        SELECT COALESCE(opening_balance, 0) + COALESCE(SUM(
                            CASE
                                WHEN type = \'income\' THEN amount
                                WHEN type = \'expense\' THEN -amount
                                ELSE 0
                            END
                        ), 0)
                        FROM payment_transactions
                        WHERE account_id = NEW.account_id
                    )
                    WHERE id = NEW.account_id;
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
            DB::statement('DROP TRIGGER IF EXISTS payment_transactions_update_account_balance');
        }

        Schema::dropIfExists('payment_transactions');
    }
};
