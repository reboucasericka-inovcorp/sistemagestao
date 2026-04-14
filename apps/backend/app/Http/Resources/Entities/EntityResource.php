<?php

namespace App\Http\Resources\Entities;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'number' => $this->number,
            'nif' => $this->nif,
            'name' => $this->name,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country' => $this->country
                ? [
                    'id' => $this->country->id,
                    'name' => $this->country->name,
                ]
                : null,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'website' => $this->website,
            'email' => $this->email,
            'gdpr_consent' => (bool) $this->gdpr_consent,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
