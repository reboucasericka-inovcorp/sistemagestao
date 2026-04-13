<?php

namespace App\Http\Requests\Entities;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_client' => $this->has('is_client')
                ? filter_var($this->input('is_client'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null,
            'is_supplier' => $this->has('is_supplier')
                ? filter_var($this->input('is_supplier'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:50', 'unique:entities,number'],
            'nif' => ['required', 'string', 'max:20', 'unique:entities,nif'],
            'name' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'regex:/^\d{4}-\d{3}$/'],
            'email' => ['nullable', 'email'],
            'is_client' => ['required', 'boolean'],
            'is_supplier' => ['required', 'boolean'],
            'gdpr_consent' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
