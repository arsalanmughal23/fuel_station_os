<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order_id' => $this->purchase_order_id,
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchaseOrder')),
            'tank_id' => $this->tank_id,
            'tank' => new TankResource($this->whenLoaded('tank')),
            'vehicle_reg_number' => $this->vehicle_reg_number,
            'driver_name' => $this->driver_name,
            'invoiced_liters' => $this->invoiced_liters,
            'deep_reading_before' => $this->deep_reading_before,
            'deep_reading_after' => $this->deep_reading_after,
            'actual_received_liters' => $this->actual_received_liters,
            'shortage_from_order' => $this->shortage_from_order,
            'shortage_from_delivery' => $this->shortage_from_delivery,
            'received_at' => $this->received_at?->toISOString(),
            'stock_transaction' => new StockTransactionResource($this->whenLoaded('stockTransaction')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}