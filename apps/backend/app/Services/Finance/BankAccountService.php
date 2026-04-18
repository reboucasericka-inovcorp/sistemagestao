<?php

namespace App\Services\Finance;

use App\Models\Finance\BankAccountModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class BankAccountService
{
    public function getAll(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $search = trim((string) Arr::get($filters, 'search', ''));
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $isActive = Arr::get($filters, 'is_active');

        $sortable = ['id', 'bank_name', 'iban', 'account_holder', 'is_active', 'created_at'];
        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return BankAccountModel::query()
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($nested) use ($term): void {
                    $nested->where('bank_name', 'like', $term)
                        ->orWhere('iban', 'like', $term)
                        ->orWhere('account_holder', 'like', $term);
                });
            })
            ->when($isActive !== null && $isActive !== '', static function ($query) use ($isActive): void {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false);
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function getById(int $id): BankAccountModel
    {
        return BankAccountModel::query()->findOrFail($id);
    }

    public function create(array $data): BankAccountModel
    {
        $account = BankAccountModel::query()->create([
            'bank_name' => (string) $data['bank_name'],
            'iban' => strtoupper(trim((string) $data['iban'])),
            'account_holder' => (string) $data['account_holder'],
            'is_active' => (bool) Arr::get($data, 'is_active', true),
        ]);

        return $this->getById((int) $account->id);
    }

    public function update(int $id, array $data): BankAccountModel
    {
        $account = $this->getById($id);
        $payload = [];

        if (array_key_exists('bank_name', $data)) {
            $payload['bank_name'] = (string) $data['bank_name'];
        }
        if (array_key_exists('iban', $data)) {
            $payload['iban'] = strtoupper(trim((string) $data['iban']));
        }
        if (array_key_exists('account_holder', $data)) {
            $payload['account_holder'] = (string) $data['account_holder'];
        }
        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        if ($payload !== []) {
            $account->update($payload);
        }

        return $this->getById($id);
    }

    public function delete(int $id): void
    {
        $account = $this->getById($id);
        $account->delete();
    }
}
