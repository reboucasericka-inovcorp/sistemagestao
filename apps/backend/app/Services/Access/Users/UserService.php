<?php

namespace App\Services\Access\Users;

use App\Models\Access\RoleModel;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) Arr::get($filters, 'search', ''));
        $activeOnly = filter_var(Arr::get($filters, 'active_only', false), FILTER_VALIDATE_BOOLEAN);
        $role = Arr::get($filters, 'role');

        $sortable = ['id', 'name', 'email', 'phone', 'is_active', 'created_at', 'updated_at'];
        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return User::query()
            ->with('roles:id,name')
            ->when($activeOnly, static fn (Builder $query): Builder => $query->active())
            ->when($role !== null && (string) $role !== '', static function (Builder $query) use ($role): void {
                $query->whereHas('roles', static function (Builder $roleQuery) use ($role): void {
                    $roleQuery
                        ->where('id', (int) $role)
                        ->orWhere('name', (string) $role);
                });
            })
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static function (Builder $inner) use ($term): void {
                    $inner
                        ->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $payload = $this->normalize($data);
            $roleId = (int) $payload['role_id'];
            unset($payload['role_id']);
            $rawPassword = Str::random(12);
            unset($payload['password']);
            $payload['password'] = Hash::make($rawPassword);
            $payload['is_active'] = $payload['is_active'] ?? true;

            $user = User::query()->create($payload);
            $role = $this->resolveRole($roleId);
            $user->syncRoles([$role->name]);

            return $user->refresh()->load('roles:id,name');
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $payload = $this->normalize($data);
            $roleId = array_key_exists('role_id', $payload) ? (int) $payload['role_id'] : null;
            unset($payload['role_id']);

            if ($payload !== []) {
                $user->update($payload);
            }

            if ($roleId !== null) {
                $role = $this->resolveRole($roleId);
                $user->syncRoles([$role->name]);
            }

            return $user->refresh()->load('roles:id,name');
        });
    }

    public function inactivate(User $user): User
    {
        if (! $user->is_active) {
            return $user->load('roles:id,name');
        }

        return DB::transaction(function () use ($user): User {
            $user->update(['is_active' => false]);

            return $user->refresh()->load('roles:id,name');
        });
    }

    private function resolveRole(int $roleId): RoleModel
    {
        $role = RoleModel::query()
            ->where('guard_name', 'sanctum')
            ->findOrFail($roleId);

        if (! $role->is_active) {
            throw new DomainException('Cannot assign an inactive role.');
        }

        return $role;
    }

    private function normalize(array $data): array
    {
        $payload = $data;

        foreach (['name', 'email', 'phone', 'password'] as $field) {
            if (array_key_exists($field, $payload) && is_string($payload[$field])) {
                $payload[$field] = trim($payload[$field]);
            }
        }

        return $payload;
    }
}
