<?php

namespace App\Http\Controllers\Api\V1\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Http\Requests\Calendar\UpdateCalendarEventRequest;
use App\Http\Resources\Calendar\CalendarEventResource;
use App\Models\Calendar\CalendarEventModel;
use App\Services\Calendar\CalendarEventService;
use App\Support\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarEventControllerAPI extends Controller
{
    public function __construct(
        protected CalendarEventService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $events = $this->service->list($request->only([
            'user_id',
            'entity_id',
            'start',
            'end',
        ]));

        return ApiResponse::success(
            'Calendar events retrieved successfully',
            CalendarEventResource::collection($events)->resolve($request)
        );
    }

    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        try {
            $event = $this->service->create(
                $request->validated(),
                (int) $request->user()->id
            );
        } catch (DomainException $e) {
            return ApiResponse::success($e->getMessage(), null, 422);
        }

        return ApiResponse::success('Calendar event created successfully', new CalendarEventResource($event), 201);
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEventModel $event): JsonResponse
    {
        try {
            $updated = $this->service->update($event, $request->validated());
        } catch (DomainException $e) {
            return ApiResponse::success($e->getMessage(), null, 422);
        }

        return ApiResponse::success('Calendar event updated successfully', new CalendarEventResource($updated));
    }

    public function destroy(CalendarEventModel $event): JsonResponse
    {
        $this->service->delete($event);

        return ApiResponse::success('Calendar event inactivated successfully', null);
    }
}
