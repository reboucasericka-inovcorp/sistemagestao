<?php

namespace App\Http\Requests\Settings\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'tax_number' => ['required', 'string', 'max:20'],
            'logo' => ['nullable', 'image'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
        ];
    }
}
