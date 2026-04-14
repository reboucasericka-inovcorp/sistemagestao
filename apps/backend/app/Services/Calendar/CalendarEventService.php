<?php

namespace App\Services\Calendar;

use App\Models\Calendar\CalendarEventModel;
use App\Models\Settings\CalendarActionModel;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CalendarEventService
{
    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, CalendarEventModel>
     */
    public function list(array $filters): Collection
    {
        $userId = Arr::get($filters, 'user_id');
        $entityId = Arr::get($filters, 'entity_id');
        $start = Arr::get($filters, 'start');
        $end = Arr::get($filters, 'end');

        return CalendarEventModel::query()
            ->with([
                'entity:id,name',
                'type:id,name,color',
                'action:id,name',
                'user:id,name',
            ])
            ->when($userId, static fn (Builder $query): Builder => $query->where('user_id', $userId))
            ->when($entityId, static fn (Builder $query): Builder => $query->where('entity_id', $entityId))
            ->when($start, static fn (Builder $query): Builder => $query->whereDate('date', '>=', $start))
            ->when($end, static fn (Builder $query): Builder => $query->whereDate('date', '<=', $end))
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $userId): CalendarEventModel
    {
        return DB::transaction(function () use ($data, $userId): CalendarEventModel {
            $payload = $this->normalizePayload($data);
            $payload['user_id'] = $userId;
            $this->enforceActionTypeIntegrity($payload);

            $event = CalendarEventModel::create($payload);

            return $event->load(['entity:id,name', 'type:id,name,color', 'action:id,name', 'user:id,name']);
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(CalendarEventModel $event, array $data): CalendarEventModel
    {
        return DB::transaction(function () use ($event, $data): CalendarEventModel {
            $payload = $this->normalizePayload($data);
            $this->enforceActionTypeIntegrity($payload, $event);

            $event->update($payload);

            return $event->refresh()->load(['entity:id,name', 'type:id,name,color', 'action:id,name', 'user:id,name']);
        });
    }

    public function delete(CalendarEventModel $event): void
    {
        DB::transaction(static function () use ($event): void {
            $event->update(['is_active' => false]);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function enforceActionTypeIntegrity(array &$payload, ?CalendarEventModel $event = null): void
    {
        $actionId = $payload['action_id'] ?? $event?->action_id;
        $typeTouched = array_key_exists('type_id', $payload);

        if ($actionId === null) {
            return;
        }

        /** @var CalendarActionModel|null $action */
        $action = CalendarActionModel::query()
            ->select(['id', 'calendar_type_id'])
            ->find($actionId);

        if (! $action) {
            throw new DomainException('A ação informada é inválida.');
        }

        $incomingTypeId = $payload['type_id'] ?? $event?->type_id;
        if ($incomingTypeId !== null && (int) $incomingTypeId !== (int) $action->calendar_type_id) {
            throw new DomainException('A ação selecionada não pertence ao tipo informado.');
        }

        // Quando action_id é informado, type_id segue o tipo da ação automaticamente.
        if (array_key_exists('action_id', $payload) || $typeTouched) {
            $payload['type_id'] = $action->calendar_type_id;
        }

        if ($action->calendar_type_id === null) {
            return;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data): array
    {
        $payload = $data;

        if (array_key_exists('description', $payload) && $payload['description'] !== null) {
            $payload['description'] = trim((string) $payload['description']);
            if ($payload['description'] === '') {
                $payload['description'] = null;
            }
        }

        return $payload;
    }
}
