<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNozzleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tank_id' => 'required|exists:tanks,id',
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
        ];
    }
}
