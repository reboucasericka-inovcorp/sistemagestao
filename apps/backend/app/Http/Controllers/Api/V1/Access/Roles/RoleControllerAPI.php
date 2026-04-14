<?php

namespace App\Http\Controllers\Api\V1\Access\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Access\Roles\StoreRoleRequest;
use App\Http\Requests\Access\Roles\UpdateRoleRequest;
use App\Http\Resources\Access\Roles\RoleResource;
use App\Models\Access\RoleModel;
use App\Services\Access\Roles\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleControllerAPI extends Controller
{
    public function __construct(
        protected RoleService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $roles = $this->service->paginate($request->only([
            'search',
            'page',
            'per_page',
            'sort',
            'direction',
            'active_only',
        ]));

        $serializedItems = RoleResource::collection($roles->items())->resolve($request);

        return response()->json([
            'message' => 'Roles retrieved successfully',
            'data' => $serializedItems,
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ],
        ], 200);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Role created successfully',
            'data' => new RoleResource($role),
        ], 201);
    }

    public function show(RoleModel $role): JsonResponse
    {
        $role->load('permissions:name')->loadCount('users');

        return response()->json([
            'message' => 'Role retrieved successfully',
            'data' => new RoleResource($role),
        ], 200);
    }

    public function update(UpdateRoleRequest $request, RoleModel $role): JsonResponse
    {
        $updated = $this->service->update($role, $request->validated());

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => new RoleResource($updated),
        ], 200);
    }

    public function destroy(RoleModel $role): JsonResponse
    {
        $updated = $this->service->inactivate($role);

        return response()->json([
            'message' => 'Role inactivated successfully',
            'data' => new RoleResource($updated),
        ], 200);
    }

    public function permissionsCatalog(): JsonResponse
    {
        return response()->json([
            'message' => 'Permissions catalog retrieved successfully',
            'data' => $this->service->permissionsCatalog(),
        ], 200);
    }
}
