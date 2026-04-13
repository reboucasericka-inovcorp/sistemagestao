<?php

namespace App\Services\Settings\ContactFunctions;

use App\Models\Settings\ContactFunctionModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ContactFunctionService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) Arr::get($filters, 'search', ''));
        $activeOnly = filter_var(Arr::get($filters, 'active_only', false), FILTER_VALIDATE_BOOLEAN);

        $sortable = ['id', 'name', 'is_active', 'created_at', 'updated_at'];
        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return ContactFunctionModel::query()
            ->when($activeOnly, static fn (Builder $query): Builder => $query->active())
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where('name', 'like', $term);
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function create(array $data): ContactFunctionModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(static fn (): ContactFunctionModel => ContactFunctionModel::create($payload));
    }

    public function update(ContactFunctionModel $contactFunction, array $data): ContactFunctionModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(function () use ($contactFunction, $payload): ContactFunctionModel {
            $contactFunction->update($payload);

            return $contactFunction->refresh();
        });
    }

    public function inactivate(ContactFunctionModel $contactFunction): ContactFunctionModel
    {
        if (! $contactFunction->is_active) {
            return $contactFunction;
        }

        return DB::transaction(function () use ($contactFunction): ContactFunctionModel {
            $contactFunction->update(['is_active' => false]);

            return $contactFunction->refresh();
        });
    }

    private function normalize(array $data): array
    {
        $payload = $data;

        if (array_key_exists('name', $payload)) {
            $payload['name'] = trim((string) $payload['name']);
        }

        return $payload;
    }
}
