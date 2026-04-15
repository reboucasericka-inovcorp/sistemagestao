<?php

namespace App\Http\Resources\Contacts;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'rgpd_consent' => (bool) $this->rgpd_consent,
            'is_active' => (bool) $this->is_active,
            'entity' => $this->entity
                ? [
                    'id' => $this->entity->id,
                    'name' => $this->entity->name,
                ]
                : null,
            'function' => $this->contactFunction
                ? [
                    'id' => $this->contactFunction->id,
                    'name' => $this->contactFunction->name,
                ]
                : null,
        ];
    }
}
