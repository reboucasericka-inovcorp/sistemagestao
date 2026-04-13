<?php

namespace App\Http\Controllers\Api\V1\Settings\Logs;

use App\Http\Controllers\Controller;
use App\Http\Resources\Settings\Logs\LogResource;
use App\Services\Settings\Logs\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogControllerAPI extends Controller
{
    public function __construct(
        protected LogService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $logs = $this->service->paginate($request->only([
            'search',
            'user',
            'menu',
            'action',
            'date_from',
            'date_to',
            'page',
            'per_page',
        ]));
        $data = LogResource::collection($logs->items())->resolve($request);

        return response()->json([
            'message' => 'Activity logs retrieved successfully',
            'data' => $data,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ], 200);
    }
}
