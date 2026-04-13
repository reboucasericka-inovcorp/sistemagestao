<?php

namespace Tests\Unit\Services\Settings\ContactFunctions;

use App\Models\Settings\ContactFunctionModel;
use App\Services\Settings\ContactFunctions\ContactFunctionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFunctionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_contact_function_with_trimmed_name(): void
    {
        $service = app(ContactFunctionService::class);

        $contactFunction = $service->create([
            'name' => '  Operacional  ',
            'is_active' => true,
        ]);

        $this->assertSame('Operacional', $contactFunction->name);
    }

    public function test_it_inactivates_contact_function(): void
    {
        $service = app(ContactFunctionService::class);
        $contactFunction = ContactFunctionModel::query()->create([
            'name' => 'Suporte',
            'is_active' => true,
        ]);

        $updated = $service->inactivate($contactFunction);

        $this->assertFalse((bool) $updated->is_active);
    }
}
