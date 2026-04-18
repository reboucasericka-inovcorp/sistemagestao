<?php

namespace App\Services\Finance;

use App\Models\EntityModel;
use App\Models\Finance\CustomerAccountModel;
use App\Models\Finance\CustomerAccountMovementModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerAccountService
{
    public function getAll(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $search = trim((string) Arr::get($filters, 'search', ''));
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortable = ['id', 'balance', 'created_at'];

        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return CustomerAccountModel::query()
            ->with(['entity:id,name,number,email,is_client'])
            ->whereHas('entity', static fn (Builder $query): Builder => $query->where('is_client', true))
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->whereHas('entity', static function (Builder $entityQuery) use ($term): void {
                    $entityQuery->where('name', 'like', $term)
                        ->orWhere('number', 'like', $term);
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function getById(int $id): CustomerAccountModel
    {
        return CustomerAccountModel::query()
            ->with([
                'entity:id,name,number,email,is_client',
                'movements' => static fn ($query) => $query->orderByDesc('date')->orderByDesc('id'),
            ])
            ->findOrFail($id);
    }

    public function listMovements(int $accountId, array $filters): LengthAwarePaginator
    {
        $account = CustomerAccountModel::query()
            ->where('entity_id', $accountId)
            ->first();
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);

        if (! $account) {
            return CustomerAccountMovementModel::query()
                ->whereRaw('1 = 0')
                ->paginate($perPage);
        }

        return CustomerAccountMovementModel::query()
            ->where('customer_account_id', $account->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function addMovement(int $accountId, array $data): CustomerAccountModel
    {
        return DB::transaction(function () use ($accountId, $data): CustomerAccountModel {
            $entityId = $accountId;
            $this->assertClientEntity($entityId);

            $account = CustomerAccountModel::query()->firstOrCreate(
                ['entity_id' => $entityId],
                ['balance' => 0]
            );

            /** @var CustomerAccountModel $account */
            $account = CustomerAccountModel::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            $type = (string) $data['type'];
            $amount = round((float) $data['amount'], 2);
            $currentBalance = (float) $account->balance;
            $nextBalance = $type === 'credit'
                ? $currentBalance + $amount
                : $currentBalance - $amount;

            CustomerAccountMovementModel::query()->create([
                'customer_account_id' => (int) $account->id,
                'type' => $type,
                'amount' => number_format($amount, 2, '.', ''),
                'description' => Arr::get($data, 'description'),
                'date' => (string) $data['date'],
            ]);

            $account->update([
                'balance' => number_format($nextBalance, 2, '.', ''),
            ]);

            return $this->getById((int) $account->id);
        });
    }

    private function assertClientEntity(int $entityId): void
    {
        $isClient = EntityModel::query()
            ->whereKey($entityId)
            ->where('is_client', true)
            ->exists();

        if ($isClient) {
            return;
        }

        throw ValidationException::withMessages([
            'entity_id' => 'A entidade selecionada não é um cliente.',
        ]);
    }
}
