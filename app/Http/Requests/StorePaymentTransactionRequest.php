<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|string',
            'category' => 'nullable|string',
            'amount' => 'required|numeric',
            'payment_method' => 'nullable|string',
            'sale_id' => 'nullable|exists:sales,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'reversed_transaction_id' => 'nullable|exists:payment_transactions,id',
            'status' => 'required|string',
            'remarks' => 'nullable|string',
            'transacted_at' => 'required|date',
        ];
    }
}
