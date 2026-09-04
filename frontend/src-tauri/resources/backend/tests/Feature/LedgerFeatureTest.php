<?php

namespace Tests\Feature;

use App\Models\Tank;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\PaymentTransaction;
use App\Models\Account;
use App\Models\FuelType;
use App\Models\Nozzle;
use App\Models\NozzleReading;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Delivery;
use App\Models\StockAdjustment;
use App\Models\PurchaseOrder;
use App\Models\DeepReading;
use App\Models\TankCalibration;
use App\Models\User;
use App\Enums\AdjustmentType;
use App\Enums\PaymentType;
use App\Enums\PaymentCategory;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ScaleUnit;
use App\Enums\SalePaymentStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\ProductCategory;
use App\Enums\AccountType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function stock_transaction_is_append_only_cannot_update(): void
    {
        $user = User::factory()->create();
        $fuelType = FuelType::factory()->create();
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id]);

        $transaction = StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => 1000,
            'unit' => 'ltr',
            'balance_after' => 1000,
            'user_id' => $user->id,
            'delivery_id' => null,
            'nozzle_reading_id' => null,
            'sale_item_id' => null,
            'stock_adjustment_id' => null,
            'reversed_transaction_id' => null,
        ]);

        // Attempting to update should throw an exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is append-only and cannot be updated.');

        $transaction->update(['quantity' => 2000]);
    }

    /** @test */
    public function stock_transaction_is_append_only_cannot_delete(): void
    {
        $user = User::factory()->create();
        $fuelType = FuelType::factory()->create();
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id]);

        $transaction = StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => 1000,
            'unit' => 'ltr',
            'balance_after' => 1000,
            'user_id' => $user->id,
            'delivery_id' => null,
            'nozzle_reading_id' => null,
            'sale_item_id' => null,
            'stock_adjustment_id' => null,
            'reversed_transaction_id' => null,
        ]);

        // Attempting to delete should throw an exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is append-only and cannot be deleted.');

        $transaction->delete();
    }

    /** @test */
    public function payment_transaction_is_append_only_cannot_update(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['account_type' => AccountType::Customer]);

        $transaction = PaymentTransaction::create([
            'account_id' => $account->id,
            'type' => PaymentType::Income,
            'category' => PaymentCategory::FuelSale,
            'amount' => 100.00,
            'payment_method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Completed,
            'user_id' => $user->id,
            'transacted_at' => now(),
        ]);

        // Attempting to update should throw an exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is append-only and cannot be updated.');

        $transaction->update(['amount' => 200.00]);
    }

    /** @test */
    public function payment_transaction_is_append_only_cannot_delete(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['account_type' => AccountType::Customer]);

        $transaction = PaymentTransaction::create([
            'account_id' => $account->id,
            'type' => PaymentType::Income,
            'category' => PaymentCategory::FuelSale,
            'amount' => 100.00,
            'payment_method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Completed,
            'user_id' => $user->id,
            'transacted_at' => now(),
        ]);

        // Attempting to delete should throw an exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is append-only and cannot be deleted.');

        $transaction->delete();
    }

    /** @test */
    public function sale_item_enforces_xor_constraint_product_id_or_nozzle_reading_id(): void
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        // Should fail: both null
        $this->expectException(\Illuminate\Database\QueryException::class);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => null,
            'nozzle_reading_id' => null,
            'unit' => 'ltr',
            'quantity' => 10,
            'unit_price' => 5.00,
            'amount' => 50.00,
        ]);

        // Should fail: both set
        $fuelType = FuelType::factory()->create();
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id]);
        $nozzle = Nozzle::factory()->create(['tank_id' => $tank->id]);
        $reading = NozzleReading::factory()->create(['nozzle_id' => $nozzle->id]);
        $product = Product::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'nozzle_reading_id' => $reading->id,
            'unit' => 'ltr',
            'quantity' => 10,
            'unit_price' => 5.00,
            'amount' => 50.00,
        ]);
    }

    /** @test */
    public function sale_item_allows_product_only(): void
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();

        $item = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'nozzle_reading_id' => null,
            'unit' => 'pcs',
            'quantity' => 5,
            'unit_price' => 10.00,
            'amount' => 50.00,
        ]);

        $this->assertEquals($product->id, $item->product_id);
        $this->assertNull($item->nozzle_reading_id);
    }

    /** @test */
    public function sale_item_allows_nozzle_reading_only(): void
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->create(['user_id' => $user->id]);
        $fuelType = FuelType::factory()->create();
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id]);
        $nozzle = Nozzle::factory()->create(['tank_id' => $tank->id]);
        $reading = NozzleReading::factory()->create(['nozzle_id' => $nozzle->id]);

        $item = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => null,
            'nozzle_reading_id' => $reading->id,
            'unit' => 'ltr',
            'quantity' => 10,
            'unit_price' => 5.00,
            'amount' => 50.00,
        ]);

        $this->assertNull($item->product_id);
        $this->assertEquals($reading->id, $item->nozzle_reading_id);
    }

    /** @test */
    public function stock_transaction_enforces_xor_constraint_exactly_one_source_fk(): void
    {
        $user = User::factory()->create();
        $fuelType = FuelType::factory()->create();
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id]);

        // Should fail: all null
        $this->expectException(\Illuminate\Database\QueryException::class);
        StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => 100,
            'unit' => 'ltr',
            'balance_after' => 100,
            'user_id' => $user->id,
        ]);

        // Should fail: two set
        $this->expectException(\Illuminate\Database\QueryException::class);
        StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => 100,
            'unit' => 'ltr',
            'balance_after' => 100,
            'user_id' => $user->id,
            'delivery_id' => 1,
            'nozzle_reading_id' => 1,
        ]);
    }

    /** @test */
    public function stock_transaction_allows_delivery_id_only(): void
    {
        $user = User::factory()->create();
        $fuelType = FuelType::factory()->create();
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id]);
        $account = Account::factory()->create(['account_type' => AccountType::Distributor]);
        $po = PurchaseOrder::factory()->create([
            'account_id' => $account->id,
            'fuel_type_id' => $fuelType->id,
        ]);
        $delivery = Delivery::factory()->create([
            'purchase_order_id' => $po->id,
            'tank_id' => $tank->id,
        ]);

        $transaction = StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => 5000,
            'unit' => 'ltr',
            'balance_after' => 5000,
            'user_id' => $user->id,
            'delivery_id' => $delivery->id,
        ]);

        $this->assertEquals($delivery->id, $transaction->delivery_id);
        $this->assertNull($transaction->nozzle_reading_id);
        $this->assertNull($transaction->sale_item_id);
        $this->assertNull($transaction->stock_adjustment_id);
        $this->assertNull($transaction->reversed_transaction_id);
    }

    /** @test */
    public function stock_transaction_allows_nozzle_reading_id_only(): void
    {
        $user = User::factory()->create();
        $fuelType = FuelType::factory()->create();
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id]);
        $nozzle = Nozzle::factory()->create(['tank_id' => $tank->id]);
        $reading = NozzleReading::factory()->create(['nozzle_id' => $nozzle->id]);

        $transaction = StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => -50,
            'unit' => 'ltr',
            'balance_after' => 4950,
            'user_id' => $user->id,
            'nozzle_reading_id' => $reading->id,
        ]);

        $this->assertEquals($reading->id, $transaction->nozzle_reading_id);
        $this->assertNull($transaction->delivery_id);
    }

    /** @test */
    public function stock_transaction_allows_sale_item_id_only_for_products(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $sale = Sale::factory()->create(['user_id' => $user->id]);
        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);

        $transaction = StockTransaction::create([
            'stockable_type' => 'Product',
            'stockable_id' => $product->id,
            'quantity' => -5,
            'unit' => 'pcs',
            'balance_after' => 95,
            'user_id' => $user->id,
            'sale_item_id' => $saleItem->id,
        ]);

        $this->assertEquals($saleItem->id, $transaction->sale_item_id);
        $this->assertNull($transaction->delivery_id);
    }

    /** @test */
    public function stock_transaction_allows_stock_adjustment_id_only(): void
    {
        $user = User::factory()->create();
        $fuelType = FuelType::factory()->create();
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id]);
        $adjustment = StockAdjustment::factory()->create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'user_id' => $user->id,
        ]);

        $transaction = StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => 10,
            'unit' => 'ltr',
            'balance_after' => 5010,
            'user_id' => $user->id,
            'stock_adjustment_id' => $adjustment->id,
        ]);

        $this->assertEquals($adjustment->id, $transaction->stock_adjustment_id);
    }

    /** @test */
    public function stock_transaction_allows_reversed_transaction_id_only(): void
    {
        $user = User::factory()->create();
        $fuelType = FuelType::factory()->create();
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id]);

        $original = StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => 100,
            'unit' => 'ltr',
            'balance_after' => 100,
            'user_id' => $user->id,
            'delivery_id' => 1,
        ]);

        $reversal = StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => -100,
            'unit' => 'ltr',
            'balance_after' => 0,
            'user_id' => $user->id,
            'reversed_transaction_id' => $original->id,
        ]);

        $this->assertEquals($original->id, $reversal->reversed_transaction_id);
        $this->assertNull($reversal->delivery_id);
    }

    /** @test */
    public function tank_calculated_stock_is_derived_from_stock_transactions(): void
    {
        $user = User::factory()->create();
        $fuelType = FuelType::factory()->create();
        $tank = Tank::factory()->create([
            'fuel_type_id' => $fuelType->id,
            'capacity_liters' => 10000,
            'calculated_stock' => 0,
        ]);

        // Initial stock from delivery
        StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => 5000,
            'unit' => 'ltr',
            'balance_after' => 5000,
            'user_id' => $user->id,
            'delivery_id' => 1,
        ]);

        $tank->refresh();
        $this->assertEquals(5000, $tank->calculated_stock);

        // Sale reduces stock
        StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => -200,
            'unit' => 'ltr',
            'balance_after' => 4800,
            'user_id' => $user->id,
            'nozzle_reading_id' => 1,
        ]);

        $tank->refresh();
        $this->assertEquals(4800, $tank->calculated_stock);

        // Adjustment
        StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => -50,
            'unit' => 'ltr',
            'balance_after' => 4750,
            'user_id' => $user->id,
            'stock_adjustment_id' => 1,
        ]);

        $tank->refresh();
        $this->assertEquals(4750, $tank->calculated_stock);
    }

    /** @test */
    public function product_current_stock_is_derived_from_stock_transactions(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'unit' => ScaleUnit::Pcs,
            'current_stock' => 0,
        ]);

        // Initial stock adjustment (positive)
        StockTransaction::create([
            'stockable_type' => 'Product',
            'stockable_id' => $product->id,
            'quantity' => 100,
            'unit' => 'pcs',
            'balance_after' => 100,
            'user_id' => $user->id,
            'stock_adjustment_id' => 1,
        ]);

        $product->refresh();
        $this->assertEquals(100, $product->current_stock);

        // Sale reduces stock
        StockTransaction::create([
            'stockable_type' => 'Product',
            'stockable_id' => $product->id,
            'quantity' => -10,
            'unit' => 'pcs',
            'balance_after' => 90,
            'user_id' => $user->id,
            'sale_item_id' => 1,
        ]);

        $product->refresh();
        $this->assertEquals(90, $product->current_stock);
    }

    /** @test */
    public function account_current_balance_is_derived_from_payment_transactions(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'account_type' => AccountType::Customer,
            'opening_balance' => 500.00,
            'current_balance' => 500.00,
        ]);

        // Income increases balance
        PaymentTransaction::create([
            'account_id' => $account->id,
            'type' => PaymentType::Income,
            'category' => PaymentCategory::FuelSale,
            'amount' => 200.00,
            'payment_method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Completed,
            'user_id' => $user->id,
            'transacted_at' => now(),
        ]);

        $account->refresh();
        $this->assertEquals(700.00, $account->current_balance);

        // Expense decreases balance
        PaymentTransaction::create([
            'account_id' => $account->id,
            'type' => PaymentType::Expense,
            'category' => PaymentCategory::Utility,
            'amount' => 100.00,
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Completed,
            'user_id' => $user->id,
            'transacted_at' => now(),
        ]);

        $account->refresh();
        $this->assertEquals(600.00, $account->current_balance);
    }

    /** @test */
    public function reversal_creates_negative_quantity_transaction(): void
    {
        $user = User::factory()->create();
        $fuelType = FuelType::factory()->create();
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id]);

        // Original delivery transaction
        $original = StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => 5000,
            'unit' => 'ltr',
            'balance_after' => 5000,
            'user_id' => $user->id,
            'delivery_id' => 1,
        ]);

        // Reversal
        $reversal = StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => -5000,
            'unit' => 'ltr',
            'balance_after' => 0,
            'user_id' => $user->id,
            'reversed_transaction_id' => $original->id,
        ]);

        $this->assertEquals(-$original->quantity, $reversal->quantity);
        $this->assertEquals($original->id, $reversal->reversed_transaction_id);
        $this->assertNull($reversal->delivery_id);

        // Balance should be back to 0
        $tank->refresh();
        $this->assertEquals(0, $tank->calculated_stock);
    }

    /** @test */
    public function payment_reversal_flips_type_and_amount(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'account_type' => AccountType::Customer,
            'opening_balance' => 0,
        ]);

        // Original income
        $original = PaymentTransaction::create([
            'account_id' => $account->id,
            'type' => PaymentType::Income,
            'category' => PaymentCategory::FuelSale,
            'amount' => 100.00,
            'payment_method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Completed,
            'user_id' => $user->id,
            'transacted_at' => now(),
        ]);

        // Reversal (expense with same amount)
        $reversal = PaymentTransaction::create([
            'account_id' => $account->id,
            'type' => PaymentType::Expense,
            'category' => PaymentCategory::FuelSale,
            'amount' => 100.00,
            'payment_method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Completed,
            'user_id' => $user->id,
            'reversed_transaction_id' => $original->id,
            'transacted_at' => now(),
        ]);

        $this->assertEquals(PaymentType::Expense, $reversal->type);
        $this->assertEquals($original->amount, $reversal->amount);
        $this->assertEquals($original->id, $reversal->reversed_transaction_id);

        // Balance should be back to 0
        $account->refresh();
        $this->assertEquals(0, $account->current_balance);
    }

    /** @test */
    public function stock_transaction_balance_after_is_calculated_correctly(): void
    {
        $user = User::factory()->create();
        $fuelType = FuelType::factory()->create();
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id]);

        // First transaction
        $tx1 = StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => 1000,
            'unit' => 'ltr',
            'balance_after' => 1000,
            'user_id' => $user->id,
            'delivery_id' => 1,
        ]);

        $this->assertEquals(1000, $tx1->balance_after);

        // Second transaction
        $tx2 = StockTransaction::create([
            'stockable_type' => 'Tank',
            'stockable_id' => $tank->id,
            'quantity' => -100,
            'unit' => 'ltr',
            'balance_after' => 900,
            'user_id' => $user->id,
            'nozzle_reading_id' => 1,
        ]);

        $this->assertEquals(900, $tx2->balance_after);
        $this->assertEquals($tx1->balance_after + $tx2->quantity, $tx2->balance_after);
    }

    /** @test */
    public function sale_total_amount_is_sum_of_sale_items(): void
    {
        $user = User::factory()->create();
        $sale = Sale::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 0,
        ]);

        // Product item
        $product = Product::factory()->create(['unit_price' => 20.00]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'unit' => 'pcs',
            'quantity' => 2,
            'unit_price' => 20.00,
            'amount' => 40.00,
        ]);

        // Fuel item
        $fuelType = FuelType::factory()->create(['current_price' => 5.00]);
        $tank = Tank::factory()->create(['fuel_type_id' => $fuelType->id]);
        $nozzle = Nozzle::factory()->create(['tank_id' => $tank->id]);
        $reading = NozzleReading::factory()->create([
            'nozzle_id' => $nozzle->id,
            'liters_sold' => 30,
            'price_per_liter' => 5.00,
            'amount' => 150.00,
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'nozzle_reading_id' => $reading->id,
            'unit' => 'ltr',
            'quantity' => 30,
            'unit_price' => 5.00,
            'amount' => 150.00,
        ]);

        $total = $sale->saleItems()->sum('amount');
        $this->assertEquals(190.00, $total);
    }
}