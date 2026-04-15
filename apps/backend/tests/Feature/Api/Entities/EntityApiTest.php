<?php

namespace Tests\Feature\Api\Entities;

use App\Models\EntityModel;
use App\Models\Settings\CountryModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EntityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_entity_and_returns_expected_payload(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['entities.create', 'entities.read']);
        $this->actingAs($user, 'sanctum');
        $country = CountryModel::query()->create([
            'name' => 'Portugal',
            'code' => 'PT',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/entities', [
            'is_client' => true,
            'is_supplier' => false,
            'nif' => '501964843',
            'name' => 'Cliente A',
            'address' => 'Rua A',
            'postal_code' => '1000-100',
            'city' => 'Lisboa',
            'country_id' => $country->id,
            'email' => 'cliente@example.com',
            'gdpr_consent' => true,
            'is_active' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.is_client', true)
            ->assertJsonPath('data.is_supplier', false)
            ->assertJsonPath('data.nif', '501964843')
            ->assertJsonPath('data.country.id', $country->id)
            ->assertJsonPath('data.number', 'ENT-000001');
    }

    public function test_it_validates_unique_nif(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['entities.create']);
        $this->actingAs($user, 'sanctum');

        EntityModel::query()->create([
            'is_client' => true,
            'is_supplier' => false,
            'number' => 'ENT-000001',
            'nif' => '123456789',
            'name' => 'Existing',
            'is_active' => true,
            'gdpr_consent' => false,
        ]);

        $response = $this->postJson('/api/v1/entities', [
            'is_client' => false,
            'is_supplier' => true,
            'nif' => '123456789',
            'name' => 'Duplicated',
            'postal_code' => '1000-100',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['nif']);
    }

    public function test_it_filters_by_is_client_and_returns_meta(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['entities.read']);
        $this->actingAs($user, 'sanctum');

        EntityModel::query()->create([
            'is_client' => true,
            'is_supplier' => false,
            'number' => 'ENT-000001',
            'nif' => '100000001',
            'name' => 'Client',
            'is_active' => true,
            'gdpr_consent' => false,
        ]);
        EntityModel::query()->create([
            'is_client' => false,
            'is_supplier' => true,
            'number' => 'ENT-000002',
            'nif' => '100000002',
            'name' => 'Supplier',
            'is_active' => true,
            'gdpr_consent' => false,
        ]);

        $response = $this->getJson('/api/v1/entities?is_client=true&per_page=1');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_client', true)
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_it_inactivates_entity(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['entities.delete']);
        $this->actingAs($user, 'sanctum');

        $entity = EntityModel::query()->create([
            'is_client' => true,
            'is_supplier' => true,
            'number' => 'ENT-000001',
            'nif' => '200000001',
            'name' => 'Entity',
            'is_active' => true,
            'gdpr_consent' => true,
        ]);

        $response = $this->deleteJson("/api/v1/entities/{$entity->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
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
