<?php

namespace App\Services;

use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockTransactionService
{
    /**
     * Append a new stock transaction (append-only ledger)
     */
    public function append(
        Model $stockable,
        float $quantity,
        string $unit,
        int $userId,
        array $sourceFK = [],
        ?string $remarks = null
    ): StockTransaction {
        // Validate XOR constraint: exactly one source FK must be set
        $this->validateXorConstraint($sourceFK);

        return DB::transaction(function () use ($stockable, $quantity, $unit, $userId, $sourceFK, $remarks) {
            // Calculate balance after this transaction
            // Use short morph key from morph map (Tank, Product, FuelType)
            $stockableType = $this->getMorphKey($stockable);
            
            $lastBalance = StockTransaction::where('stockable_type', $stockableType)
                ->where('stockable_id', $stockable->id)
                ->latest()
                ->value('balance_after') ?? 0;

            return StockTransaction::create(array_merge([
                'stockable_type' => $stockableType,
                'stockable_id'   => $stockable->id,
                'quantity'       => $quantity,
                'unit'           => $unit,
                'balance_after'  => $lastBalance + $quantity,
                'user_id'        => $userId,
                'remarks'        => $remarks,
            ], $sourceFK));
        });
    }

    /**
     * Get the short morph key for a model (Tank, Product, FuelType)
     */
    private function getMorphKey(Model $model): string
    {
        $class = get_class($model);
        $morphMap = [
            \App\Models\Tank::class => 'Tank',
            \App\Models\Product::class => 'Product',
            \App\Models\FuelType::class => 'FuelType',
        ];
        
        return $morphMap[$class] ?? $class;
    }

    /**
     * Validate XOR constraint: exactly one of the source FKs must be set
     */
    private function validateXorConstraint(array $sourceFK): void
    {
        $allowedKeys = [
            'delivery_id',
            'nozzle_reading_id',
            'sale_item_id',
            'stock_adjustment_id',
            'reversed_transaction_id',
        ];

        $setKeys = array_intersect(array_keys($sourceFK), $allowedKeys);
        
        if (count($setKeys) !== 1) {
            throw new \InvalidArgumentException(
                'StockTransaction requires exactly one source FK. ' .
                'Provided: ' . implode(', ', $setKeys) . '. ' .
                'Allowed: ' . implode(', ', $allowedKeys)
            );
        }
    }

    /**
     * Reverse a stock transaction by creating an opposite entry
     */
    public function reverse(
        StockTransaction $original,
        int $userId,
        string $reason
    ): StockTransaction {
        return $this->append(
            stockable: $original->stockable,
            quantity: -$original->quantity,
            unit: $original->unit,
            userId: $userId,
            sourceFK: ['reversed_transaction_id' => $original->id],
            remarks: "Reversal: {$reason}"
        );
    }

    // Keep original create method for backward compatibility
    public function create(array $data): StockTransaction
    {
        return StockTransaction::create($data);
    }
}
