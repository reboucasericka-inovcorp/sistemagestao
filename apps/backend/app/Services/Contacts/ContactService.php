<?php

namespace App\Services\Contacts;

use App\Models\ContactModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ContactService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $search = trim((string) Arr::get($filters, 'search', ''));
        $entityId = Arr::get($filters, 'entity_id');
        $isActive = Arr::get($filters, 'is_active');

        return ContactModel::query()
            ->with(['entity:id,name', 'contactFunction:id,name'])
            ->when($entityId !== null && $entityId !== '', static fn (Builder $query): Builder => $query->where(
                'entity_id',
                (int) $entityId
            ))
            ->when($isActive !== null && $isActive !== '', static fn (Builder $query): Builder => $query->where(
                'is_active',
                filter_var($isActive, FILTER_VALIDATE_BOOLEAN)
            ))
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static function (Builder $subQuery) use ($term): void {
                    $subQuery
                        ->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->latest('id')
            ->paginate($perPage);
    }

    public function create(array $data): ContactModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(function () use ($payload): ContactModel {
            $payload['number'] = $this->nextNumber();

            return ContactModel::create($payload);
        });
    }

    public function update(ContactModel $contact, array $data): ContactModel
    {
        $payload = $this->normalize($data);

        return DB::transaction(function () use ($contact, $payload): ContactModel {
            $contact->update($payload);

            return $contact->refresh();
        });
    }

    public function inactivate(ContactModel $contact): ContactModel
    {
        if (! $contact->is_active) {
            return $contact;
        }

        return DB::transaction(function () use ($contact): ContactModel {
            $contact->update(['is_active' => false]);

            return $contact->refresh();
        });
    }

    private function normalize(array $data): array
    {
        $payload = $data;

        if (array_key_exists('email', $payload)) {
            $payload['email'] = $payload['email']
                ? strtolower((string) $payload['email'])
                : null;
        }

        if (array_key_exists('first_name', $payload)) {
            $payload['first_name'] = trim((string) $payload['first_name']);
        }

        if (array_key_exists('last_name', $payload)) {
            $payload['last_name'] = trim((string) $payload['last_name']);
        }

        return $payload;
    }

    private function nextNumber(): string
    {
        /** @var ContactModel|null $last */
        $last = ContactModel::query()->latest('id')->first(['number']);

        if (! $last || ! preg_match('/^CONT-(\d{4,})$/', (string) $last->number, $matches)) {
            return 'CONT-0001';
        }

        $next = (int) $matches[1] + 1;

        return sprintf('CONT-%04d', $next);
    }
}
