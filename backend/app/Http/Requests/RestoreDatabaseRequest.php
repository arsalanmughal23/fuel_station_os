<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestoreDatabaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('restore_database');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'backup_file' => 'required|file|mimes:sqlite,db,sqlite3|max:204800', // Max 200MB
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'backup_file.required' => 'A backup file is required.',
            'backup_file.file' => 'The backup must be a file.',
            'backup_file.mimes' => 'The backup file must be a SQLite database file (.sqlite, .db, .sqlite3).',
            'backup_file.max' => 'The backup file must not exceed 200MB.',
        ];
    }
}