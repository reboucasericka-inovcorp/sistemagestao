<?php

namespace App\Http\Resources\Settings\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tax_number' => $this->tax_number,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country_id' => $this->country_id,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'website' => $this->website,
            'is_active' => $this->is_active,
            'logo' => $this->logo_path,
            'logo_url' => $this->logo_path ? url(Storage::url($this->logo_path)) : null,
        ];
    }
}
