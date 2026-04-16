<?php

namespace App\Services\Orders;

use App\Models\EntityModel;
use App\Models\Orders\SupplierOrderModel;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SupplierOrderService
{
    public function getAll(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $search = trim((string) Arr::get($filters, 'search', ''));
        $status = trim((string) Arr::get($filters, 'status', ''));
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortable = ['id', 'number', 'order_date', 'status', 'total_amount', 'created_at'];

        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return SupplierOrderModel::query()
            ->with('supplier:id,name,number')
            ->when($status !== '', static fn (Builder $query): Builder => $query->where('status', $status))
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static function (Builder $nested) use ($term): void {
                    $nested->where('number', 'like', $term)
                        ->orWhereHas('supplier', static function (Builder $supplierQuery) use ($term): void {
                            $supplierQuery->where('name', 'like', $term);
                        });
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function getById(int $id): SupplierOrderModel
    {
        return SupplierOrderModel::query()
            ->with(['supplier:id,name,number', 'items.article:id,name,reference'])
            ->findOrFail($id);
    }

    public function create(array $data): SupplierOrderModel
    {
        return DB::transaction(function () use ($data): SupplierOrderModel {
            $orderDate = Carbon::parse((string) $data['order_date'])->startOfDay();
            $items = Arr::get($data, 'items', []);
            $totalAmount = $this->sumItemsTotal($items);
            $supplierId = (int) $data['supplier_id'];
            $this->assertSupplierEntity($supplierId);

            $order = SupplierOrderModel::query()->create([
                'number' => $this->generateNumber($orderDate->year),
                'order_date' => $orderDate->toDateString(),
                'supplier_id' => $supplierId,
                'status' => 'draft',
                'total_amount' => $totalAmount,
            ]);

            $this->syncItems($order, $items);

            return $this->getById((int) $order->getKey());
        });
    }

    public function update(int $id, array $data): SupplierOrderModel
    {
        return DB::transaction(function () use ($id, $data): SupplierOrderModel {
            $order = SupplierOrderModel::query()->findOrFail($id);
            $items = Arr::get($data, 'items');

            $payload = [];
            if (array_key_exists('order_date', $data)) {
                $payload['order_date'] = Carbon::parse((string) $data['order_date'])->toDateString();
            }
            if (array_key_exists('supplier_id', $data)) {
                $supplierId = (int) $data['supplier_id'];
                $this->assertSupplierEntity($supplierId);
                $payload['supplier_id'] = $supplierId;
            }
            if (array_key_exists('status', $data)) {
                $payload['status'] = (string) $data['status'];
            }
            if ($items !== null) {
                $payload['total_amount'] = $this->sumItemsTotal($items);
            }

            if ($payload !== []) {
                $order->update($payload);
            }

            if ($items !== null) {
                $this->syncItems($order, $items);
            }

            return $this->getById((int) $order->getKey());
        });
    }

    private function sumItemsTotal(array $items): string
    {
        $sum = array_reduce($items, static function (float $carry, array $item): float {
            return $carry + (float) ($item['total'] ?? 0);
        }, 0.0);

        return number_format($sum, 2, '.', '');
    }

    private function syncItems(SupplierOrderModel $order, array $items): void
    {
        $order->items()->delete();

        foreach ($items as $item) {
            $order->items()->create([
                'article_id' => (int) $item['article_id'],
                'quantity' => number_format((float) $item['quantity'], 2, '.', ''),
                'cost_price' => number_format((float) $item['cost_price'], 2, '.', ''),
                'total' => number_format((float) $item['total'], 2, '.', ''),
            ]);
        }
    }

    private function generateNumber(int $year): string
    {
        $prefix = sprintf('SO-%d-', $year);
        $last = SupplierOrderModel::query()
            ->where('number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('number')
            ->first();

        $next = 1;
        if ($last !== null) {
            $pattern = sprintf('/^SO-%d-(\d{4})$/', $year);
            if (preg_match($pattern, (string) $last->number, $matches) === 1) {
                $next = (int) $matches[1] + 1;
            } else {
                throw new DomainException('Invalid supplier order number format.');
            }
        }

        return sprintf('SO-%d-%04d', $year, $next);
    }

    private function assertSupplierEntity(int $supplierId): void
    {
        $isSupplier = EntityModel::query()
            ->whereKey($supplierId)
            ->where('is_supplier', true)
            ->exists();

        if (! $isSupplier) {
            throw new DomainException('Selected entity is not a supplier.');
        }
    }
}
