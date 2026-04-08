<?php

use App\Http\Controllers\Api\V1\EntityControllerAPI;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('entities', EntityControllerAPI::class)->only(['index', 'store']);
});
