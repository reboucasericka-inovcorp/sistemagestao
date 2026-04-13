<?php

namespace Tests\Feature\Api\Settings\Vat;

use App\Models\Settings\VatModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_vat_entries_with_meta(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        VatModel::query()->create([
            'name' => 'IVA 23%',
            'rate' => '23.00',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/vat');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_it_creates_updates_and_inactivates_vat_entry(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        $createResponse = $this->postJson('/api/v1/vat', [
            'name' => 'IVA reduzido',
            'rate' => 6,
            'is_active' => true,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'IVA reduzido')
            ->assertJsonPath('data.rate', 6);

        $id = (int) $createResponse->json('data.id');

        $updateResponse = $this->putJson("/api/v1/vat/{$id}", [
            'rate' => 13,
        ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.rate', 13);

        $deleteResponse = $this->deleteJson("/api/v1/vat/{$id}");
        $deleteResponse
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }
}
