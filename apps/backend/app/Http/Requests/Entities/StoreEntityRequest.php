<?php

namespace App\Http\Requests\Entities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_client' => ['required', 'boolean'],
            'is_supplier' => ['required', 'boolean'],
            'nif' => ['required', 'string', 'max:20', Rule::unique('entities', 'nif')],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'regex:/^\d{4}-\d{3}$/'],
            'city' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'gdpr_consent' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $isClient = filter_var($this->input('is_client'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $isSupplier = filter_var($this->input('is_supplier'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (! $isClient && ! $isSupplier) {
                $validator->errors()->add('is_client', 'Selecione pelo menos Cliente ou Fornecedor.');
            }
        });
    }
}
