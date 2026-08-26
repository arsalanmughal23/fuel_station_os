<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stockable = $this->whenLoaded('stockable');
        
        return [
            'id' => $this->id,
            'stockable_type' => $this->stockable_type,
            'stockable_id' => $this->stockable_id,
            'stockable' => $stockable ? [
                'id' => $stockable->id,
                'name' => $stockable->name ?? $stockable->title ?? 'Unknown',
                'type' => $this->stockable_type === 'App\\Models\\Tank' ? 'tank' : 'product',
            ] : null,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'deep_reading_id' => $this->deep_reading_id,
            'deep_reading' => new DeepReadingResource($this->whenLoaded('deepReading')),
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'adjustment_type' => $this->adjustment_type?->value,
            'reason' => $this->reason,
            'adjusted_at' => $this->adjusted_at?->toISOString(),
            'stock_transaction' => new StockTransactionResource($this->whenLoaded('stockTransaction')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}