<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['sometimes', 'string', 'max:255'],
            'iban' => ['sometimes', 'string', 'max:34', Rule::unique('bank_accounts', 'iban')->ignore($this->route('id'))],
            'account_holder' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('iban')) {
            $this->merge([
                'iban' => strtoupper((string) preg_replace('/\s+/', '', (string) $this->input('iban'))),
            ]);
        }
    }
}
