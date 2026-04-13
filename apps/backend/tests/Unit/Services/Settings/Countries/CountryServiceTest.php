<?php

namespace Tests\Unit\Services\Settings\Countries;

use App\Models\Settings\CountryModel;
use App\Services\Settings\Countries\CountryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_code_to_uppercase_on_create(): void
    {
        $service = app(CountryService::class);

        $country = $service->create([
            'name' => 'Portugal',
            'code' => 'pt',
            'is_active' => true,
        ]);

        $this->assertSame('PT', $country->code);
    }

    public function test_it_inactivates_country(): void
    {
        $service = app(CountryService::class);
        $country = CountryModel::query()->create([
            'name' => 'France',
            'code' => 'FR',
            'is_active' => true,
        ]);

        $updated = $service->inactivate($country);

        $this->assertFalse((bool) $updated->is_active);
    }
}
