<?php

namespace App\Http\Controllers\Api\V1\Settings\CalendarTypes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CalendarTypes\StoreCalendarTypeRequest;
use App\Http\Requests\Settings\CalendarTypes\UpdateCalendarTypeRequest;
use App\Http\Resources\Settings\CalendarTypes\CalendarTypeResource;
use App\Models\Settings\CalendarTypeModel;
use App\Services\Settings\CalendarTypes\CalendarTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarTypeControllerAPI extends Controller
{
    public function __construct(
        protected CalendarTypeService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $calendarTypes = $this->service->paginate($request->only([
            'search',
            'page',
            'per_page',
            'sort',
            'direction',
            'active_only',
        ]));
        $resourceCollection = CalendarTypeResource::collection($calendarTypes);
        $serialized = $resourceCollection->resolve($request);

        return response()->json([
            'message' => 'Calendar types retrieved successfully',
            'data' => $serialized['data'] ?? [],
            'meta' => [
                'current_page' => $calendarTypes->currentPage(),
                'last_page' => $calendarTypes->lastPage(),
                'per_page' => $calendarTypes->perPage(),
                'total' => $calendarTypes->total(),
            ],
        ], 200);
    }

    public function store(StoreCalendarTypeRequest $request): JsonResponse
    {
        $calendarType = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Calendar type created successfully',
            'data' => new CalendarTypeResource($calendarType),
        ], 201);
    }

    public function show(CalendarTypeModel $calendar_type): JsonResponse
    {
        return response()->json([
            'message' => 'Calendar type retrieved successfully',
            'data' => new CalendarTypeResource($calendar_type),
        ], 200);
    }

    public function update(UpdateCalendarTypeRequest $request, CalendarTypeModel $calendar_type): JsonResponse
    {
        $updated = $this->service->update($calendar_type, $request->validated());

        return response()->json([
            'message' => 'Calendar type updated successfully',
            'data' => new CalendarTypeResource($updated),
        ], 200);
    }

    public function destroy(CalendarTypeModel $calendar_type): JsonResponse
    {
        $updated = $this->service->inactivate($calendar_type);

        return response()->json([
            'message' => 'Calendar type inactivated successfully',
            'data' => new CalendarTypeResource($updated),
        ], 200);
    }
}
