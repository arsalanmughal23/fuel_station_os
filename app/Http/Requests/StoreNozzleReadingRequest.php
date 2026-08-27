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
            'closing_reading' => 'required|numeric|min:0',
            'recorded_at' => 'sometimes|date',
        ];
    }
}
