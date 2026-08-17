<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNozzleReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nozzle_id' => 'required|exists:nozzles,id',
            'user_id' => 'required|exists:users,id',
            'opening_reading' => 'required|numeric',
            'closing_reading' => 'required|numeric',
            'liters_sold' => 'required|numeric',
            'price_per_liter' => 'required|numeric',
            'amount' => 'required|numeric',
            'recorded_at' => 'required|date',
        ];
    }
}
