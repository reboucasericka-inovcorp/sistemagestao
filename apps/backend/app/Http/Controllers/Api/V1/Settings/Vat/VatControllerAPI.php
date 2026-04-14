<?php

namespace App\Http\Controllers\Api\V1\Settings\Vat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\Vat\StoreVatRequest;
use App\Http\Requests\Settings\Vat\UpdateVatRequest;
use App\Http\Resources\Settings\Vat\VatResource;
use App\Models\Settings\VatModel;
use App\Services\Settings\Vat\VatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VatControllerAPI extends Controller
{
    public function __construct(
        protected VatService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $vats = $this->service->paginate($request->only([
            'search',
            'page',
            'per_page',
            'sort',
            'direction',
            'active_only',
        ]));
        $serializedItems = VatResource::collection($vats->items())->resolve($request);

        return response()->json([
            'message' => 'VAT entries retrieved successfully',
            'data' => $serializedItems,
            'meta' => [
                'current_page' => $vats->currentPage(),
                'last_page' => $vats->lastPage(),
                'per_page' => $vats->perPage(),
                'total' => $vats->total(),
            ],
        ], 200);
    }

    public function store(StoreVatRequest $request): JsonResponse
    {
        $vat = $this->service->create($request->validated());

        return response()->json([
            'message' => 'VAT entry created successfully',
            'data' => new VatResource($vat),
        ], 201);
    }

    public function show(VatModel $vat): JsonResponse
    {
        return response()->json([
            'message' => 'VAT entry retrieved successfully',
            'data' => new VatResource($vat),
        ], 200);
    }

    public function update(UpdateVatRequest $request, VatModel $vat): JsonResponse
    {
        $updated = $this->service->update($vat, $request->validated());

        return response()->json([
            'message' => 'VAT entry updated successfully',
            'data' => new VatResource($updated),
        ], 200);
    }

    public function destroy(VatModel $vat): JsonResponse
    {
        $updated = $this->service->inactivate($vat);

        return response()->json([
            'message' => 'VAT entry inactivated successfully',
            'data' => new VatResource($updated),
        ], 200);
    }
}
