<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCustomerAccountMovementRequest;
use App\Http\Resources\Finance\CustomerAccountMovementResource;
use App\Http\Resources\Finance\CustomerAccountResource;
use App\Services\Finance\CustomerAccountService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAccountControllerAPI extends Controller
{
    public function __construct(
        protected CustomerAccountService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $accounts = $this->service->getAll($request->only([
            'search',
            'page',
            'per_page',
            'sort',
            'direction',
        ]));

        $serializedItems = CustomerAccountResource::collection($accounts->items())->resolve($request);

        return ApiResponse::success('Customer accounts retrieved successfully', $serializedItems, 200, [
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $account = $this->service->getById($id);

        return ApiResponse::success('Customer account retrieved successfully', new CustomerAccountResource($account));
    }

    public function movements(Request $request, int $entityId): JsonResponse
    {
        $movements = $this->service->listMovements($entityId, $request->only(['page', 'per_page']));
        $serializedItems = CustomerAccountMovementResource::collection($movements->items())->resolve($request);

        return ApiResponse::success('Customer account movements retrieved successfully', $serializedItems, 200, [
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ],
        ]);
    }

    public function storeMovement(StoreCustomerAccountMovementRequest $request, int $entityId): JsonResponse
    {
        $account = $this->service->addMovement($entityId, $request->validated());

        return ApiResponse::success('Customer account movement created successfully', new CustomerAccountResource($account), 201);
    }
}
