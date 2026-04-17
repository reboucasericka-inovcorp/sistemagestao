<?php

namespace App\Services\DigitalArchive;

use App\Models\DigitalFileModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DigitalFileService
{
    public function paginate(array $filters)
    {
        $perPage = min(max((int) Arr::get($filters, 'per_page', 15), 1), 100);
        $search = trim((string) Arr::get($filters, 'search', ''));

        return DigitalFileModel::query()
            ->with(['uploader:id,name,email', 'entity:id,name'])
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';
                $query->where('name', 'like', $term);
            })
            ->latest()
            ->paginate($perPage);
    }

    public function store(array $data, UploadedFile $file, int $userId): DigitalFileModel
    {
        $path = $file->store('digital-files', 'private');

        try {
            return DB::transaction(function () use ($data, $file, $userId, $path): DigitalFileModel {
                return DigitalFileModel::query()->create([
                    'name' => $data['name'] ?? $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'category' => $data['category'] ?? null,
                    'description' => $data['description'] ?? null,
                    'uploaded_by' => $userId,
                    'entity_id' => $data['entity_id'] ?? null,
                    'fileable_id' => $data['fileable_id'] ?? null,
                    'fileable_type' => $data['fileable_type'] ?? null,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('private')->delete($path);
            throw $exception;
        }
    }

    public function download(DigitalFileModel $file)
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $privateDisk */
        $privateDisk = Storage::disk('private');

        return $privateDisk->download(
            $file->file_path,
            $file->name.'.'.pathinfo($file->file_path, PATHINFO_EXTENSION)
        );
    }

    public function delete(DigitalFileModel $file): void
    {
        DB::transaction(function () use ($file): void {
            Storage::disk('private')->delete($file->file_path);
            $file->delete();
        });
    }
}
