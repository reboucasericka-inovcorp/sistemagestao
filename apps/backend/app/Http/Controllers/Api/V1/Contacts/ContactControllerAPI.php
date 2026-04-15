<?php

namespace App\Http\Controllers\Api\V1\Contacts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contacts\StoreContactRequest;
use App\Http\Requests\Contacts\UpdateContactRequest;
use App\Http\Resources\Contacts\ContactResource;
use App\Models\ContactModel;
use App\Services\Contacts\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactControllerAPI extends Controller
{
    public function __construct(
        protected ContactService $service
    ) {
        $this->authorizeResource(ContactModel::class, 'contact');
    }

    public function index(Request $request): JsonResponse
    {
        $contacts = $this->service->paginate($request->only([
            'search',
            'entity_id',
            'is_active',
            'page',
            'per_page',
        ]));

        return response()->json([
            'message' => 'Contacts retrieved successfully',
            'data' => ContactResource::collection($contacts->items()),
            'meta' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ], 200);
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = $this->service->create($request->validated());
        $contact->load(['entity:id,name', 'contactFunction:id,name']);

        return response()->json([
            'message' => 'Contact created successfully',
            'data' => new ContactResource($contact),
        ], 201);
    }

    public function show(ContactModel $contact): JsonResponse
    {
        $contact->load(['entity:id,name', 'contactFunction:id,name']);

        return response()->json([
            'message' => 'Contact retrieved successfully',
            'data' => new ContactResource($contact),
        ], 200);
    }

    public function update(UpdateContactRequest $request, ContactModel $contact): JsonResponse
    {
        $contact = $this->service->update($contact, $request->validated());
        $contact->load(['entity:id,name', 'contactFunction:id,name']);

        return response()->json([
            'message' => 'Contact updated successfully',
            'data' => new ContactResource($contact),
        ], 200);
    }

    public function destroy(ContactModel $contact): JsonResponse
    {
        $updated = $this->service->inactivate($contact);
        $updated->load(['entity:id,name', 'contactFunction:id,name']);

        return response()->json([
            'message' => 'Contact inactivated successfully',
            'data' => new ContactResource($updated),
        ], 200);
    }
}
