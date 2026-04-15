<?php

namespace App\Http\Controllers\Api\V1\Entities;

use App\Http\Controllers\Controller;
use App\Services\ViesService;
use Illuminate\Http\JsonResponse;

class ViesController extends Controller
{
    public function __construct(
        private readonly ViesService $viesService
    ) {
    }

    public function show(string $nif): JsonResponse
    {
        $result = $this->viesService->validateNif($nif);

        return response()->json([
            'message' => 'VIES validation completed successfully',
            'data' => $result,
        ]);
    }
}
