<?php

namespace App\Http\Resources\DigitalArchive;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'file_path' => $this->file_path,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'category' => $this->category,
            'description' => $this->description,
            'uploaded_by' => $this->uploaded_by,
            'uploader' => $this->whenLoaded('uploader', fn (): array => [
                'id' => $this->uploader->id,
                'name' => $this->uploader->name,
                'email' => $this->uploader->email,
            ]),
            'entity_id' => $this->entity_id,
            'entity' => $this->whenLoaded('entity', fn (): array => [
                'id' => $this->entity->id,
                'name' => $this->entity->name,
            ]),
            'fileable_id' => $this->fileable_id,
            'fileable_type' => $this->fileable_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
