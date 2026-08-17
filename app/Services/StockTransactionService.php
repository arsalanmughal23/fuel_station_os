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
        return DB::transaction(function () use ($stockable, $quantity, $unit, $userId, $sourceFK, $remarks) {
            // Calculate balance after this transaction
            $lastBalance = StockTransaction::where('stockable_type', get_class($stockable))
                ->where('stockable_id', $stockable->id)
                ->latest()
                ->value('balance_after') ?? 0;

            return StockTransaction::create(array_merge([
                'stockable_type' => get_class($stockable),
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
