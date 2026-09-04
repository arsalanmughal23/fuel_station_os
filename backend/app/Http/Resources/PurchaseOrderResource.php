<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'account' => new AccountResource($this->whenLoaded('account')),
            'fuel_type_id' => $this->fuel_type_id,
            'fuel_type' => new FuelTypeResource($this->whenLoaded('fuelType')),
            'ordered_liters' => $this->ordered_liters,
            'price_per_liter' => $this->price_per_liter,
            'total_amount' => $this->total_amount,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status?->value,
            'received_liters' => $this->whenLoaded('deliveries', fn() => $this->deliveries->sum('actual_received_liters')),
            'deliveries_count' => $this->whenLoaded('deliveries', fn() => $this->deliveries->count()),
            'deliveries' => DeliveryResource::collection($this->whenLoaded('deliveries')),
            'payment_transactions' => PaymentTransactionResource::collection($this->whenLoaded('paymentTransactions')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}