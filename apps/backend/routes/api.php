<?php


use App\Http\Controllers\Api\V1\Contacts\ContactControllerAPI;
use App\Http\Controllers\Api\V1\Settings\Articles\ArticleControllerAPI;
use App\Http\Controllers\Api\V1\Settings\CalendarActions\CalendarActionControllerAPI;
use App\Http\Controllers\Api\V1\Entities\EntityControllerAPI;
use App\Http\Controllers\Api\V1\Settings\CalendarTypes\CalendarTypeControllerAPI;
use App\Http\Controllers\Api\V1\Settings\Company\CompanyControllerAPI;
use App\Http\Controllers\Api\V1\Settings\ContactFunctions\ContactFunctionControllerAPI;
use App\Http\Controllers\Api\V1\Settings\Countries\CountryControllerAPI;
use App\Http\Controllers\Api\V1\Settings\Logs\LogControllerAPI;
use App\Http\Controllers\Api\V1\Settings\Vat\VatControllerAPI;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

Route::prefix('v1')->group(function (): void {
    // Rotas públicas (sem autenticação)
    Route::post('/login', function (Request $request) {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
        ]);
    });

    // Rotas protegidas (com autenticação)
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::apiResource('contacts', ContactControllerAPI::class);
        Route::apiResource('entities', EntityControllerAPI::class);
        Route::apiResource('countries', CountryControllerAPI::class);
        Route::apiResource('articles', ArticleControllerAPI::class);
        Route::apiResource('calendar-types', CalendarTypeControllerAPI::class);
        Route::apiResource('calendar-actions', CalendarActionControllerAPI::class);
        Route::apiResource('contact-functions', ContactFunctionControllerAPI::class);
        Route::apiResource('vat', VatControllerAPI::class);
        Route::get('activity-logs', [LogControllerAPI::class, 'index']);
        Route::get('company', [CompanyControllerAPI::class, 'show']);
        Route::post('company', [CompanyControllerAPI::class, 'store']);
        Route::put('company', [CompanyControllerAPI::class, 'update']);
    });
});
