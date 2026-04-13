<?php

namespace App\Services\Settings\Articles;

use App\Models\Settings\ArticleModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ArticleService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $sort = (string) Arr::get($filters, 'sort', 'id');
        $direction = strtolower((string) Arr::get($filters, 'direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) Arr::get($filters, 'search', ''));
        $activeOnly = filter_var(Arr::get($filters, 'active_only', false), FILTER_VALIDATE_BOOLEAN);

        $sortable = ['id', 'reference', 'name', 'price', 'vat_id', 'is_active', 'created_at', 'updated_at'];
        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        return ArticleModel::query()
            ->with('vat:id,name,rate')
            ->when($activeOnly, static fn (Builder $query): Builder => $query->active())
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static function (Builder $inner) use ($term): void {
                    $inner->where('reference', 'like', $term)
                        ->orWhere('name', 'like', $term);
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage);
    }

    public function create(array $data, ?UploadedFile $photo = null): ArticleModel
    {
        return $this->persist(null, $data, $photo);
    }

    public function update(ArticleModel $article, array $data, ?UploadedFile $photo = null): ArticleModel
    {
        return $this->persist($article, $data, $photo);
    }

    public function inactivate(ArticleModel $article): ArticleModel
    {
        if (! $article->is_active) {
            return $article;
        }

        return DB::transaction(function () use ($article): ArticleModel {
            $article->update(['is_active' => false]);

            return $article->refresh()->load('vat:id,name,rate');
        });
    }

    private function persist(?ArticleModel $article, array $data, ?UploadedFile $photo = null): ArticleModel
    {
        $payload = $this->normalize($data);
        $oldPhotoPath = $article?->photo_path;
        $newPhotoPath = null;

        if ($photo !== null) {
            $newPhotoPath = $photo->store('articles', 'public');
            $payload['photo_path'] = $newPhotoPath;
        }

        try {
            $saved = DB::transaction(function () use ($article, $payload): ArticleModel {
                if ($article === null) {
                    return ArticleModel::query()->create($payload);
                }

                $article->update($payload);

                return $article->refresh();
            });
        } catch (Throwable $e) {
            if ($newPhotoPath !== null) {
                Storage::disk('public')->delete($newPhotoPath);
            }
            throw $e;
        }

        if ($newPhotoPath !== null && $oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return $saved->load('vat:id,name,rate');
    }

    private function normalize(array $data): array
    {
        $payload = $data;

        foreach (['reference', 'name'] as $field) {
            if (array_key_exists($field, $payload) && is_string($payload[$field])) {
                $payload[$field] = trim($payload[$field]);
            }
        }

        if (array_key_exists('price', $payload)) {
            $payload['price'] = number_format((float) $payload['price'], 2, '.', '');
        }

        return $payload;
    }
}
