<?php

namespace App\Services\Settings\Vat;

use App\Models\Settings\VatModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class VatService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) Arr::get($filters, 'search', ''));
        $activeOnly = filter_var(Arr::get($filters, 'active_only', false), FILTER_VALIDATE_BOOLEAN);

        $sortable = ['id', 'name', 'rate', 'is_active', 'created_at', 'updated_at'];
        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return VatModel::query()
            ->when($activeOnly, static fn (Builder $query): Builder => $query->active())
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where('name', 'like', $term);
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function create(array $data): VatModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(static fn (): VatModel => VatModel::create($payload));
    }

    public function update(VatModel $vat, array $data): VatModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(function () use ($vat, $payload): VatModel {
            $vat->update($payload);

            return $vat->refresh();
        });
    }

    public function inactivate(VatModel $vat): VatModel
    {
        if (! $vat->is_active) {
            return $vat;
        }

        return DB::transaction(function () use ($vat): VatModel {
            $vat->update(['is_active' => false]);

            return $vat->refresh();
        });
    }

    private function normalize(array $data): array
    {
        $payload = $data;

        if (array_key_exists('name', $payload)) {
            $payload['name'] = trim((string) $payload['name']);
        }

        if (array_key_exists('rate', $payload)) {
            $payload['rate'] = number_format((float) $payload['rate'], 2, '.', '');
        }

        return $payload;
    }
}
