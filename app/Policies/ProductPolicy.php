<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    public function create(User $user): Response
    {
        return $this->staffOnly($user);
    }

    public function update(User $user): Response
    {
        return $this->staffOnly($user);
    }

    public function delete(User $user): Response
    {
        return $this->staffOnly($user);
    }

    public function addImage(User $user): Response
    {
        return $this->staffOnly($user);
    }

    private function staffOnly(User $user): Response
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_VENDEDOR], true)
            ? Response::allow()
            : Response::deny('No tienes permisos para realizar esta acción.');
    }
}
