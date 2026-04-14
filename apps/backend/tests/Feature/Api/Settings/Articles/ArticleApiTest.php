<?php

namespace Tests\Feature\Api\Settings\Articles;

use App\Models\Settings\ArticleModel;
use App\Models\Settings\VatModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ArticleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_articles_with_meta(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['articles.read']);
        $this->actingAs($user, 'sanctum');
        $vat = VatModel::query()->create([
            'name' => 'IVA 23%',
            'rate' => '23.00',
            'is_active' => true,
        ]);
        ArticleModel::query()->create([
            'reference' => 'ART-001',
            'name' => 'Servico A',
            'price' => '100.00',
            'vat_id' => $vat->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/articles');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_it_creates_updates_uploads_photo_and_inactivates_article(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['articles.create', 'articles.read', 'articles.update', 'articles.delete']);
        $this->actingAs($user, 'sanctum');
        Storage::fake('public');
        $vat = VatModel::query()->create([
            'name' => 'IVA reduzido',
            'rate' => '6.00',
            'is_active' => true,
        ]);

        $createResponse = $this->post('/api/v1/articles', [
            'reference' => 'ART-002',
            'name' => 'Produto B',
            'description' => 'Descricao',
            'price' => 10,
            'vat_id' => $vat->id,
            'photo' => UploadedFile::fake()->image('article.png'),
            'notes' => 'Notas',
            'is_active' => true,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.reference', 'ART-002')
            ->assertJsonPath('data.vat.id', $vat->id);

        $id = (int) $createResponse->json('data.id');
        $photoPath = ArticleModel::query()->findOrFail($id)->photo_path;
        $this->assertTrue(Storage::disk('public')->exists($photoPath));

        $updateResponse = $this->putJson("/api/v1/articles/{$id}", [
            'name' => 'Produto B Atualizado',
            'price' => 12.5,
        ]);
        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.name', 'Produto B Atualizado')
            ->assertJsonPath('data.price', '12.50');

        $deleteResponse = $this->deleteJson("/api/v1/articles/{$id}");
        $deleteResponse->assertOk()->assertJsonPath('data.is_active', false);
    }

    /**
     * @param list<string> $permissions
     */
    private function grantPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'sanctum',
            ]);
        }

        $user->givePermissionTo($permissions);
    }
}
