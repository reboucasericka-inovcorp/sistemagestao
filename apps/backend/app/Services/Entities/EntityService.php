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
        $typeFilter = strtolower((string) Arr::get($filters, 'type', ''));
        $isClient = filter_var(Arr::get($filters, 'is_client', false), FILTER_VALIDATE_BOOLEAN);
        $isSupplier = filter_var(Arr::get($filters, 'is_supplier', false), FILTER_VALIDATE_BOOLEAN);
        $activeOnly = filter_var(Arr::get($filters, 'active_only', false), FILTER_VALIDATE_BOOLEAN);

        if ($typeFilter === 'client') {
            $isClient = true;
        } elseif ($typeFilter === 'supplier') {
            $isSupplier = true;
        }

        $sortable = ['id', 'number', 'name', 'nif', 'email', 'is_active', 'created_at', 'updated_at'];
        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return EntityModel::query()
            ->with('country:id,name')
            ->when($isClient, static fn (Builder $query): Builder => $query->clients())
            ->when($isSupplier, static fn (Builder $query): Builder => $query->suppliers())
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
            $this->ensureEntityKindIsSelected($payload);

            if (EntityModel::query()->where('nif', $payload['nif'])->exists()) {
                throw new DomainException('NIF already exists.');
            }

            $payload['number'] = $this->generateNumber();

            return EntityModel::query()->create($payload);
        });
    }

    public function update(EntityModel $entity, array $data): EntityModel
    {
        $payload = $this->normalize($data);
        $payload['is_client'] = array_key_exists('is_client', $payload)
            ? (bool) $payload['is_client']
            : (bool) $entity->is_client;
        $payload['is_supplier'] = array_key_exists('is_supplier', $payload)
            ? (bool) $payload['is_supplier']
            : (bool) $entity->is_supplier;
        $this->ensureEntityKindIsSelected($payload);

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

    public function generateNumber(): string
    {
        $last = EntityModel::query()
            ->where('number', 'like', 'ENT-%')
            ->lockForUpdate()
            ->orderByDesc('number')
            ->first();
        $nextNumber = 1;

        if ($last !== null && preg_match('/^ENT-(\d{6})$/', (string) $last->number, $matches) === 1) {
            $nextNumber = (int) $matches[1] + 1;
        } elseif ($last !== null) {
            $nextNumber = ((int) preg_replace('/\D+/', '', (string) $last->number)) + 1;
        }

        return 'ENT-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
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

        if (array_key_exists('is_client', $payload)) {
            $payload['is_client'] = filter_var($payload['is_client'], FILTER_VALIDATE_BOOLEAN);
        }

        if (array_key_exists('is_supplier', $payload)) {
            $payload['is_supplier'] = filter_var($payload['is_supplier'], FILTER_VALIDATE_BOOLEAN);
        }

        return $payload;
    }

    private function ensureEntityKindIsSelected(array $payload): void
    {
        $isClient = (bool) ($payload['is_client'] ?? false);
        $isSupplier = (bool) ($payload['is_supplier'] ?? false);

        if (! $isClient && ! $isSupplier) {
            throw new DomainException('At least one of is_client or is_supplier must be true.');
        }
    }
}
