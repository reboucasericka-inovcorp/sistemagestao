<?php

namespace Tests\Feature\Api\Finance;

use App\Models\EntityModel;
use App\Models\Finance\CustomerAccountModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_increases_balance(): void
    {
        $this->actingAsWithPermissions(['customer-accounts.create', 'customer-accounts.read']);
        $entity = EntityModel::factory()->create([
            'is_client' => true,
            'is_supplier' => false,
        ]);

        $response = $this->postJson("/api/v1/customer-accounts/{$entity->id}/movements", [
            'type' => 'credit',
            'amount' => 100,
            'description' => 'Credito inicial',
            'date' => now()->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.balance', '100.00');
    }

    public function test_debit_decreases_balance(): void
    {
        $this->actingAsWithPermissions(['customer-accounts.create', 'customer-accounts.read']);
        $entity = EntityModel::factory()->create([
            'is_client' => true,
            'is_supplier' => false,
        ]);

        CustomerAccountModel::query()->create([
            'entity_id' => $entity->id,
            'balance' => '200.00',
        ]);

        $response = $this->postJson("/api/v1/customer-accounts/{$entity->id}/movements", [
            'type' => 'debit',
            'amount' => 50,
            'description' => 'Debito teste',
            'date' => now()->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.balance', '150.00');
    }

    public function test_balance_is_accumulated_correctly_with_multiple_movements(): void
    {
        $this->actingAsWithPermissions(['customer-accounts.create', 'customer-accounts.read']);
        $entity = EntityModel::factory()->create([
            'is_client' => true,
            'is_supplier' => false,
        ]);

        $this->postJson("/api/v1/customer-accounts/{$entity->id}/movements", [
            'type' => 'credit',
            'amount' => 100,
            'description' => 'Credito',
            'date' => now()->toDateString(),
        ])->assertCreated();

        $this->postJson("/api/v1/customer-accounts/{$entity->id}/movements", [
            'type' => 'debit',
            'amount' => 20,
            'description' => 'Debito',
            'date' => now()->toDateString(),
        ])->assertCreated();

        $this->getJson('/api/v1/customer-accounts')
            ->assertOk()
            ->assertJsonFragment([
                'entity_id' => $entity->id,
                'balance' => '80.00',
            ]);
    }

    public function test_it_returns_403_without_create_permission(): void
    {
        $this->actingAsWithPermissions(['customer-accounts.read']);
        $entity = EntityModel::factory()->create([
            'is_client' => true,
            'is_supplier' => false,
        ]);

        $this->postJson("/api/v1/customer-accounts/{$entity->id}/movements", [
            'type' => 'credit',
            'amount' => 100,
            'description' => 'Sem permissao',
            'date' => now()->toDateString(),
        ])->assertForbidden();
    }
}
