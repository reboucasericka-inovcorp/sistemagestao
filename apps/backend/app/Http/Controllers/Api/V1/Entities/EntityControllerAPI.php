<?php

namespace App\Http\Controllers\Api\V1\Entities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\StoreEntityRequest;
use App\Http\Requests\Entities\UpdateEntityRequest;
use App\Http\Resources\Entities\EntityResource;
use App\Models\EntityModel;
use App\Services\Entities\EntityService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntityControllerAPI extends Controller
{
    public function __construct(
        protected EntityService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $entities = $this->service->paginate($request->only([
            'search',
            'type',
            'active_only',
            'page',
            'per_page',
            'sort',
            'direction',
        ]));
        $serializedItems = EntityResource::collection($entities->items())->resolve($request);

        return response()->json([
            'message' => 'Entities retrieved successfully',
            'data' => $serializedItems,
            'meta' => [
                'current_page' => $entities->currentPage(),
                'last_page' => $entities->lastPage(),
                'per_page' => $entities->perPage(),
                'total' => $entities->total(),
            ],
        ], 200);
    }

    public function store(StoreEntityRequest $request): JsonResponse
    {
        try {
            $entity = $this->service->create($request->validated());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $entity->load('country:id,name');

        return response()->json([
            'message' => 'Entity created successfully',
            'data' => new EntityResource($entity),
        ], 201);
    }

    public function show(EntityModel $entity): JsonResponse
    {
        $entity->load('country:id,name');

        return response()->json([
            'message' => 'Entity retrieved successfully',
            'data' => new EntityResource($entity),
        ], 200);
    }

    public function update(UpdateEntityRequest $request, EntityModel $entity): JsonResponse
    {
        try {
            $entity = $this->service->update($entity, $request->validated());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $entity->load('country:id,name');

        return response()->json([
            'message' => 'Entity updated successfully',
            'data' => new EntityResource($entity),
        ], 200);
    }

    public function destroy(EntityModel $entity): JsonResponse
    {
        $updated = $this->service->inactivate($entity);
        $updated->load('country:id,name');

        return response()->json([
            'message' => 'Entity inactivated successfully',
            'data' => new EntityResource($updated),
        ], 200);
    }
}
