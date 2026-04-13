<?php

namespace App\Services\Entities;

use App\Models\EntityModel;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class EntityService
{
    public function create(array $data): EntityModel
    {
        $payload = $this->normalize($data);
        $this->assertBusinessRules($payload);

        try {
            return DB::transaction(static fn (): EntityModel => EntityModel::create($payload));
        } catch (QueryException $e) {
            if ($this->isUniqueConstraint($e)) {
                throw new DomainException('Numero ou NIF ja existe.');
            }

            throw $e;
        }
    }

    public function update(EntityModel $entity, array $data): EntityModel
    {
        $payload = $this->normalize($data);
        $this->assertBusinessRules(array_merge($entity->toArray(), $payload));

        try {
            return DB::transaction(function () use ($entity, $payload): EntityModel {
                $entity->update($payload);

                return $entity->refresh();
            });
        } catch (QueryException $e) {
            if ($this->isUniqueConstraint($e)) {
                throw new DomainException('Numero ou NIF ja existe.');
            }

            throw $e;
        }
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

    private function assertBusinessRules(array $data): void
    {
        $isClient = (bool) ($data['is_client'] ?? false);
        $isSupplier = (bool) ($data['is_supplier'] ?? false);

        if (! $isClient && ! $isSupplier) {
            throw new DomainException('A entidade deve ser cliente, fornecedor ou ambos.');
        }
    }

    private function isUniqueConstraint(QueryException $e): bool
    {
        return in_array((string) $e->getCode(), ['23000', '23505'], true)
            || str_contains(strtolower($e->getMessage()), 'unique');
    }
}
