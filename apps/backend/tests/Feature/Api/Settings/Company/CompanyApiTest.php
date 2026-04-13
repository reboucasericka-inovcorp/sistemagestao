<?php

namespace Tests\Feature\Api\Settings\Company;

use App\Models\Settings\CompanyModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_empty_company_when_not_configured(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');

        $response = $this->getJson('/api/v1/company');

        $response
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_it_creates_company(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        Storage::fake('public');

        $response = $this->post('/api/v1/company', [
            'name' => 'Inovcorp',
            'tax_number' => '123456789',
            'address' => 'Rua A',
            'postal_code' => '1000-001',
            'city' => 'Lisboa',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Inovcorp');

        $this->assertDatabaseCount('companies', 1);
        Storage::disk('public')->assertExists(CompanyModel::query()->firstOrFail()->logo_path);
    }

    public function test_it_updates_company_with_put_and_post_method_spoofing(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        Storage::fake('public');

        CompanyModel::query()->create([
            'name' => 'Inovcorp',
            'tax_number' => '123456789',
        ]);

        $putResponse = $this->putJson('/api/v1/company', [
            'name' => 'Inovcorp SA',
            'city' => 'Porto',
        ]);
        $putResponse->assertOk()->assertJsonPath('data.name', 'Inovcorp SA');

        $postMethodResponse = $this->post('/api/v1/company', [
            '_method' => 'PUT',
            'tax_number' => '999999990',
            'logo' => UploadedFile::fake()->image('new-logo.png'),
        ]);
        $postMethodResponse->assertOk();

        $this->assertDatabaseHas('companies', [
            'name' => 'Inovcorp SA',
            'tax_number' => '999999990',
            'city' => 'Porto',
        ]);
    }
}
