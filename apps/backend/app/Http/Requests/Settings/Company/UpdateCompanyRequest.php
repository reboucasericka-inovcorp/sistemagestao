<?php

namespace App\Http\Requests\Settings\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'tax_number' => ['sometimes', 'string', 'max:20'],
            'logo' => ['nullable', 'image'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
        ];
    }
}
