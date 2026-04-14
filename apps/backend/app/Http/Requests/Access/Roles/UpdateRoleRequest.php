<?php

namespace App\Http\Requests\Access\Roles;

use App\Models\Access\RoleModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->route('role');
        $id = $role instanceof RoleModel ? $role->getKey() : $role;

        return [
            'name' => ['sometimes', 'string', 'max:120', Rule::unique('roles', 'name')->ignore($id)],
            'is_active' => ['sometimes', 'boolean'],
            'permissions' => ['sometimes', 'array'],
        ];
    }
}
