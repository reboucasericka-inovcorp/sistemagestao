<?php

namespace App\Http\Controllers\Api\V1\Settings\ContactFunctions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ContactFunctions\StoreContactFunctionRequest;
use App\Http\Requests\Settings\ContactFunctions\UpdateContactFunctionRequest;
use App\Http\Resources\Settings\ContactFunctions\ContactFunctionResource;
use App\Models\Settings\ContactFunctionModel;
use App\Services\Settings\ContactFunctions\ContactFunctionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactFunctionControllerAPI extends Controller
{
    public function __construct(
        protected ContactFunctionService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $functions = $this->service->paginate($request->only([
            'search',
            'page',
            'per_page',
            'sort',
            'direction',
            'active_only',
        ]));

        return response()->json([
            'message' => 'Contact functions retrieved successfully',
            'data' => ContactFunctionResource::collection($functions->items()),
            'meta' => [
                'current_page' => $functions->currentPage(),
                'last_page' => $functions->lastPage(),
                'per_page' => $functions->perPage(),
                'total' => $functions->total(),
            ],
        ]);
    }

    public function store(StoreContactFunctionRequest $request): JsonResponse
    {
        $contactFunction = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Contact function created successfully',
            'data' => new ContactFunctionResource($contactFunction),
        ], 201);
    }

    public function show(ContactFunctionModel $contact_function): JsonResponse
    {
        return response()->json([
            'message' => 'Contact function retrieved successfully',
            'data' => new ContactFunctionResource($contact_function),
        ], 200);
    }

    public function update(UpdateContactFunctionRequest $request, ContactFunctionModel $contact_function): JsonResponse
    {
        $updated = $this->service->update($contact_function, $request->validated());

        return response()->json([
            'message' => 'Contact function updated successfully',
            'data' => new ContactFunctionResource($updated),
        ], 200);
    }

    public function destroy(ContactFunctionModel $contact_function): JsonResponse
    {
        $updated = $this->service->inactivate($contact_function);

        return response()->json([
            'message' => 'Contact function inactivated successfully',
            'data' => new ContactFunctionResource($updated),
        ], 200);
    }
}
