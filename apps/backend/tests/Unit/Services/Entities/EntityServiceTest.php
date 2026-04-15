<?php

namespace Tests\Unit\Services\Entities;

use App\Models\EntityModel;
use App\Services\Entities\EntityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_next_number_when_creating_entities(): void
    {
        $service = app(EntityService::class);

        $first = $service->create([
            'is_client' => true,
            'is_supplier' => false,
            'nif' => '501111111',
            'name' => 'First',
            'postal_code' => '1000-100',
            'is_active' => true,
            'gdpr_consent' => false,
        ]);

        $second = $service->create([
            'is_client' => false,
            'is_supplier' => true,
            'nif' => '501111112',
            'name' => 'Second',
            'postal_code' => '1000-101',
            'is_active' => true,
            'gdpr_consent' => false,
        ]);

        $this->assertSame('ENT-000001', $first->number);
        $this->assertSame('ENT-000002', $second->number);
    }

    public function test_it_filters_clients_in_pagination(): void
    {
        $service = app(EntityService::class);

        EntityModel::query()->create([
            'is_client' => true,
            'is_supplier' => false,
            'number' => 'ENT-000001',
            'nif' => '500000001',
            'name' => 'Client',
            'is_active' => true,
            'gdpr_consent' => false,
        ]);
        EntityModel::query()->create([
            'is_client' => false,
            'is_supplier' => true,
            'number' => 'ENT-000002',
            'nif' => '500000002',
            'name' => 'Supplier',
            'is_active' => true,
            'gdpr_consent' => false,
        ]);

        $result = $service->paginate([
            'is_client' => true,
            'per_page' => 10,
        ]);

        $this->assertSame(1, $result->total());
        $this->assertTrue((bool) $result->items()[0]->is_client);
    }

    public function test_it_inactivates_entity(): void
    {
        $service = app(EntityService::class);
        $entity = EntityModel::query()->create([
            'is_client' => true,
            'is_supplier' => true,
            'number' => 'ENT-000001',
            'nif' => '500000003',
            'name' => 'Entity',
            'is_active' => true,
            'gdpr_consent' => true,
        ]);

        $updated = $service->inactivate($entity);

        $this->assertFalse((bool) $updated->is_active);
    }
}
