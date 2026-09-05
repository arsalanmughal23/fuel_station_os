<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'tank_id' => 'required|exists:tanks,id',
            'delivered_at' => 'required|date',
            'quantity' => 'required|numeric',
        ];
    }
}
