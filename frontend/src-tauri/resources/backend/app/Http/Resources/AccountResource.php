<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'account_type' => $this->account_type?->value,
            'name' => $this->name,
            'contact' => $this->contact,
            'opening_balance' => $this->opening_balance,
            'current_balance' => $this->current_balance,
            'purchase_orders_count' => $this->whenLoaded('purchaseOrders', fn() => $this->purchaseOrders->count()),
            'sales_count' => $this->whenLoaded('sales', fn() => $this->sales->count()),
            'payment_transactions_count' => $this->whenLoaded('paymentTransactions', fn() => $this->paymentTransactions->count()),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}