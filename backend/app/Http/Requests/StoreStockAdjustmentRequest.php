<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stockable_type' => 'required|string',
            'stockable_id' => 'required|integer',
            'unit' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
            'deep_reading_id' => 'nullable|exists:deep_readings,id',
            'quantity' => 'required|numeric',
            'adjustment_type' => 'required|string',
            'reason' => 'nullable|string',
            'adjusted_at' => 'required|date',
        ];
    }
}
