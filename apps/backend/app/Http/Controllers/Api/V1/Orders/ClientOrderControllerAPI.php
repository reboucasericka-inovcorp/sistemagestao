<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreClientOrderRequest;
use App\Http\Requests\Orders\UpdateClientOrderRequest;
use App\Http\Resources\Orders\ClientOrderResource;
use App\Services\Orders\ClientOrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientOrderControllerAPI extends Controller
{
    public function __construct(
        protected ClientOrderService $service
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
        $serializedItems = ClientOrderResource::collection($orders->items())->resolve($request);

        return ApiResponse::success('Client orders retrieved successfully', $serializedItems, 200, [
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

        return ApiResponse::success('Client order retrieved successfully', new ClientOrderResource($order));
    }

    public function store(StoreClientOrderRequest $request): JsonResponse
    {
        $order = $this->service->create($request->validated());

        return ApiResponse::success('Client order created successfully', new ClientOrderResource($order), 201);
    }

    public function update(UpdateClientOrderRequest $request, int $id): JsonResponse
    {
        $order = $this->service->update($id, $request->validated());

        return ApiResponse::success('Client order updated successfully', new ClientOrderResource($order));
    }

    public function convert(int $id): JsonResponse
    {
        $orders = $this->service->convertToSupplierOrders($id);

        return ApiResponse::success('Supplier orders generated successfully', $orders);
    }

    public function pdf(int $id)
    {
        $pdf = $this->service->generatePdf($id);

        return $pdf->download("client_order_{$id}.pdf");
    }
}
