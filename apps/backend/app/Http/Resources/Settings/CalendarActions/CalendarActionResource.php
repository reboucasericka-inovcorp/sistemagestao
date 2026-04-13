<?php

namespace App\Http\Resources\Settings\CalendarActions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'calendar_type_id' => $this->calendar_type_id,
            'calendar_type' => $this->whenLoaded('calendarType', fn (): ?array => $this->calendarType
                ? [
                    'id' => $this->calendarType->id,
                    'name' => $this->calendarType->name,
                ]
                : null),
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
