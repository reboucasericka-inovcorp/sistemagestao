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
            'type' => 'client',
            'nif' => '501111111',
            'name' => 'First',
            'postal_code' => '1000-100',
            'is_active' => true,
            'gdpr_consent' => false,
        ]);

        $second = $service->create([
            'type' => 'supplier',
            'nif' => '501111112',
            'name' => 'Second',
            'postal_code' => '1000-101',
            'is_active' => true,
            'gdpr_consent' => false,
        ]);

        $this->assertSame(1, (int) $first->number);
        $this->assertSame(2, (int) $second->number);
    }

    public function test_it_filters_clients_in_pagination(): void
    {
        $service = app(EntityService::class);

        EntityModel::query()->create([
            'type' => 'client',
            'number' => 1,
            'nif' => '500000001',
            'name' => 'Client',
            'is_active' => true,
            'gdpr_consent' => false,
        ]);
        EntityModel::query()->create([
            'type' => 'supplier',
            'number' => 2,
            'nif' => '500000002',
            'name' => 'Supplier',
            'is_active' => true,
            'gdpr_consent' => false,
        ]);

        $result = $service->paginate([
            'type' => 'client',
            'per_page' => 10,
        ]);

        $this->assertSame(1, $result->total());
        $this->assertSame('client', $result->items()[0]->type);
    }

    public function test_it_inactivates_entity(): void
    {
        $service = app(EntityService::class);
        $entity = EntityModel::query()->create([
            'type' => 'both',
            'number' => 1,
            'nif' => '500000003',
            'name' => 'Entity',
            'is_active' => true,
            'gdpr_consent' => true,
        ]);

        $updated = $service->inactivate($entity);

        $this->assertFalse((bool) $updated->is_active);
    }
}
