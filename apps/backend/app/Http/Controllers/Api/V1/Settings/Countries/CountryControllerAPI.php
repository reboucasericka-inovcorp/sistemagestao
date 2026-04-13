<?php

namespace App\Http\Controllers\Api\V1\Settings\Countries;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\Countries\StoreCountryRequest;
use App\Http\Requests\Settings\Countries\UpdateCountryRequest;
use App\Http\Resources\Settings\Countries\CountryResource;
use App\Models\Settings\CountryModel;
use App\Services\Settings\Countries\CountryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryControllerAPI extends Controller
{
    public function __construct(
        protected CountryService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $countries = $this->service->paginate($request->only([
            'search',
            'page',
            'per_page',
            'sort',
            'direction',
            'active_only',
        ]));


        return response()->json([
            'message' => 'Countries retrieved successfully',
            'data' => CountryResource::collection($countries->items()),
            'meta' => [
                'current_page' => $countries->currentPage(),
                'last_page' => $countries->lastPage(),
                'per_page' => $countries->perPage(),
                'total' => $countries->total(),
            ],
        ]);
    }

    public function store(StoreCountryRequest $request): JsonResponse
    {
        $country = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Country created successfully',
            'data' => new CountryResource($country),
        ], 201);
    }

    public function show(CountryModel $country): JsonResponse
    {
        return response()->json([
            'message' => 'Country retrieved successfully',
            'data' => new CountryResource($country),
        ], 200);
    }

    public function update(UpdateCountryRequest $request, CountryModel $country): JsonResponse
    {
        $updated = $this->service->update($country, $request->validated());

        return response()->json([
            'message' => 'Country updated successfully',
            'data' => new CountryResource($updated),
        ], 200);
    }

    public function destroy(CountryModel $country): JsonResponse
    {
        $updated = $this->service->inactivate($country);

        return response()->json([
            'message' => 'Country inactivated successfully',
            'data' => new CountryResource($updated),
        ], 200);
    }
}
