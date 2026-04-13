<?php

namespace App\Http\Requests\Settings\Countries;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('countries', 'name')],
            'code' => ['nullable', 'string', 'size:2', Rule::unique('countries', 'code')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
