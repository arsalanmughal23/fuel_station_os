<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'account_id' => $this->account_id,
            'account' => new AccountResource($this->whenLoaded('account')),
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'change_amount' => $this->change_amount,
            'payment_status' => $this->payment_status?->value,
            'sale_date' => $this->sale_date?->toISOString(),
            'sale_items' => SaleItemResource::collection($this->whenLoaded('saleItems')),
            'payment_transaction' => new PaymentTransactionResource($this->whenLoaded('paymentTransaction')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}