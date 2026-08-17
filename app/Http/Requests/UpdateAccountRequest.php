<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_type' => 'sometimes|string|max:255',
            'user_id' => 'sometimes|nullable|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'contact' => 'sometimes|nullable|string|max:255',
        ];
    }
}
