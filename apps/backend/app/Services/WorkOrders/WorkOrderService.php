<?php

namespace App\Services\WorkOrders;

use App\Models\Orders\ClientOrderModel;
use App\Models\WorkOrders\WorkOrderModel;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class WorkOrderService
{
    public function getAll(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $search = trim((string) Arr::get($filters, 'search', ''));
        $status = trim((string) Arr::get($filters, 'status', ''));
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortable = ['id', 'number', 'date', 'status', 'total_amount', 'created_at'];

        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return WorkOrderModel::query()
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

    public function getById(int $id): WorkOrderModel
    {
        return WorkOrderModel::query()
            ->with(['client:id,name,number', 'items.article:id,name,reference'])
            ->findOrFail($id);
    }

    public function create(array $data): WorkOrderModel
    {
        return DB::transaction(function () use ($data): WorkOrderModel {
            $date = Carbon::parse((string) $data['date'])->startOfDay();
            $items = Arr::get($data, 'items', []);
            $totalAmount = $this->sumItemsTotal($items);

            $workOrder = WorkOrderModel::query()->create([
                'number' => $this->generateNumber($date->year),
                'date' => $date->toDateString(),
                'client_id' => (int) $data['client_id'],
                'status' => (string) Arr::get($data, 'status', 'draft'),
                'description' => Arr::get($data, 'description'),
                'total_amount' => $totalAmount,
            ]);

            $this->syncItems($workOrder, $items);

            return $this->getById((int) $workOrder->getKey());
        });
    }

    public function update(int $id, array $data): WorkOrderModel
    {
        return DB::transaction(function () use ($id, $data): WorkOrderModel {
            $workOrder = WorkOrderModel::query()->findOrFail($id);
            $items = Arr::get($data, 'items');

            $payload = [];
            if (array_key_exists('date', $data)) {
                $payload['date'] = Carbon::parse((string) $data['date'])->toDateString();
            }
            if (array_key_exists('client_id', $data)) {
                $payload['client_id'] = (int) $data['client_id'];
            }
            if (array_key_exists('status', $data)) {
                $payload['status'] = (string) $data['status'];
            }
            if (array_key_exists('description', $data)) {
                $payload['description'] = $data['description'];
            }
            if ($items !== null) {
                $payload['total_amount'] = $this->sumItemsTotal($items);
            }

            if ($payload !== []) {
                $workOrder->update($payload);
            }

            if ($items !== null) {
                $this->syncItems($workOrder, $items);
            }

            return $this->getById((int) $workOrder->getKey());
        });
    }

    public function convertFromClientOrder(int $id): WorkOrderModel
    {
        return DB::transaction(function () use ($id): WorkOrderModel {
            $clientOrder = ClientOrderModel::query()
                ->with(['items.article'])
                ->findOrFail($id);
            $itemsPayload = $clientOrder->items->map(static function ($item): array {
                $quantity = (float) $item->quantity;
                $price = (float) $item->cost_price;
                $total = $quantity * $price;

                return [
                    'article_id' => (int) $item->article_id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $total,
                ];
            })->values()->all();
            $totalAmount = $this->sumItemsTotal($itemsPayload);

            $workOrder = WorkOrderModel::query()->create([
                'number' => $this->generateNumber((int) Carbon::now()->format('Y')),
                'date' => Carbon::now()->toDateString(),
                'client_id' => (int) $clientOrder->client_id,
                'status' => 'draft',
                'description' => null,
                'total_amount' => $totalAmount,
            ]);

            foreach ($itemsPayload as $item) {
                $workOrder->items()->create([
                    'article_id' => (int) $item['article_id'],
                    'quantity' => number_format((float) $item['quantity'], 2, '.', ''),
                    'price' => number_format((float) $item['price'], 2, '.', ''),
                    'total' => number_format((float) $item['total'], 2, '.', ''),
                ]);
            }

            return $this->getById((int) $workOrder->getKey());
        });
    }

    private function sumItemsTotal(array $items): string
    {
        $sum = array_reduce($items, static function (float $carry, array $item): float {
            return $carry + (float) ($item['total'] ?? 0);
        }, 0.0);

        return number_format($sum, 2, '.', '');
    }

    private function syncItems(WorkOrderModel $workOrder, array $items): void
    {
        $workOrder->items()->delete();

        foreach ($items as $item) {
            $workOrder->items()->create([
                'article_id' => (int) $item['article_id'],
                'quantity' => number_format((float) $item['quantity'], 2, '.', ''),
                'price' => number_format((float) $item['price'], 2, '.', ''),
                'total' => number_format((float) $item['total'], 2, '.', ''),
            ]);
        }
    }

    private function generateNumber(int $year): string
    {
        $prefix = sprintf('OT-%d-', $year);
        $last = WorkOrderModel::query()
            ->where('number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('number')
            ->first();

        $next = 1;
        if ($last !== null) {
            $pattern = sprintf('/^OT-%d-(\d{4})$/', $year);
            if (preg_match($pattern, (string) $last->number, $matches) === 1) {
                $next = (int) $matches[1] + 1;
            } else {
                throw new DomainException('Invalid work order number format.');
            }
        }

        return sprintf('OT-%d-%04d', $year, $next);
    }
}
