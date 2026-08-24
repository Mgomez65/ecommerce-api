<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class CategoryPolicy
{
    public function create(User $user): Response
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_VENDEDOR], true)
            ? Response::allow()
            : Response::deny('No tienes permisos para realizar esta acción.');
    }

    public function update(User $user): Response
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_VENDEDOR], true)
            ? Response::allow()
            : Response::deny('No tienes permisos para realizar esta acción.');
    }

    public function delete(User $user): Response
    {
        return $user->role === User::ROLE_ADMIN
            ? Response::allow()
            : Response::deny('No tienes permisos para realizar esta acción.');
    }
}
