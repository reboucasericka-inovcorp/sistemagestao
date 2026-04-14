<?php

namespace App\Http\Resources\Calendar;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
{
    private const DEFAULT_COLOR = '#3b82f6';

    public function toArray(Request $request): array
    {
        $date = $this->date instanceof Carbon ? $this->date->format('Y-m-d') : (string) $this->date;
        $time = substr((string) $this->time, 0, 5);
        $start = Carbon::createFromFormat('Y-m-d H:i', sprintf('%s %s', $date, $time), config('app.timezone'));
        $end = $start->copy()->addMinutes((int) $this->duration);

        return [
            'id' => $this->id,
            'date' => $date,
            'time' => $time,
            'duration' => (int) $this->duration,
            'user_id' => $this->user_id,
            'entity_id' => $this->entity_id,
            'type_id' => $this->type_id,
            'action_id' => $this->action_id,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'title' => $this->action?->name ?? $this->type?->name ?? 'Evento',
            'start' => $start->format('Y-m-d\TH:i:s'),
            'end' => $end->format('Y-m-d\TH:i:s'),
            'color' => $this->type?->color ?? self::DEFAULT_COLOR,
            'entity' => $this->whenLoaded('entity', fn (): ?array => $this->entity
                ? ['id' => $this->entity->id, 'name' => $this->entity->name]
                : null),
            'type' => $this->whenLoaded('type', fn (): ?array => $this->type
                ? ['id' => $this->type->id, 'name' => $this->type->name, 'color' => $this->type->color]
                : null),
            'action' => $this->whenLoaded('action', fn (): ?array => $this->action
                ? ['id' => $this->action->id, 'name' => $this->action->name]
                : null),
        ];
    }
}
