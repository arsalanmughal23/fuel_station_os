<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PriceHistory;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private StockTransactionService $stockTransactionService
    ) {}

    /**
     * Create a new product
     */
    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Update product price and create price history record
     */
    public function updatePrice(Product $product, float $newPrice, int $userId, ?string $reason = null): Product
    {
        return DB::transaction(function () use ($product, $newPrice, $userId, $reason) {
            $oldPrice = $product->unit_price;

            if ($oldPrice == $newPrice) {
                return $product; // No change
            }

            $product->update(['unit_price' => $newPrice]);

            PriceHistory::create([
                'priceable_type' => Product::class,
                'priceable_id'   => $product->id,
                'old_price'      => $oldPrice,
                'new_price'      => $newPrice,
                'user_id'        => $userId,
                'reason'         => $reason ?? 'Price update',
            ]);

            return $product->fresh();
        });
    }

    /**
     * Update product details (not price - use updatePrice for that)
     */
    public function updateProduct(Product $product, array $data): Product
    {
        // Prevent direct stock/price updates via this method
        unset($data['current_stock'], $data['unit_price']);

        $product->update($data);
        return $product->fresh();
    }

    /**
     * Adjust product stock (creates stock adjustment + ledger entry)
     */
    public function adjustStock(
        Product $product,
        float $quantity, // positive = in, negative = out
        string $adjustmentType,
        string $reason,
        int $userId,
        ?string $unit = null
    ): \App\Models\StockAdjustment {
        return app(\App\Services\StockAdjustmentService::class)->recordAdjustment([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => $quantity,
            'unit'           => $unit ?? $product->unit->value,
            'adjustment_type' => $adjustmentType,
            'reason'         => $reason,
        ], $userId);
    }

    /**
     * Get product with current stock
     */
    public function getProductWithStock(int $productId): ?Product
    {
        $product = Product::find($productId);
        if ($product) {
            // Access accessor to get computed stock
            $product->current_stock;
        }
        return $product;
    }

    /**
     * Get price history for a product
     */
    public function getPriceHistory(Product $product)
    {
        return $product->priceHistory()->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts(float $threshold = 10)
    {
        return Product::where('current_stock', '<=', $threshold)->get();
    }
}
