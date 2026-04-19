<?php

namespace App\Http\Controllers\Api\V1\External;

use App\Http\Controllers\Controller;
use App\Services\External\ViesService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ViesControllerAPI extends Controller
{
    public function __construct(
        private readonly ViesService $viesService
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $nif = strtoupper(trim((string) $request->query('nif', '')));
        $sanitized = preg_replace('/[^A-Z0-9]/', '', $nif) ?? '';

        $countryCode = 'PT';
        $vatNumber = $sanitized;

        if (preg_match('/^[A-Z]{2}/', $sanitized) === 1) {
            $countryCode = substr($sanitized, 0, 2);
            $vatNumber = substr($sanitized, 2);
        }

        $result = $this->viesService->validate($countryCode, $vatNumber);

        return ApiResponse::success('VIES validation', $result);
    }
}

