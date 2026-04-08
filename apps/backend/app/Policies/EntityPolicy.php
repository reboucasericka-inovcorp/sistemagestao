<?php

namespace App\Policies;

use App\Models\EntityModel;
use App\Models\User;

class EntityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('entities.view');
    }

    public function view(User $user, EntityModel $entity): bool
    {
        return $user->can('entities.view');
    }

    public function create(User $user): bool
    {
        return $user->can('entities.create');
    }

    public function update(User $user, EntityModel $entity): bool
    {
        return $user->can('entities.update');
    }

    public function delete(User $user, EntityModel $entity): bool
    {
        return $user->can('entities.delete');
    }
}
