<?php

namespace Tests\Unit\Services\Settings\CalendarTypes;

use App\Models\Settings\CalendarTypeModel;
use App\Services\Settings\CalendarTypes\CalendarTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTypeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_calendar_type_data(): void
    {
        $service = app(CalendarTypeService::class);

        $calendarType = $service->create([
            'name' => '  Interno  ',
            'color' => '  #abc123  ',
            'is_active' => true,
        ]);

        $this->assertSame('Interno', $calendarType->name);
        $this->assertSame('#ABC123', $calendarType->color);
    }

    public function test_it_inactivates_calendar_type(): void
    {
        $service = app(CalendarTypeService::class);
        $calendarType = CalendarTypeModel::query()->create([
            'name' => 'Suporte',
            'color' => '#111111',
            'is_active' => true,
        ]);

        $updated = $service->inactivate($calendarType);

        $this->assertFalse((bool) $updated->is_active);
    }
}
