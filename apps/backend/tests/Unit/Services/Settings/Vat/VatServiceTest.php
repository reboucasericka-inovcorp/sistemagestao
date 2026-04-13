<?php

namespace Tests\Unit\Services\Settings\Vat;

use App\Models\Settings\VatModel;
use App\Services\Settings\Vat\VatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_rate_to_two_decimal_places(): void
    {
        $service = app(VatService::class);

        $vat = $service->create([
            'name' => 'IVA 23%',
            'rate' => 23,
            'is_active' => true,
        ]);

        $this->assertSame('23.00', $vat->getRawOriginal('rate'));
    }

    public function test_it_inactivates_vat_entry(): void
    {
        $service = app(VatService::class);
        $vat = VatModel::query()->create([
            'name' => 'IVA 13%',
            'rate' => '13.00',
            'is_active' => true,
        ]);

        $updated = $service->inactivate($vat);

        $this->assertFalse((bool) $updated->is_active);
    }
}
