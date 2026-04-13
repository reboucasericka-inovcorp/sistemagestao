<?php

namespace Tests\Unit\Services\Settings\CalendarActions;

use App\Models\Settings\CalendarActionModel;
use App\Services\Settings\CalendarActions\CalendarActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarActionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_calendar_action_with_trimmed_name(): void
    {
        $service = app(CalendarActionService::class);

        $calendarAction = $service->create([
            'name' => '  Follow Up  ',
            'calendar_type_id' => null,
            'is_active' => true,
        ]);

        $this->assertSame('Follow Up', $calendarAction->name);
    }

    public function test_it_inactivates_calendar_action(): void
    {
        $service = app(CalendarActionService::class);
        $calendarAction = CalendarActionModel::query()->create([
            'name' => 'Chamada',
            'calendar_type_id' => null,
            'is_active' => true,
        ]);

        $updated = $service->inactivate($calendarAction);

        $this->assertFalse((bool) $updated->is_active);
    }
}
