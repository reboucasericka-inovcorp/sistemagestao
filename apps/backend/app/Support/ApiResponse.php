<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(string $message, mixed $data = null, int $status = 200, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'message' => $message,
            'data' => $data,
        ], $extra), $status);
    }
}
