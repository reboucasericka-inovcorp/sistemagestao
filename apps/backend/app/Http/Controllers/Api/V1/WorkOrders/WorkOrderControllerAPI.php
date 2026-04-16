<?php

namespace App\Http\Controllers\Api\V1\WorkOrders;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkOrders\StoreWorkOrderRequest;
use App\Http\Requests\WorkOrders\UpdateWorkOrderRequest;
use App\Http\Resources\WorkOrders\WorkOrderResource;
use App\Services\WorkOrders\WorkOrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkOrderControllerAPI extends Controller
{
    public function __construct(
        protected WorkOrderService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $workOrders = $this->service->getAll($request->only([
            'search',
            'status',
            'page',
            'per_page',
            'sort',
            'direction',
        ]));

        $serializedItems = WorkOrderResource::collection($workOrders->items())->resolve($request);

        return ApiResponse::success('Work orders retrieved successfully', $serializedItems, 200, [
            'meta' => [
                'current_page' => $workOrders->currentPage(),
                'last_page' => $workOrders->lastPage(),
                'per_page' => $workOrders->perPage(),
                'total' => $workOrders->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $workOrder = $this->service->getById($id);

        return ApiResponse::success('Work order retrieved successfully', new WorkOrderResource($workOrder));
    }

    public function store(StoreWorkOrderRequest $request): JsonResponse
    {
        $workOrder = $this->service->create($request->validated());

        return ApiResponse::success('Work order created successfully', new WorkOrderResource($workOrder), 201);
    }

    public function update(UpdateWorkOrderRequest $request, int $id): JsonResponse
    {
        $workOrder = $this->service->update($id, $request->validated());

        return ApiResponse::success('Work order updated successfully', new WorkOrderResource($workOrder));
    }

    public function convertFromClientOrder(int $id): JsonResponse
    {
        $workOrder = $this->service->convertFromClientOrder($id);

        return ApiResponse::success('Work order generated successfully', new WorkOrderResource($workOrder), 201);
    }
}
