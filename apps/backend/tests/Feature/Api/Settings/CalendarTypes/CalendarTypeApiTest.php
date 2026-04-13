<?php

namespace Tests\Feature\Api\Settings\CalendarTypes;

use App\Models\Settings\CalendarTypeModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTypeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_calendar_types_with_meta(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        CalendarTypeModel::query()->create([
            'name' => 'Reuniao',
            'color' => '#22C55E',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/calendar-types');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_it_performs_basic_crud_flow(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        $createResponse = $this->postJson('/api/v1/calendar-types', [
            'name' => 'Visita',
            'color' => '#3B82F6',
            'is_active' => true,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'Visita')
            ->assertJsonPath('data.color', '#3B82F6');

        $id = (int) $createResponse->json('data.id');

        $showResponse = $this->getJson("/api/v1/calendar-types/{$id}");
        $showResponse->assertOk()->assertJsonPath('data.id', $id);

        $updateResponse = $this->putJson("/api/v1/calendar-types/{$id}", [
            'name' => 'Visita Comercial',
            'color' => '#2563EB',
        ]);
        $updateResponse->assertOk()->assertJsonPath('data.name', 'Visita Comercial');

        $deleteResponse = $this->deleteJson("/api/v1/calendar-types/{$id}");
        $deleteResponse->assertOk()->assertJsonPath('data.is_active', false);
    }
}
