<?php

namespace App\Services\Settings\Company;

use App\Models\Settings\CompanyModel;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CompanyService
{
    public function get(): ?CompanyModel
    {
        return CompanyModel::query()->first();
    }

    public function create(array $data, ?UploadedFile $logo = null): CompanyModel
    {
        if (CompanyModel::query()->exists()) {
            throw new DomainException('Company already exists. Use update endpoint.');
        }

        return $this->persist(null, $data, $logo);
    }

    public function update(array $data, ?UploadedFile $logo = null): CompanyModel
    {
        $company = CompanyModel::query()->firstOrCreate([], $this->normalize($data));

        return $this->persist($company, $data, $logo);
    }

    private function persist(?CompanyModel $company, array $data, ?UploadedFile $logo = null): CompanyModel
    {
        $payload = $this->normalize($data);
        $oldLogoPath = $company?->logo_path;
        $newLogoPath = null;

        if ($logo !== null) {
            $newLogoPath = $logo->store('company', 'public');
            $payload['logo_path'] = $newLogoPath;
        }

        try {
            $saved = DB::transaction(function () use ($company, $payload): CompanyModel {
                if ($company === null) {
                    return CompanyModel::query()->create($payload);
                }

                $company->update($payload);

                return $company->refresh();
            });
        } catch (Throwable $e) {
            if ($newLogoPath !== null) {
                Storage::disk('public')->delete($newLogoPath);
            }
            throw $e;
        }

        if ($newLogoPath !== null && $oldLogoPath && $oldLogoPath !== $newLogoPath) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        return $saved;
    }

    private function normalize(array $data): array
    {
        $payload = $data;

        foreach (['name', 'tax_number', 'address', 'postal_code', 'city'] as $field) {
            if (array_key_exists($field, $payload) && is_string($payload[$field])) {
                $payload[$field] = trim($payload[$field]);
            }
        }

        if (array_key_exists('tax_number', $payload) && $payload['tax_number'] !== null) {
            $payload['tax_number'] = preg_replace('/\D/', '', (string) $payload['tax_number']);
        }

        return $payload;
    }
}
