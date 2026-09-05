<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\NozzleReading;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private StockTransactionService $stockTransactionService,
        private PaymentTransactionService $paymentTransactionService
    ) {}

    /**
     * Create a sale with items (fuel and/or products) and post ledger entries
     * 
     * @param array $data {
     *     int user_id,
     *     int|null account_id,
     *     string payment_method, // cash, bank_transfer, cheque, card
     *     array items [
     *         // Product item
     *         'type' => 'product',
     *         'product_id' => int,
     *         'quantity' => float,
     *         // OR Fuel item
     *         'type' => 'fuel',
     *         'nozzle_reading_id' => int,
     *     ]
     * }
     */
    public function createSale(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            $itemsData = $data['items'] ?? [];
            
            if (empty($itemsData)) {
                throw new \InvalidArgumentException('Sale must have at least one item');
            }

            // Calculate total amount from items
            $totalAmount = 0;
            foreach ($itemsData as $item) {
                if ($item['type'] === 'product') {
                    $product = Product::findOrFail($item['product_id']);
                    $unitPrice = $item['unit_price'] ?? $product->unit_price;
                    $totalAmount += ($item['quantity'] ?? 0) * $unitPrice;
                } elseif ($item['type'] === 'fuel') {
                    $reading = NozzleReading::findOrFail($item['nozzle_reading_id']);
                    $totalAmount += $reading->amount;
                }
            }

            // Create the sale
            $sale = Sale::create([
                'user_id' => $userId,
                'account_id' => $data['account_id'] ?? null,
                'total_amount' => $totalAmount,
                'paid_amount' => $data['paid_amount'] ?? $totalAmount,
                'change_amount' => max(0, ($data['paid_amount'] ?? $totalAmount) - $totalAmount),
                'payment_status' => ($data['paid_amount'] ?? $totalAmount) >= $totalAmount ? 'paid' : 'partially_paid',
                'sale_date' => $data['sale_date'] ?? now(),
            ]);

            // Create sale items and post stock transactions for products
            foreach ($itemsData as $item) {
                if ($item['type'] === 'product') {
                    $this->createProductSaleItem($sale, $item, $userId);
                } elseif ($item['type'] === 'fuel') {
                    $this->createFuelSaleItem($sale, $item);
                }
            }

            // Post payment transaction for revenue
            if ($sale->paid_amount > 0) {
                $this->paymentTransactionService->append(
                    accountId: $sale->account_id ?? $this->getOrCreateWalkInAccount(),
                    type: 'income',
                    category: 'fuel_sale',
                    amount: $sale->paid_amount,
                    paymentMethod: $data['payment_method'] ?? 'cash',
                    status: 'completed',
                    userId: $userId,
                    saleId: $sale->id,
                    remarks: "Sale #{$sale->id} payment"
                );
            }

            return $sale->load(['saleItems.product', 'saleItems.nozzleReading', 'account']);
        });
    }

    /**
     * Create product sale item and post stock transaction
     */
    private function createProductSaleItem(Sale $sale, array $item, int $userId): SaleItem
    {
        $product = Product::findOrFail($item['product_id']);
        $quantity = $item['quantity'] ?? 0;
        $unitPrice = $item['unit_price'] ?? $product->unit_price;
        $totalPrice = $quantity * $unitPrice;

        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'unit' => $product->unit,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => $totalPrice,
        ]);

        // Post stock transaction for product stock-out
        $this->stockTransactionService->append(
            stockable: $product,
            quantity: -$quantity, // Negative for stock-out
            unit: $product->unit->value,
            userId: $userId,
            sourceFK: ['sale_item_id' => $saleItem->id],
            remarks: "Product sale: {$product->title} x {$quantity} {$product->unit->value}"
        );

        return $saleItem;
    }

    /**
     * Create fuel sale item (links existing nozzle reading to sale)
     */
    private function createFuelSaleItem(Sale $sale, array $item): SaleItem
    {
        $reading = NozzleReading::with('nozzle.tank.fuelType')->findOrFail($item['nozzle_reading_id']);

        return SaleItem::create([
            'sale_id' => $sale->id,
            'nozzle_reading_id' => $reading->id,
            'unit' => 'ltr',
            'quantity' => $reading->liters_sold,
            'unit_price' => $reading->price_per_liter,
            'amount' => $reading->amount,
        ]);
    }

    /**
     * Get or create a walk-in customer account for anonymous sales
     */
    private function getOrCreateWalkInAccount(): int
    {
        // This would typically be a specific account for walk-in customers
        // For now, we'll require account_id for paid sales
        throw new \LogicException('Account ID is required for paid sales. Create a walk-in customer account first.');
    }

    /**
     * Create sale without posting ledger entries (for manual entry)
     */
    public function create(array $data): Sale
    {
        return Sale::create($data);
    }
}
