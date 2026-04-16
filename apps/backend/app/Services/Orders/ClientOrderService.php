<?php

namespace App\Services\Orders;

use App\Models\Orders\ClientOrderModel;
use App\Models\Orders\SupplierOrderItemModel;
use App\Models\Orders\SupplierOrderModel;
use App\Services\Settings\Company\CompanyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ClientOrderService
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

        return ClientOrderModel::query()
            ->with('client:id,name,number')
            ->when($status !== '', static fn (Builder $query): Builder => $query->where('status', $status))
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static function (Builder $nested) use ($term): void {
                    $nested->where('number', 'like', $term)
                        ->orWhereHas('client', static function (Builder $clientQuery) use ($term): void {
                            $clientQuery->where('name', 'like', $term);
                        });
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function getById(int $id): ClientOrderModel
    {
        return ClientOrderModel::query()
            ->with(['client:id,name,number', 'items.article:id,name,reference', 'items.supplier:id,name,number'])
            ->findOrFail($id);
    }

    public function create(array $data): ClientOrderModel
    {
        return DB::transaction(function () use ($data): ClientOrderModel {
            $orderDate = Carbon::parse((string) $data['order_date'])->startOfDay();
            $items = Arr::get($data, 'items', []);
            $this->assertAllItemsHaveSupplier($items);
            $totalAmount = $this->sumItemsTotal($items);

            $order = ClientOrderModel::query()->create([
                'number' => $this->generateNumber($orderDate->year),
                'order_date' => $orderDate->toDateString(),
                'client_id' => (int) $data['client_id'],
                'status' => 'draft',
                'total_amount' => $totalAmount,
            ]);

            $this->syncItems($order, $items);

            return $this->getById((int) $order->getKey());
        });
    }

    public function update(int $id, array $data): ClientOrderModel
    {
        return DB::transaction(function () use ($id, $data): ClientOrderModel {
            $order = ClientOrderModel::query()->findOrFail($id);
            $items = Arr::get($data, 'items');

            $payload = [];
            if (array_key_exists('order_date', $data)) {
                $payload['order_date'] = Carbon::parse((string) $data['order_date'])->toDateString();
            }
            if (array_key_exists('client_id', $data)) {
                $payload['client_id'] = (int) $data['client_id'];
            }
            if (array_key_exists('status', $data)) {
                $payload['status'] = (string) $data['status'];
            }
            if ($items !== null) {
                $this->assertAllItemsHaveSupplier($items);
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

    public function createFromProposal(array $payload): ClientOrderModel
    {
        return DB::transaction(function () use ($payload): ClientOrderModel {
            $orderDate = Carbon::now()->toDateString();
            $order = ClientOrderModel::query()->create([
                'number' => $this->generateNumber((int) Carbon::now()->format('Y')),
                'client_id' => (int) $payload['client_id'],
                'order_date' => $orderDate,
                'status' => 'draft',
                'total_amount' => number_format((float) $payload['total_amount'], 2, '.', ''),
            ]);

            foreach ($payload['items'] as $item) {
                $order->items()->create([
                    'article_id' => (int) $item['article_id'],
                    'supplier_id' => (int) $item['supplier_id'],
                    'quantity' => number_format((float) $item['quantity'], 2, '.', ''),
                    'cost_price' => number_format((float) $item['cost_price'], 2, '.', ''),
                    'total' => number_format((float) $item['total'], 2, '.', ''),
                ]);
            }

            return $this->getById((int) $order->getKey());
        });
    }

    public function convertToSupplierOrders(int $id): array
    {
        return DB::transaction(function () use ($id): array {
            $order = ClientOrderModel::query()->with('items')->findOrFail($id);

            if ($order->status === 'closed') {
                throw new DomainException('Encomenda já fechada.');
            }

            $missingSupplier = $order->items->contains(static fn ($item): bool => empty($item->supplier_id));
            if ($missingSupplier) {
                throw new DomainException('Todos os itens devem ter fornecedor.');
            }

            $groupedItems = $order->items->groupBy('supplier_id');

            if ($groupedItems->isEmpty()) {
                throw new DomainException('A encomenda não possui itens com fornecedor definido.');
            }

            $createdOrders = [];
            foreach ($groupedItems as $supplierId => $items) {
                $supplierOrder = SupplierOrderModel::query()->create([
                    'number' => $this->generateSupplierOrderNumber((int) Carbon::now()->format('Y')),
                    'supplier_id' => (int) $supplierId,
                    'order_date' => Carbon::now()->toDateString(),
                    'status' => 'draft',
                    'total_amount' => 0,
                ]);

                $totalAmount = 0.0;
                foreach ($items as $item) {
                    $lineTotal = (float) $item->quantity * (float) $item->cost_price;

                    SupplierOrderItemModel::query()->create([
                        'supplier_order_id' => (int) $supplierOrder->getKey(),
                        'article_id' => (int) $item->article_id,
                        'quantity' => number_format((float) $item->quantity, 2, '.', ''),
                        'cost_price' => number_format((float) $item->cost_price, 2, '.', ''),
                        'total' => number_format($lineTotal, 2, '.', ''),
                    ]);

                    $totalAmount += $lineTotal;
                }

                $supplierOrder->update([
                    'total_amount' => number_format($totalAmount, 2, '.', ''),
                ]);

                $createdOrders[] = $supplierOrder->fresh(['supplier:id,name,number', 'items.article:id,name,reference']);
            }

            $order->update([
                'status' => 'closed',
            ]);

            return $createdOrders;
        });
    }

    public function generatePdf(int $id)
    {
        $order = ClientOrderModel::query()
            ->with(['client', 'items.article', 'items.supplier'])
            ->findOrFail($id);

        $company = app(CompanyService::class)->getCurrent();

        return Pdf::loadView('pdf.client-order', [
            'order' => $order,
            'company' => $company,
        ]);
    }

    private function sumItemsTotal(array $items): string
    {
        $sum = array_reduce($items, static function (float $carry, array $item): float {
            return $carry + (float) ($item['total'] ?? 0);
        }, 0.0);

        return number_format($sum, 2, '.', '');
    }

    private function syncItems(ClientOrderModel $order, array $items): void
    {
        $order->items()->delete();

        foreach ($items as $item) {
            $order->items()->create([
                'article_id' => (int) $item['article_id'],
                'supplier_id' => (int) $item['supplier_id'],
                'quantity' => number_format((float) $item['quantity'], 2, '.', ''),
                'cost_price' => number_format((float) $item['cost_price'], 2, '.', ''),
                'total' => number_format((float) $item['total'], 2, '.', ''),
            ]);
        }
    }

    private function assertAllItemsHaveSupplier(array $items): void
    {
        foreach ($items as $item) {
            if (empty($item['supplier_id'])) {
                throw new DomainException('Todos os itens devem ter fornecedor.');
            }
        }
    }

    private function generateNumber(int $year): string
    {
        $prefix = sprintf('CO-%d-', $year);
        $last = ClientOrderModel::query()
            ->where('number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('number')
            ->first();

        $next = 1;
        if ($last !== null) {
            $pattern = sprintf('/^CO-%d-(\d{4})$/', $year);
            if (preg_match($pattern, (string) $last->number, $matches) === 1) {
                $next = (int) $matches[1] + 1;
            } else {
                throw new DomainException('Invalid client order number format.');
            }
        }

        return sprintf('CO-%d-%04d', $year, $next);
    }

    private function generateSupplierOrderNumber(int $year): string
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
}
