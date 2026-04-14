<?php

namespace App\Http\Resources\Access\Roles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => (bool) ($this->is_active ?? true),
            'permissions' => $this->whenLoaded(
                'permissions',
                fn (): array => $this->permissions->pluck('name')->values()->all(),
                [],
            ),
            'users_count' => (int) ($this->users_count ?? 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
