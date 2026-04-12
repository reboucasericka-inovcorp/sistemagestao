<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\StoreContactRequest;
use App\Http\Requests\Contact\UpdateContactRequest;
use App\Models\ContactModel;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactControllerAPI extends Controller
{
    public function __construct(
        protected ContactService $service
    ) {
        $this->authorizeResource(ContactModel::class, 'contact');
    }

    public function index(Request $request): mixed
    {
        $perPage = min($request->integer('per_page', 15), 100);

        return ContactModel::query()
            ->with(['entity', 'contactFunction'])
            ->when($request->filled('entity_id'), static fn ($query) => $query->where(
                'entity_id',
                $request->integer('entity_id')
            ))
            ->when($request->boolean('active_only'), static fn ($query) => $query->active())
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = '%'.addcslashes((string) $request->string('search'), '%_\\').'%';
                $query->where(static function ($q) use ($term): void {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = $this->service->create($request->validated());
        $contact->load(['entity', 'contactFunction']);

        return response()->json([
            'message' => 'Contact created successfully',
            'data' => $contact,
        ], 201);
    }

    public function show(ContactModel $contact): JsonResponse
    {
        $contact->load(['entity', 'contactFunction']);

        return response()->json([
            'message' => 'Contact retrieved successfully',
            'data' => $contact,
        ], 200);
    }

    public function update(UpdateContactRequest $request, ContactModel $contact): JsonResponse
    {
        $contact = $this->service->update($contact, $request->validated());
        $contact->load(['entity', 'contactFunction']);

        return response()->json([
            'message' => 'Contact updated successfully',
            'data' => $contact,
        ], 200);
    }

    public function destroy(ContactModel $contact): JsonResponse
    {
        if (! $contact->is_active) {
            return response()->json([
                'message' => 'Contact already inactive',
                'data' => $contact,
            ], 200);
        }

        $contact->update(['is_active' => false]);

        return response()->json([
            'message' => 'Contact inactivated successfully',
            'data' => $contact->refresh(),
        ], 200);
    }
}
