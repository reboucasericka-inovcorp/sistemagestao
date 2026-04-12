<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entity\StoreEntityRequest;
use App\Http\Requests\Entity\UpdateEntityRequest;
use App\Models\EntityModel;
use App\Services\EntityService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntityControllerAPI extends Controller
{
    public function __construct(
        protected EntityService $service
    ) {
        $this->authorizeResource(EntityModel::class, 'entity');
    }

    public function index(Request $request): mixed
    {
        $perPage = min($request->integer('per_page', 15), 100);

        return EntityModel::query()
            ->when(
                $request->boolean('clients') && ! $request->boolean('suppliers'),
                static fn ($query) => $query->clients()
            )
            ->when(
                $request->boolean('suppliers') && ! $request->boolean('clients'),
                static fn ($query) => $query->suppliers()
            )
            ->when(
                $request->boolean('active_only'),
                static fn ($query) => $query->active()
            )
            ->latest()
            ->paginate($perPage);
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

        return response()->json([
            'message' => 'Entity created successfully',
            'data' => $entity,
        ], 201);
    }

    public function show(EntityModel $entity): JsonResponse
    {
        return response()->json([
            'message' => 'Entity retrieved successfully',
            'data' => $entity,
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

        return response()->json([
            'message' => 'Entity updated successfully',
            'data' => $entity,
        ], 200);
    }

    public function destroy(EntityModel $entity): JsonResponse
    {
        if (! $entity->is_active) {
            return response()->json([
                'message' => 'Entity already inactive',
                'data' => $entity,
            ], 200);
        }

        $entity->update(['is_active' => false]);

        return response()->json([
            'message' => 'Entity inactivated successfully',
            'data' => $entity->refresh(),
        ], 200);
    }
}
