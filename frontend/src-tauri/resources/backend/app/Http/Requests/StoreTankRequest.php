<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fuel_type_id' => 'required|exists:fuel_types,id',
            'name' => 'required|string|max:255',
            'capacity_liters' => 'required|numeric',
        ];
    }
}
