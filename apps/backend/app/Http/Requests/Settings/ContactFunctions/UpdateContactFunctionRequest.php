<?php

namespace App\Http\Requests\Settings\ContactFunctions;

use App\Models\Settings\ContactFunctionModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactFunctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $contactFunction = $this->route('contact_function');
        $id = $contactFunction instanceof ContactFunctionModel ? $contactFunction->getKey() : $contactFunction;

        return [
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('contact_functions', 'name')->ignore($id)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
