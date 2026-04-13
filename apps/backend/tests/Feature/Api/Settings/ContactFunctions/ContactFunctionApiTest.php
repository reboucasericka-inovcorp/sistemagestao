<?php

namespace Tests\Feature\Api\Settings\ContactFunctions;

use App\Models\Settings\ContactFunctionModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFunctionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_contact_functions_with_standard_response_shape(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        ContactFunctionModel::query()->create([
            'name' => 'Comercial',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/contact-functions');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
            ]);
    }

    public function test_it_performs_basic_crud_flow(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        $createResponse = $this->postJson('/api/v1/contact-functions', [
            'name' => 'Financeiro',
            'is_active' => true,
        ]);

        $createResponse->assertCreated()->assertJsonPath('data.name', 'Financeiro');
        $id = (int) $createResponse->json('data.id');

        $showResponse = $this->getJson("/api/v1/contact-functions/{$id}");
        $showResponse->assertOk()->assertJsonPath('data.id', $id);

        $updateResponse = $this->putJson("/api/v1/contact-functions/{$id}", [
            'name' => 'Financeiro Senior',
        ]);
        $updateResponse->assertOk()->assertJsonPath('data.name', 'Financeiro Senior');

        $deleteResponse = $this->deleteJson("/api/v1/contact-functions/{$id}");
        $deleteResponse->assertOk()->assertJsonPath('data.is_active', false);
    }
}
