<?php

namespace Tests\Feature\Api\Settings\Company;

use App\Models\Settings\CompanyModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CompanyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_empty_company_when_not_configured(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['company.read']);
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/company');

        $response
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_it_creates_company_on_put_when_not_configured(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['company.update']);
        $this->actingAs($user, 'sanctum');
        Storage::fake('public');

        $response = $this->post('/api/v1/company', [
            '_method' => 'PUT',
            'name' => 'Inovcorp',
            'tax_number' => '123456789',
            'address' => 'Rua A',
            'postal_code' => '1000-001',
            'city' => 'Lisboa',
            'country_id' => null,
            'phone' => '210000000',
            'mobile' => '910000000',
            'email' => 'empresa@inovcorp.pt',
            'website' => 'https://inovcorp.pt',
            'is_active' => true,
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Inovcorp');

        $this->assertDatabaseCount('companies', 1);
        $this->assertTrue(
            Storage::disk('public')->exists((string) CompanyModel::query()->firstOrFail()->logo_path)
        );
    }

    public function test_it_updates_company_with_put(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['company.update']);
        $this->actingAs($user, 'sanctum');
        Storage::fake('public');

        CompanyModel::query()->create([
            'name' => 'Inovcorp',
            'tax_number' => '123456789',
            'is_active' => true,
        ]);

        $putResponse = $this->putJson('/api/v1/company', [
            'name' => 'Inovcorp SA',
            'tax_number' => '123456789',
            'city' => 'Porto',
            'is_active' => true,
        ]);
        $putResponse->assertOk()->assertJsonPath('data.name', 'Inovcorp SA');

        $secondPutResponse = $this->post('/api/v1/company', [
            '_method' => 'PUT',
            'tax_number' => '999999990',
            'name' => 'Inovcorp SA',
            'is_active' => true,
            'logo' => UploadedFile::fake()->image('new-logo.png'),
        ]);
        $secondPutResponse->assertOk();

        $this->assertDatabaseHas('companies', [
            'name' => 'Inovcorp SA',
            'tax_number' => '999999990',
            'city' => 'Porto',
        ]);
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
