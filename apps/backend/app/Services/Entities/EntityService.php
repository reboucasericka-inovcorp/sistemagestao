<?php

namespace App\Services\Entities;

use App\Models\EntityModel;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EntityService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) Arr::get($filters, 'search', ''));
        $type = (string) Arr::get($filters, 'type', '');
        $activeOnly = filter_var(Arr::get($filters, 'active_only', false), FILTER_VALIDATE_BOOLEAN);

        $sortable = ['id', 'number', 'type', 'name', 'nif', 'email', 'is_active', 'created_at', 'updated_at'];
        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return EntityModel::query()
            ->with('country:id,name')
            ->when($type === 'client', static fn (Builder $query): Builder => $query->clients())
            ->when($type === 'supplier', static fn (Builder $query): Builder => $query->suppliers())
            ->when($activeOnly, static fn (Builder $query): Builder => $query->active())
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function (Builder $nested) use ($term): void {
                    $nested
                        ->where('name', 'like', $term)
                        ->orWhere('nif', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function create(array $data): EntityModel
    {
        return DB::transaction(function () use ($data): EntityModel {
            $payload = $this->normalize($data);
            $payload['number'] = $this->generateNumber();

            if (EntityModel::query()->where('nif', $payload['nif'])->exists()) {
                throw new DomainException('NIF already exists.');
            }

            $this->fetchFromVies((string) $payload['nif']);

            return EntityModel::query()->create($payload);
        });
    }

    public function update(EntityModel $entity, array $data): EntityModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(function () use ($entity, $payload): EntityModel {
            if (array_key_exists('nif', $payload)) {
                $exists = EntityModel::query()
                    ->where('nif', $payload['nif'])
                    ->whereKeyNot($entity->getKey())
                    ->exists();

                if ($exists) {
                    throw new DomainException('NIF already exists.');
                }
            }

            $entity->update($payload);

            return $entity->refresh();
        });
    }

    public function inactivate(EntityModel $entity): EntityModel
    {
        if (! $entity->is_active) {
            return $entity;
        }

        return DB::transaction(function () use ($entity): EntityModel {
            $entity->update(['is_active' => false]);

            return $entity->refresh();
        });
    }

    public function generateNumber(): int
    {
        $lastNumber = (int) EntityModel::query()->lockForUpdate()->max('number');

        return $lastNumber + 1;
    }

    public function fetchFromVies(string $nif): ?array
    {
        return null;
    }

    private function normalize(array $data): array
    {
        $payload = $data;

        if (array_key_exists('nif', $payload)) {
            $payload['nif'] = preg_replace('/\D+/', '', (string) $payload['nif']);
        }

        if (array_key_exists('email', $payload)) {
            $payload['email'] = $payload['email']
                ? strtolower((string) $payload['email'])
                : null;
        }

        return $payload;
    }
}
