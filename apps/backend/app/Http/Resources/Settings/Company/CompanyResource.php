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
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'tax_number' => $this->tax_number,
            'logo_url' => $this->logo_path ? Storage::url($this->logo_path) : null,
        ];
    }
}
