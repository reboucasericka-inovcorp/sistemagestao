<?php


use App\Http\Controllers\Api\V1\Contacts\ContactControllerAPI;
use App\Http\Controllers\Api\V1\Calendar\CalendarEventControllerAPI;
use App\Http\Controllers\Api\V1\Access\Roles\RoleControllerAPI;
use App\Http\Controllers\Api\V1\Access\Users\UserControllerAPI;
use App\Http\Controllers\Api\V1\Finance\BankAccountControllerAPI;
use App\Http\Controllers\Api\V1\Finance\CustomerAccountControllerAPI;
use App\Http\Controllers\Api\V1\Finance\SupplierInvoiceControllerAPI;
use App\Http\Controllers\Api\V1\Orders\ClientOrderControllerAPI;
use App\Http\Controllers\Api\V1\Orders\SupplierOrderControllerAPI;
use App\Http\Controllers\Api\V1\Settings\Articles\ArticleControllerAPI;
use App\Http\Controllers\Api\V1\WorkOrders\WorkOrderControllerAPI;
use App\Http\Controllers\Api\V1\Settings\CalendarActions\CalendarActionControllerAPI;
use App\Http\Controllers\Api\V1\Entities\EntityControllerAPI;
use App\Http\Controllers\Api\V1\Entities\ViesController;
use App\Http\Controllers\Api\V1\DigitalArchive\DigitalFileControllerAPI;
use App\Http\Controllers\Api\V1\Settings\CalendarTypes\CalendarTypeControllerAPI;
use App\Http\Controllers\Api\V1\Settings\Company\CompanyControllerAPI;
use App\Http\Controllers\Api\V1\Settings\ContactFunctions\ContactFunctionControllerAPI;
use App\Http\Controllers\Api\V1\Settings\Countries\CountryControllerAPI;
use App\Http\Controllers\Api\V1\Settings\Logs\LogControllerAPI;
use App\Http\Controllers\Api\V1\Proposals\ProposalControllerAPI;
use App\Http\Controllers\Api\V1\Settings\Vat\VatControllerAPI;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

Route::prefix('v1')->group(function (): void {
    // Rotas públicas (sem autenticação)
    Route::get('company', [CompanyControllerAPI::class, 'show']);

    Route::post('/login', function (Request $request) {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Utilizador inativo.',
            ], 422);
        }

        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
        ]);
    });

    // Rotas protegidas (com autenticação)
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', function (Request $request) {
            $user = $request->user();

            return response()->json([
                'message' => 'Utilizador autenticado carregado com sucesso.',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
                ],
            ]);
        });

        Route::get('contacts', [ContactControllerAPI::class, 'index'])->middleware('permission:contacts.read');
        Route::get('contacts/{contact}', [ContactControllerAPI::class, 'show'])->middleware('permission:contacts.read');
        Route::post('contacts', [ContactControllerAPI::class, 'store'])->middleware('permission:contacts.create');
        Route::match(['put', 'patch'], 'contacts/{contact}', [ContactControllerAPI::class, 'update'])->middleware('permission:contacts.update');
        Route::delete('contacts/{contact}', [ContactControllerAPI::class, 'destroy'])->middleware('permission:contacts.delete');

        Route::apiResource('entities', EntityControllerAPI::class)
            ->middlewareFor(['index', 'show'], 'permission:entities.read')
            ->middlewareFor('store', 'permission:entities.create')
            ->middlewareFor('update', 'permission:entities.update')
            ->middlewareFor('destroy', 'permission:entities.delete');
        Route::get('vies/{nif}', [ViesController::class, 'show'])->middleware('permission:entities.read');

        Route::get('countries', [CountryControllerAPI::class, 'index'])->middleware('permission:countries.read');
        Route::get('countries/{country}', [CountryControllerAPI::class, 'show'])->middleware('permission:countries.read');
        Route::post('countries', [CountryControllerAPI::class, 'store'])->middleware('permission:countries.create');
        Route::match(['put', 'patch'], 'countries/{country}', [CountryControllerAPI::class, 'update'])->middleware('permission:countries.update');
        Route::delete('countries/{country}', [CountryControllerAPI::class, 'destroy'])->middleware('permission:countries.delete');

        Route::get('articles', [ArticleControllerAPI::class, 'index'])->middleware('permission:articles.read');
        Route::get('articles/{article}', [ArticleControllerAPI::class, 'show'])->middleware('permission:articles.read');
        Route::post('articles', [ArticleControllerAPI::class, 'store'])->middleware('permission:articles.create');
        Route::match(['put', 'patch'], 'articles/{article}', [ArticleControllerAPI::class, 'update'])->middleware('permission:articles.update');
        Route::delete('articles/{article}', [ArticleControllerAPI::class, 'destroy'])->middleware('permission:articles.delete');

        Route::get('calendar-types', [CalendarTypeControllerAPI::class, 'index'])->middleware('permission:calendar-types.read');
        Route::get('calendar-types/{calendar_type}', [CalendarTypeControllerAPI::class, 'show'])->middleware('permission:calendar-types.read');
        Route::post('calendar-types', [CalendarTypeControllerAPI::class, 'store'])->middleware('permission:calendar-types.create');
        Route::match(['put', 'patch'], 'calendar-types/{calendar_type}', [CalendarTypeControllerAPI::class, 'update'])->middleware('permission:calendar-types.update');
        Route::delete('calendar-types/{calendar_type}', [CalendarTypeControllerAPI::class, 'destroy'])->middleware('permission:calendar-types.delete');

        Route::get('calendar-actions', [CalendarActionControllerAPI::class, 'index'])->middleware('permission:calendar-actions.read');
        Route::get('calendar-actions/{calendar_action}', [CalendarActionControllerAPI::class, 'show'])->middleware('permission:calendar-actions.read');
        Route::post('calendar-actions', [CalendarActionControllerAPI::class, 'store'])->middleware('permission:calendar-actions.create');
        Route::match(['put', 'patch'], 'calendar-actions/{calendar_action}', [CalendarActionControllerAPI::class, 'update'])->middleware('permission:calendar-actions.update');
        Route::delete('calendar-actions/{calendar_action}', [CalendarActionControllerAPI::class, 'destroy'])->middleware('permission:calendar-actions.delete');

        Route::get('calendar-events', [CalendarEventControllerAPI::class, 'index'])->middleware('permission:calendar-events.read');
        Route::post('calendar-events', [CalendarEventControllerAPI::class, 'store'])->middleware('permission:calendar-events.create');
        Route::match(['put', 'patch'], 'calendar-events/{event}', [CalendarEventControllerAPI::class, 'update'])->middleware('permission:calendar-events.update');
        Route::delete('calendar-events/{event}', [CalendarEventControllerAPI::class, 'destroy'])->middleware('permission:calendar-events.delete');

        Route::get('contact-functions', [ContactFunctionControllerAPI::class, 'index'])->middleware('permission:contact-functions.read');
        Route::get('contact-functions/{contact_function}', [ContactFunctionControllerAPI::class, 'show'])->middleware('permission:contact-functions.read');
        Route::post('contact-functions', [ContactFunctionControllerAPI::class, 'store'])->middleware('permission:contact-functions.create');
        Route::match(['put', 'patch'], 'contact-functions/{contact_function}', [ContactFunctionControllerAPI::class, 'update'])->middleware('permission:contact-functions.update');
        Route::delete('contact-functions/{contact_function}', [ContactFunctionControllerAPI::class, 'destroy'])->middleware('permission:contact-functions.delete');

        Route::get('vat', [VatControllerAPI::class, 'index'])->middleware('permission:vat.read');
        Route::get('vat/{vat}', [VatControllerAPI::class, 'show'])->middleware('permission:vat.read');
        Route::post('vat', [VatControllerAPI::class, 'store'])->middleware('permission:vat.create');
        Route::match(['put', 'patch'], 'vat/{vat}', [VatControllerAPI::class, 'update'])->middleware('permission:vat.update');
        Route::delete('vat/{vat}', [VatControllerAPI::class, 'destroy'])->middleware('permission:vat.delete');

        Route::get('activity-logs', [LogControllerAPI::class, 'index'])->middleware('permission:logs.read');
        Route::put('company', [CompanyControllerAPI::class, 'update'])->middleware('permission:company.update');

        Route::get('users', [UserControllerAPI::class, 'index'])->middleware('permission:users.read');
        Route::get('users/{user}', [UserControllerAPI::class, 'show'])->middleware('permission:users.read');
        Route::post('users', [UserControllerAPI::class, 'store'])->middleware('permission:users.create');
        Route::match(['put', 'patch'], 'users/{user}', [UserControllerAPI::class, 'update'])->middleware('permission:users.update');
        Route::delete('users/{user}', [UserControllerAPI::class, 'destroy'])->middleware('permission:users.delete');

        Route::get('roles', [RoleControllerAPI::class, 'index'])->middleware('permission:roles.read');
        Route::get('roles/{role}', [RoleControllerAPI::class, 'show'])->middleware('permission:roles.read');
        Route::post('roles', [RoleControllerAPI::class, 'store'])->middleware('permission:roles.create');
        Route::match(['put', 'patch'], 'roles/{role}', [RoleControllerAPI::class, 'update'])->middleware('permission:roles.update');
        Route::delete('roles/{role}', [RoleControllerAPI::class, 'destroy'])->middleware('permission:roles.delete');
        Route::get('roles-permissions-catalog', [RoleControllerAPI::class, 'permissionsCatalog'])
            ->middleware('permission:roles.read');

        Route::get('proposals', [ProposalControllerAPI::class, 'index']);
        Route::get('proposals/{id}', [ProposalControllerAPI::class, 'show']);
        Route::post('proposals', [ProposalControllerAPI::class, 'store']);
        Route::put('proposals/{id}', [ProposalControllerAPI::class, 'update']);
        Route::post('proposals/{id}/convert', [ProposalControllerAPI::class, 'convert']);
        Route::get('proposals/{id}/pdf', [ProposalControllerAPI::class, 'pdf']);

        Route::get('client-orders', [ClientOrderControllerAPI::class, 'index']);
        Route::get('client-orders/{id}', [ClientOrderControllerAPI::class, 'show']);
        Route::post('client-orders', [ClientOrderControllerAPI::class, 'store']);
        Route::put('client-orders/{id}', [ClientOrderControllerAPI::class, 'update']);
        Route::post('client-orders/{id}/convert-suppliers', [ClientOrderControllerAPI::class, 'convert']);
        Route::get('client-orders/{id}/pdf', [ClientOrderControllerAPI::class, 'pdf']);

        Route::get('supplier-orders', [SupplierOrderControllerAPI::class, 'index']);
        Route::get('supplier-orders/{id}', [SupplierOrderControllerAPI::class, 'show']);
        Route::post('supplier-orders', [SupplierOrderControllerAPI::class, 'store']);
        Route::put('supplier-orders/{id}', [SupplierOrderControllerAPI::class, 'update']);

        Route::get('bank-accounts', [BankAccountControllerAPI::class, 'index'])->middleware('permission:bank-accounts.read');
        Route::get('bank-accounts/{id}', [BankAccountControllerAPI::class, 'show'])->middleware('permission:bank-accounts.read');
        Route::post('bank-accounts', [BankAccountControllerAPI::class, 'store'])->middleware('permission:bank-accounts.create');
        Route::put('bank-accounts/{id}', [BankAccountControllerAPI::class, 'update'])->middleware('permission:bank-accounts.update');
        Route::delete('bank-accounts/{id}', [BankAccountControllerAPI::class, 'destroy'])->middleware('permission:bank-accounts.delete');

        Route::get('customer-accounts', [CustomerAccountControllerAPI::class, 'index'])->middleware('permission:customer-accounts.read');
        Route::get('customer-accounts/{id}', [CustomerAccountControllerAPI::class, 'show'])->middleware('permission:customer-accounts.read');
        Route::get('customer-accounts/{id}/movements', [CustomerAccountControllerAPI::class, 'movements'])->middleware('permission:customer-accounts.read');
        Route::post('customer-accounts/{id}/movements', [CustomerAccountControllerAPI::class, 'storeMovement'])->middleware('permission:customer-accounts.create');

        Route::get('supplier-invoices', [SupplierInvoiceControllerAPI::class, 'index'])->middleware('permission:supplier-invoices.read');
        Route::get('supplier-invoices/{id}', [SupplierInvoiceControllerAPI::class, 'show'])->middleware('permission:supplier-invoices.read');
        Route::post('supplier-invoices', [SupplierInvoiceControllerAPI::class, 'store'])->middleware('permission:supplier-invoices.create');
        Route::put('supplier-invoices/{id}', [SupplierInvoiceControllerAPI::class, 'update'])->middleware('permission:supplier-invoices.update');
        Route::delete('supplier-invoices/{id}', [SupplierInvoiceControllerAPI::class, 'destroy'])->middleware('permission:supplier-invoices.delete');
        Route::get('supplier-invoices/{id}/files/{fileId}/download', [SupplierInvoiceControllerAPI::class, 'download'])->middleware('permission:supplier-invoices.read');

        Route::get('work-orders', [WorkOrderControllerAPI::class, 'index']);
        Route::get('work-orders/{id}', [WorkOrderControllerAPI::class, 'show']);
        Route::post('work-orders', [WorkOrderControllerAPI::class, 'store']);
        Route::put('work-orders/{id}', [WorkOrderControllerAPI::class, 'update']);
        Route::post('work-orders/from-client-order/{id}', [WorkOrderControllerAPI::class, 'convertFromClientOrder']);

        Route::get('digital-files', [DigitalFileControllerAPI::class, 'index'])->middleware('permission:digital-files.read');
        Route::post('digital-files', [DigitalFileControllerAPI::class, 'store'])->middleware('permission:digital-files.create');
        Route::get('digital-files/{digitalFile}/download', [DigitalFileControllerAPI::class, 'download'])->middleware('permission:digital-files.read');
        Route::delete('digital-files/{digitalFile}', [DigitalFileControllerAPI::class, 'destroy'])->middleware('permission:digital-files.delete');
    });
});
