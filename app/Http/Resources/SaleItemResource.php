<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_id' => $this->sale_id,
            'type' => $this->product_id ? 'product' : 'fuel',
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'nozzle_reading_id' => $this->nozzle_reading_id,
            'nozzle_reading' => new NozzleReadingResource($this->whenLoaded('nozzleReading')),
            'unit' => $this->unit?->value,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'amount' => $this->amount,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}