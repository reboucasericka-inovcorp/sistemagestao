<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreSupplierOrderRequest;
use App\Http\Requests\Orders\UpdateSupplierOrderRequest;
use App\Http\Resources\Orders\SupplierOrderResource;
use App\Services\Orders\SupplierOrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierOrderControllerAPI extends Controller
{
    public function __construct(
        protected SupplierOrderService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->service->getAll($request->only([
            'search',
            'status',
            'page',
            'per_page',
            'sort',
            'direction',
        ]));
        $serializedItems = SupplierOrderResource::collection($orders->items())->resolve($request);

        return ApiResponse::success('Supplier orders retrieved successfully', $serializedItems, 200, [
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->service->getById($id);

        return ApiResponse::success('Supplier order retrieved successfully', new SupplierOrderResource($order));
    }

    public function store(StoreSupplierOrderRequest $request): JsonResponse
    {
        $order = $this->service->create($request->validated());

        return ApiResponse::success('Supplier order created successfully', new SupplierOrderResource($order), 201);
    }

    public function update(UpdateSupplierOrderRequest $request, int $id): JsonResponse
    {
        $order = $this->service->update($id, $request->validated());

        return ApiResponse::success('Supplier order updated successfully', new SupplierOrderResource($order));
    }
}
