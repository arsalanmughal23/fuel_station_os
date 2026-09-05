<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'account' => new AccountResource($this->whenLoaded('account')),
            'type' => $this->type?->value,
            'category' => $this->category?->value,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method?->value,
            'status' => $this->status?->value,
            'sale_id' => $this->sale_id,
            'sale' => new SaleResource($this->whenLoaded('sale')),
            'purchase_order_id' => $this->purchase_order_id,
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchaseOrder')),
            'reversed_transaction' => new PaymentTransactionResource($this->whenLoaded('reversedTransaction')),
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'remarks' => $this->remarks,
            'transacted_at' => $this->transacted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}