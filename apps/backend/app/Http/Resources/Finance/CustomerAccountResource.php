<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_id' => $this->entity_id,
            'balance' => $this->balance,
            'entity' => $this->entity
                ? [
                    'id' => $this->entity->id,
                    'name' => $this->entity->name,
                    'number' => $this->entity->number,
                    'email' => $this->entity->email ?? null,
                ]
                : null,
            'movements' => $this->relationLoaded('movements')
                ? CustomerAccountMovementResource::collection($this->movements)->resolve($request)
                : [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
