<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeepReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tank_id' => 'required|exists:tanks,id',
            'user_id' => 'required|exists:users,id',
            'inside_reading' => 'required|numeric',
            'outside_reading' => 'required|numeric',
            'recorded_at' => 'required|date',
        ];
    }
}
