<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNozzleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tank_id' => 'sometimes|exists:tanks,id',
            'name' => 'sometimes|string|max:255',
            'position' => 'sometimes|nullable|string|max:255',
        ];
    }
}
