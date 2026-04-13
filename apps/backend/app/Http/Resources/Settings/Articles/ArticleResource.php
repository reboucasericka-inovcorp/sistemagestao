<?php

namespace App\Http\Resources\Settings\Articles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (string) $this->price,
            'vat_id' => $this->vat_id,
            'vat' => [
                'id' => $this->vat?->id,
                'name' => $this->vat?->name,
                'rate' => $this->vat ? (string) $this->vat->rate : null,
            ],
            'photo_url' => $this->photo_path ? Storage::url($this->photo_path) : null,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
