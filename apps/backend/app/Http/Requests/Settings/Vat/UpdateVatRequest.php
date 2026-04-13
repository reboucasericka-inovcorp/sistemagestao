<?php

namespace App\Http\Requests\Settings\Vat;

use App\Models\Settings\VatModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vat = $this->route('vat');
        $id = $vat instanceof VatModel ? $vat->getKey() : $vat;

        return [
            'name' => ['sometimes', 'string', 'max:120', Rule::unique('vats', 'name')->ignore($id)],
            'rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
