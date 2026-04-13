<?php

namespace App\Services\Settings\CalendarTypes;

use App\Models\Settings\CalendarTypeModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CalendarTypeService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) Arr::get($filters, 'search', ''));
        $activeOnly = filter_var(Arr::get($filters, 'active_only', false), FILTER_VALIDATE_BOOLEAN);

        $sortable = ['id', 'name', 'color', 'is_active', 'created_at', 'updated_at'];
        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return CalendarTypeModel::query()
            ->when($activeOnly, static fn (Builder $query): Builder => $query->active())
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('color', 'like', $term);
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function create(array $data): CalendarTypeModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(static fn (): CalendarTypeModel => CalendarTypeModel::create($payload));
    }

    public function update(CalendarTypeModel $calendarType, array $data): CalendarTypeModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(function () use ($calendarType, $payload): CalendarTypeModel {
            $calendarType->update($payload);

            return $calendarType->refresh();
        });
    }

    public function inactivate(CalendarTypeModel $calendarType): CalendarTypeModel
    {
        if (! $calendarType->is_active) {
            return $calendarType;
        }

        return DB::transaction(function () use ($calendarType): CalendarTypeModel {
            $calendarType->update(['is_active' => false]);

            return $calendarType->refresh();
        });
    }

    private function normalize(array $data): array
    {
        $payload = $data;

        if (array_key_exists('name', $payload)) {
            $payload['name'] = trim((string) $payload['name']);
        }

        if (array_key_exists('color', $payload)) {
            $color = trim((string) $payload['color']);
            if ($color === '') {
                $payload['color'] = null;
            } elseif (str_starts_with($color, '#')) {
                $payload['color'] = strtoupper($color);
            } else {
                $payload['color'] = $color;
            }
        }

        return $payload;
    }
}
