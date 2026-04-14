<?php

namespace App\Http\Requests\Access\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('roles', 'name')],
            'is_active' => ['sometimes', 'boolean'],
            'permissions' => ['required', 'array'],
        ];
    }
}
