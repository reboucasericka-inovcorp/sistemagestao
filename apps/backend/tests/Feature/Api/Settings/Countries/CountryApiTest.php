<?php

namespace Tests\Feature\Api\Settings\Countries;

use App\Models\Settings\CountryModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_countries_with_standard_response_shape(): void
    {
        $this->actingAsWithPermissions(['countries.read']);
        CountryModel::query()->create([
            'name' => 'Portugal',
            'code' => 'PT',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/countries');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
            ]);
    }

    public function test_it_creates_a_country(): void
    {
        $this->actingAsWithPermissions(['countries.create']);

        $payload = [
            'name' => 'Espanha',
            'code' => 'ES',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/countries', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Espanha')
            ->assertJsonPath('data.code', 'ES');

        $this->assertDatabaseHas('countries', [
            'name' => 'Espanha',
            'code' => 'ES',
        ]);
    }
}
