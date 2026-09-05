<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fuel_type_id' => 'sometimes|exists:fuel_types,id',
            'name' => 'sometimes|string|max:255',
            'capacity_liters' => 'sometimes|numeric',
        ];
    }
}
