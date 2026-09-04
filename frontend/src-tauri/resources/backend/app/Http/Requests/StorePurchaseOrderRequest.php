<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => 'required|exists:accounts,id',
            'fuel_type_id' => 'required|exists:fuel_types,id',
            'quantity' => 'required|numeric',
            'unit_price' => 'required|numeric',
            'ordered_at' => 'required|date',
            'status' => 'required|string',
        ];
    }
}
