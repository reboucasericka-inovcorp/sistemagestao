<?php

namespace App\Http\Requests\Entity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $entityId = $this->route('entity');

        return [
            'number' => ['sometimes', 'string', 'max:50', Rule::unique('entities', 'number')->ignore($entityId)],
            'nif' => ['sometimes', 'string', 'max:20', Rule::unique('entities', 'nif')->ignore($entityId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'regex:/^\d{4}-\d{3}$/'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_client' => ['sometimes', 'boolean'],
            'is_supplier' => ['sometimes', 'boolean'],
            'gdpr_consent' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $isClient = $this->has('is_client') ? (bool) $this->boolean('is_client') : null;
            $isSupplier = $this->has('is_supplier') ? (bool) $this->boolean('is_supplier') : null;

            if ($isClient === false && $isSupplier === false) {
                $validator->errors()->add('is_client', 'Pelo menos um tipo deve ser selecionado: cliente ou fornecedor.');
            }
        });
    }
}
