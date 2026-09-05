<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_type' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255',
            'opening_balance' => 'required|numeric',
        ];
    }
}
