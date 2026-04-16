<?php

namespace App\Http\Controllers\Api\V1\Access\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Access\Users\StoreUserRequest;
use App\Http\Requests\Access\Users\UpdateUserRequest;
use App\Http\Resources\Access\Users\UserResource;
use App\Models\User;
use App\Services\Access\Users\UserService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class UserControllerAPI extends Controller
{
    public function __construct(
        protected UserService $service
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $users = $this->service->paginate($request->only([
            'search',
            'role',
            'page',
            'per_page',
            'sort',
            'direction',
            'active_only',
        ]));

        $serializedItems = UserResource::collection($users->items())->resolve($request);

        return response()->json([
            'message' => 'Users retrieved successfully',
            'data' => $serializedItems,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ], 200);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->service->create($request->validated());
            Password::sendResetLink([
                'email' => $user->email,
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
        }

        return response()->json([
            'message' => 'User created successfully',
            'data' => new UserResource($user),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->load('roles:id,name');

        return response()->json([
            'message' => 'User retrieved successfully',
            'data' => new UserResource($user),
        ], 200);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $updated = $this->service->update($user, $request->validated());
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'data' => new UserResource($updated),
        ], 200);
    }

    public function destroy(User $user): JsonResponse
    {
        $updated = $this->service->inactivate($user);

        return response()->json([
            'message' => 'User inactivated successfully',
            'data' => new UserResource($updated),
        ], 200);
    }
}
