<?php

use App\Http\Controllers\Api\V1\ContactControllerAPI;
use App\Http\Controllers\Api\V1\EntityControllerAPI;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('entities', EntityControllerAPI::class);
    Route::apiResource('contacts', ContactControllerAPI::class);
});
