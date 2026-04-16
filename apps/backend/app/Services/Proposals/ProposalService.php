<?php

namespace App\Services\Proposals;

use App\Models\Proposals\ProposalModel;
use App\Services\Orders\ClientOrderService;
use App\Services\Settings\Company\CompanyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProposalService
{
    public function getAll(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $search = trim((string) Arr::get($filters, 'search', ''));
        $status = trim((string) Arr::get($filters, 'status', ''));
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortable = ['id', 'number', 'proposal_date', 'valid_until', 'status', 'total_amount', 'created_at'];

        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return ProposalModel::query()
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

    public function getById(int $id): ProposalModel
    {
        return ProposalModel::query()
            ->with(['client:id,name,number', 'items.article:id,name,reference', 'items.supplier:id,name,number'])
            ->findOrFail($id);
    }

    public function create(array $data): ProposalModel
    {
        return DB::transaction(function () use ($data): ProposalModel {
            $proposalDate = Carbon::parse((string) $data['proposal_date'])->startOfDay();
            $validUntil = $proposalDate->copy()->addDays(30);
            $items = Arr::get($data, 'items', []);
            $totalAmount = $this->sumItemsTotal($items);

            $proposal = ProposalModel::query()->create([
                'number' => $this->generateNumber($proposalDate->year),
                'proposal_date' => $proposalDate->toDateString(),
                'valid_until' => $validUntil->toDateString(),
                'client_id' => (int) $data['client_id'],
                'status' => 'draft',
                'total_amount' => $totalAmount,
            ]);

            $this->syncItems($proposal, $items);

            return $this->getById((int) $proposal->getKey());
        });
    }

    public function update(int $id, array $data): ProposalModel
    {
        return DB::transaction(function () use ($id, $data): ProposalModel {
            $proposal = ProposalModel::query()->findOrFail($id);
            $items = Arr::get($data, 'items');

            $payload = [];
            if (array_key_exists('proposal_date', $data)) {
                $proposalDate = Carbon::parse((string) $data['proposal_date'])->startOfDay();
                $payload['proposal_date'] = $proposalDate->toDateString();
                $payload['valid_until'] = $proposalDate->copy()->addDays(30)->toDateString();
            }
            if (array_key_exists('client_id', $data)) {
                $payload['client_id'] = (int) $data['client_id'];
            }
            if (array_key_exists('status', $data)) {
                $payload['status'] = (string) $data['status'];
            }
            if ($items !== null) {
                $payload['total_amount'] = $this->sumItemsTotal($items);
            }

            if ($payload !== []) {
                $proposal->update($payload);
            }

            if ($items !== null) {
                $this->syncItems($proposal, $items);
            }

            return $this->getById((int) $proposal->getKey());
        });
    }

    public function convertToOrder(int $id): ProposalModel
    {
        return DB::transaction(function () use ($id): ProposalModel {
            $proposal = ProposalModel::query()->with('items')->findOrFail($id);

            if ($proposal->status !== 'closed') {
                $proposal->update(['status' => 'closed']);
                app(ClientOrderService::class)->createFromProposal([
                    'client_id' => (int) $proposal->client_id,
                    'total_amount' => (float) $proposal->total_amount,
                    'items' => $proposal->items->map(static fn ($item): array => [
                        'article_id' => (int) $item->article_id,
                        'supplier_id' => (int) $item->supplier_id,
                        'quantity' => (float) $item->quantity,
                        'cost_price' => (float) $item->cost_price,
                        'total' => (float) $item->total,
                    ])->values()->all(),
                ]);
            }

            return $this->getById((int) $proposal->getKey());
        });
    }

    public function generatePdf(int $id)
    {
        $proposal = ProposalModel::query()
            ->with(['client', 'items.article'])
            ->findOrFail($id);

        $company = app(CompanyService::class)->getCurrent();

        return Pdf::loadView('pdf.proposal', [
            'proposal' => $proposal,
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

    private function syncItems(ProposalModel $proposal, array $items): void
    {
        $proposal->items()->delete();

        foreach ($items as $item) {
            $proposal->items()->create([
                'article_id' => (int) $item['article_id'],
                'supplier_id' => (int) $item['supplier_id'],
                'quantity' => number_format((float) $item['quantity'], 2, '.', ''),
                'cost_price' => number_format((float) $item['cost_price'], 2, '.', ''),
                'total' => number_format((float) $item['total'], 2, '.', ''),
            ]);
        }
    }

    private function generateNumber(int $year): string
    {
        $prefix = sprintf('PR-%d-', $year);
        $last = ProposalModel::query()
            ->where('number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('number')
            ->first();

        $next = 1;
        if ($last !== null) {
            $pattern = sprintf('/^PR-%d-(\d{4})$/', $year);
            if (preg_match($pattern, (string) $last->number, $matches) === 1) {
                $next = (int) $matches[1] + 1;
            } else {
                throw new DomainException('Invalid proposal number format.');
            }
        }

        return sprintf('PR-%d-%04d', $year, $next);
    }
}
