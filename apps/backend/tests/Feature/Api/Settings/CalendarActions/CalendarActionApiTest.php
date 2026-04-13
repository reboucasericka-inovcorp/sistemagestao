<?php

namespace Tests\Feature\Api\Settings\CalendarActions;

use App\Models\Settings\CalendarActionModel;
use App\Models\Settings\CalendarTypeModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarActionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_calendar_actions_with_meta(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        $calendarType = CalendarTypeModel::query()->create([
            'name' => 'Comercial',
            'color' => '#2563EB',
            'is_active' => true,
        ]);
        CalendarActionModel::query()->create([
            'name' => 'Visita Cliente',
            'calendar_type_id' => $calendarType->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/calendar-actions');

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
        $calendarType = CalendarTypeModel::query()->create([
            'name' => 'Interno',
            'color' => '#22C55E',
            'is_active' => true,
        ]);

        $createResponse = $this->postJson('/api/v1/calendar-actions', [
            'name' => 'Reuniao Planeamento',
            'calendar_type_id' => $calendarType->id,
            'is_active' => true,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'Reuniao Planeamento')
            ->assertJsonPath('data.calendar_type_id', $calendarType->id);

        $id = (int) $createResponse->json('data.id');

        $showResponse = $this->getJson("/api/v1/calendar-actions/{$id}");
        $showResponse->assertOk()->assertJsonPath('data.id', $id);

        $updateResponse = $this->putJson("/api/v1/calendar-actions/{$id}", [
            'name' => 'Reuniao Semanal',
            'calendar_type_id' => null,
        ]);
        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.name', 'Reuniao Semanal')
            ->assertJsonPath('data.calendar_type_id', null);

        $deleteResponse = $this->deleteJson("/api/v1/calendar-actions/{$id}");
        $deleteResponse->assertOk()->assertJsonPath('data.is_active', false);
    }
}
