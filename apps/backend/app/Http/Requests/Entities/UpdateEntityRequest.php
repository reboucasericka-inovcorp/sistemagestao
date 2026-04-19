<?php

namespace App\Http\Requests\Entities;

use App\Models\EntityModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $entity = $this->route('entity');
        $entityId = $entity instanceof EntityModel ? $entity->getKey() : $entity;

        return [
            'is_client' => ['sometimes', 'boolean'],
            'is_supplier' => ['sometimes', 'boolean'],
            'nif' => ['sometimes', 'string', 'max:20', Rule::unique('entities', 'nif')->ignore($entityId)],
            'name' => ['sometimes', 'string', 'max:255'],
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

    public function messages(): array
    {
        return [
            'nif.unique' => 'Este NIF já está registado.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $entity = $this->route('entity');
            $currentIsClient = $entity instanceof EntityModel ? (bool) $entity->is_client : false;
            $currentIsSupplier = $entity instanceof EntityModel ? (bool) $entity->is_supplier : false;

            $isClient = $this->has('is_client')
                ? filter_var($this->input('is_client'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : $currentIsClient;
            $isSupplier = $this->has('is_supplier')
                ? filter_var($this->input('is_supplier'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : $currentIsSupplier;

            if (! $isClient && ! $isSupplier) {
                $validator->errors()->add('is_client', 'Selecione pelo menos Cliente ou Fornecedor.');
            }
        });
    }
}
