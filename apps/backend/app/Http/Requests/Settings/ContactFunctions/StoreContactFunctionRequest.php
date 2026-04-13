<?php

namespace App\Http\Requests\Settings\ContactFunctions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactFunctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('contact_functions', 'name')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
