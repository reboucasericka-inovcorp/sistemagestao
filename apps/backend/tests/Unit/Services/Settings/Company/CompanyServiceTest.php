<?php

namespace Tests\Unit\Services\Settings\Company;

use App\Models\Settings\CompanyModel;
use App\Services\Settings\Company\CompanyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_keeps_singleton_record(): void
    {
        $service = app(CompanyService::class);

        $service->update([
            'name' => 'Inovcorp',
            'tax_number' => '123456789',
        ]);

        $service->update([
            'name' => 'Inovcorp Updated',
            'tax_number' => '123456789',
        ]);

        $this->assertSame(1, CompanyModel::query()->count());
        $this->assertDatabaseHas('companies', ['name' => 'Inovcorp Updated']);
    }

    public function test_it_replaces_old_logo_file(): void
    {
        Storage::fake('public');
        $service = app(CompanyService::class);

        $first = $service->update([
            'name' => 'Inovcorp',
            'tax_number' => '123456789',
        ], UploadedFile::fake()->image('first.png'));

        $firstLogo = $first->logo_path;
        $this->assertNotNull($firstLogo);
        Storage::disk('public')->assertExists($firstLogo);

        $second = $service->update([
            'name' => 'Inovcorp',
            'tax_number' => '123456789',
        ], UploadedFile::fake()->image('second.png'));

        $secondLogo = $second->logo_path;
        $this->assertNotSame($firstLogo, $secondLogo);
        Storage::disk('public')->assertExists($secondLogo);
        Storage::disk('public')->assertMissing($firstLogo);
    }
}
