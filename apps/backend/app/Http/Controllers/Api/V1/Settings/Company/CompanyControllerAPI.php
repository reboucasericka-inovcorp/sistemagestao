<?php

namespace App\Http\Controllers\Api\V1\Settings\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\Company\StoreCompanyRequest;
use App\Http\Requests\Settings\Company\UpdateCompanyRequest;
use App\Http\Resources\Settings\Company\CompanyResource;
use App\Services\Settings\Company\CompanyService;
use DomainException;
use Illuminate\Http\JsonResponse;

class CompanyControllerAPI extends Controller
{
    public function __construct(
        protected CompanyService $service
    ) {
    }

    public function show(): JsonResponse
    {
        $company = $this->service->get();

        return response()->json([
            'message' => $company ? 'Company retrieved successfully' : 'Company not configured',
            'data' => $company ? new CompanyResource($company) : null,
        ], 200);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        try {
            $company = $this->service->create(
                $request->validated(),
                $request->file('logo')
            );
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
        }

        return response()->json([
            'message' => 'Company created successfully',
            'data' => new CompanyResource($company),
        ], 201);
    }

    public function update(UpdateCompanyRequest $request): JsonResponse
    {
        $company = $this->service->update(
            $request->validated(),
            $request->file('logo')
        );

        return response()->json([
            'message' => 'Company updated successfully',
            'data' => new CompanyResource($company),
        ], 200);
    }
}
