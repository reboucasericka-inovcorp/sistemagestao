<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreBankAccountRequest;
use App\Http\Requests\Finance\UpdateBankAccountRequest;
use App\Http\Resources\Finance\BankAccountResource;
use App\Services\Finance\BankAccountService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountControllerAPI extends Controller
{
    public function __construct(
        protected BankAccountService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $accounts = $this->service->getAll($request->only([
            'search',
            'is_active',
            'page',
            'per_page',
            'sort',
            'direction',
        ]));

        $serializedItems = BankAccountResource::collection($accounts->items())->resolve($request);

        return ApiResponse::success('Bank accounts retrieved successfully', $serializedItems, 200, [
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
        ]);
    }

    public function store(StoreBankAccountRequest $request): JsonResponse
    {
        $account = $this->service->create($request->validated());

        return ApiResponse::success('Bank account created successfully', new BankAccountResource($account), 201);
    }

    public function show(int $id): JsonResponse
    {
        $account = $this->service->getById($id);

        return ApiResponse::success('Bank account retrieved successfully', new BankAccountResource($account));
    }

    public function update(UpdateBankAccountRequest $request, int $id): JsonResponse
    {
        $account = $this->service->update($id, $request->validated());

        return ApiResponse::success('Bank account updated successfully', new BankAccountResource($account));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return ApiResponse::success('Bank account deleted successfully', null);
    }
}
