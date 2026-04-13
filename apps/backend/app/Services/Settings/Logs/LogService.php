<?php

namespace App\Services\Settings\Logs;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Activity;

class LogService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $search = trim((string) Arr::get($filters, 'search', ''));
        $user = trim((string) Arr::get($filters, 'user', ''));
        $menu = trim((string) Arr::get($filters, 'menu', ''));
        $action = trim((string) Arr::get($filters, 'action', ''));
        $dateFrom = trim((string) Arr::get($filters, 'date_from', ''));
        $dateTo = trim((string) Arr::get($filters, 'date_to', ''));

        return Activity::query()
            ->with('causer')
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static function (Builder $inner) use ($term): void {
                    $inner->where('description', 'like', $term)
                        ->orWhere('log_name', 'like', $term)
                        ->orWhereHasMorph('causer', '*', static function (Builder $causerQuery) use ($term): void {
                            $causerQuery->where('name', 'like', $term);
                        });
                });
            })
            ->when($user !== '', static function (Builder $query) use ($user): void {
                $term = '%'.addcslashes($user, '%_\\').'%';
                $query->whereHasMorph('causer', '*', static function (Builder $causerQuery) use ($term, $user): void {
                    $causerQuery->where('name', 'like', $term)
                        ->orWhere('id', $user);
                });
            })
            ->when($menu !== '', static function (Builder $query) use ($menu): void {
                $query->where('log_name', $menu);
            })
            ->when($action !== '', static function (Builder $query) use ($action): void {
                $query->where('description', $action);
            })
            ->when($dateFrom !== '', static function (Builder $query) use ($dateFrom): void {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo !== '', static function (Builder $query) use ($dateTo): void {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
