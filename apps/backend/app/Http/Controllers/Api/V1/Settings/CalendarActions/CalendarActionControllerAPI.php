<?php

namespace App\Http\Controllers\Api\V1\Settings\CalendarActions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CalendarActions\StoreCalendarActionRequest;
use App\Http\Requests\Settings\CalendarActions\UpdateCalendarActionRequest;
use App\Http\Resources\Settings\CalendarActions\CalendarActionResource;
use App\Models\Settings\CalendarActionModel;
use App\Services\Settings\CalendarActions\CalendarActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarActionControllerAPI extends Controller
{
    public function __construct(
        protected CalendarActionService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $calendarActions = $this->service->paginate($request->only([
            'search',
            'page',
            'per_page',
            'sort',
            'direction',
            'active_only',
        ]));
        $serializedItems = CalendarActionResource::collection($calendarActions->items())->resolve($request);

        return response()->json([
            'message' => 'Calendar actions retrieved successfully',
            'data' => $serializedItems,
            'meta' => [
                'current_page' => $calendarActions->currentPage(),
                'last_page' => $calendarActions->lastPage(),
                'per_page' => $calendarActions->perPage(),
                'total' => $calendarActions->total(),
            ],
        ], 200);
    }

    public function store(StoreCalendarActionRequest $request): JsonResponse
    {
        $calendarAction = $this->service->create($request->validated());
        $calendarAction->load('calendarType:id,name');

        return response()->json([
            'message' => 'Calendar action created successfully',
            'data' => new CalendarActionResource($calendarAction),
        ], 201);
    }

    public function show(CalendarActionModel $calendar_action): JsonResponse
    {
        $calendar_action->load('calendarType:id,name');

        return response()->json([
            'message' => 'Calendar action retrieved successfully',
            'data' => new CalendarActionResource($calendar_action),
        ], 200);
    }

    public function update(UpdateCalendarActionRequest $request, CalendarActionModel $calendar_action): JsonResponse
    {
        $updated = $this->service->update($calendar_action, $request->validated());
        $updated->load('calendarType:id,name');

        return response()->json([
            'message' => 'Calendar action updated successfully',
            'data' => new CalendarActionResource($updated),
        ], 200);
    }

    public function destroy(CalendarActionModel $calendar_action): JsonResponse
    {
        $updated = $this->service->inactivate($calendar_action);
        $updated->load('calendarType:id,name');

        return response()->json([
            'message' => 'Calendar action inactivated successfully',
            'data' => new CalendarActionResource($updated),
        ], 200);
    }
}
