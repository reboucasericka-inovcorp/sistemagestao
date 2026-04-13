<?php

namespace App\Services\Settings\Countries;

use App\Models\Settings\CountryModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CountryService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) Arr::get($filters, 'search', ''));
        $activeOnly = filter_var(Arr::get($filters, 'active_only', false), FILTER_VALIDATE_BOOLEAN);

        $sortable = ['id', 'name', 'code', 'is_active', 'created_at', 'updated_at'];
        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return CountryModel::query()
            ->when($activeOnly, static fn (Builder $query): Builder => $query->active())
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term);
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function create(array $data): CountryModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(static fn (): CountryModel => CountryModel::create($payload));
    }

    public function update(CountryModel $country, array $data): CountryModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(function () use ($country, $payload): CountryModel {
            $country->update($payload);

            return $country->refresh();
        });
    }

    public function inactivate(CountryModel $country): CountryModel
    {
        if (! $country->is_active) {
            return $country;
        }

        return DB::transaction(function () use ($country): CountryModel {
            $country->update(['is_active' => false]);

            return $country->refresh();
        });
    }

    private function normalize(array $data): array
    {
        $payload = $data;

        if (array_key_exists('name', $payload)) {
            $payload['name'] = trim((string) $payload['name']);
        }

        if (array_key_exists('code', $payload)) {
            $code = trim((string) $payload['code']);
            $payload['code'] = $code === '' ? null : strtoupper($code);
        }

        return $payload;
    }
}
