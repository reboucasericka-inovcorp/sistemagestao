<?php

namespace Tests\Unit\Services\Settings\Articles;

use App\Models\Settings\ArticleModel;
use App\Models\Settings\VatModel;
use App\Services\Settings\Articles\ArticleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_price_and_text_fields(): void
    {
        $vat = VatModel::query()->create([
            'name' => 'IVA 23%',
            'rate' => '23.00',
            'is_active' => true,
        ]);
        $service = app(ArticleService::class);

        $article = $service->create([
            'reference' => '  ART-100  ',
            'name' => '  Nome Artigo  ',
            'price' => 23,
            'vat_id' => $vat->id,
            'is_active' => true,
        ]);

        $this->assertSame('ART-100', $article->reference);
        $this->assertSame('Nome Artigo', $article->name);
        $this->assertSame('23.00', $article->getRawOriginal('price'));
    }

    public function test_it_replaces_old_photo_file(): void
    {
        Storage::fake('public');
        $vat = VatModel::query()->create([
            'name' => 'IVA 13%',
            'rate' => '13.00',
            'is_active' => true,
        ]);
        $service = app(ArticleService::class);

        $first = $service->create([
            'reference' => 'ART-101',
            'name' => 'Servico',
            'price' => 50,
            'vat_id' => $vat->id,
            'is_active' => true,
        ], UploadedFile::fake()->image('first.png'));

        $firstPhoto = $first->photo_path;
        Storage::disk('public')->assertExists($firstPhoto);

        $second = $service->update($first, [
            'name' => 'Servico Atualizado',
        ], UploadedFile::fake()->image('second.png'));

        $secondPhoto = $second->photo_path;
        $this->assertNotSame($firstPhoto, $secondPhoto);
        Storage::disk('public')->assertExists($secondPhoto);
        Storage::disk('public')->assertMissing($firstPhoto);
    }
}
