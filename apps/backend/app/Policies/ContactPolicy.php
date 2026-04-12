<?php

namespace App\Policies;

use App\Models\ContactModel;
use App\Models\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contacts.view');
    }

    public function view(User $user, ContactModel $contact): bool
    {
        return $user->can('contacts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('contacts.create');
    }

    public function update(User $user, ContactModel $contact): bool
    {
        return $user->can('contacts.update');
    }

    public function delete(User $user, ContactModel $contact): bool
    {
        return $user->can('contacts.delete');
    }
}
