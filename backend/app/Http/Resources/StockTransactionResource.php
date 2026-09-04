<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stockable_type' => $this->stockable_type,
            'stockable_id' => $this->stockable_id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'balance_after' => $this->balance_after,
            'source' => $this->getSource(),
            'delivery' => new DeliveryResource($this->whenLoaded('delivery')),
            'nozzle_reading' => new NozzleReadingResource($this->whenLoaded('nozzleReading')),
            'sale_item' => new SaleItemResource($this->whenLoaded('saleItem')),
            'stock_adjustment' => new StockAdjustmentResource($this->whenLoaded('stockAdjustment')),
            'reversed_transaction' => new StockTransactionResource($this->whenLoaded('reversedTransaction')),
            'remarks' => $this->remarks,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function getSource(): string
    {
        if ($this->delivery_id) return 'delivery';
        if ($this->nozzle_reading_id) return 'nozzle_reading';
        if ($this->sale_item_id) return 'sale_item';
        if ($this->stock_adjustment_id) return 'stock_adjustment';
        if ($this->reversed_transaction_id) return 'reversal';
        return 'unknown';
    }
}