<?php

namespace App\Services\Settings\CalendarActions;

use App\Models\Settings\CalendarActionModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CalendarActionService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) Arr::get($filters, 'search', ''));
        $activeOnly = filter_var(Arr::get($filters, 'active_only', false), FILTER_VALIDATE_BOOLEAN);

        $sortable = ['id', 'name', 'calendar_type_id', 'is_active', 'created_at', 'updated_at'];
        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return CalendarActionModel::query()
            ->with('calendarType:id,name')
            ->when($activeOnly, static fn (Builder $query): Builder => $query->active())
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where('name', 'like', $term);
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function create(array $data): CalendarActionModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(static fn (): CalendarActionModel => CalendarActionModel::create($payload));
    }

    public function update(CalendarActionModel $calendarAction, array $data): CalendarActionModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(function () use ($calendarAction, $payload): CalendarActionModel {
            $calendarAction->update($payload);

            return $calendarAction->refresh();
        });
    }

    public function inactivate(CalendarActionModel $calendarAction): CalendarActionModel
    {
        if (! $calendarAction->is_active) {
            return $calendarAction;
        }

        return DB::transaction(function () use ($calendarAction): CalendarActionModel {
            $calendarAction->update(['is_active' => false]);

            return $calendarAction->refresh();
        });
    }

    private function normalize(array $data): array
    {
        $payload = $data;

        if (array_key_exists('name', $payload)) {
            $payload['name'] = trim((string) $payload['name']);
        }

        if (array_key_exists('calendar_type_id', $payload) && $payload['calendar_type_id'] === '') {
            $payload['calendar_type_id'] = null;
        }

        return $payload;
    }
}
