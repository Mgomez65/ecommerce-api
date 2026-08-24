<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->adminOnly($user);
    }

    public function view(User $user): Response
    {
        return $this->adminOnly($user);
    }

    public function create(User $user): Response
    {
        return $this->adminOnly($user);
    }

    public function update(User $user): Response
    {
        return $this->adminOnly($user);
    }

    public function delete(User $user): Response
    {
        return $this->adminOnly($user);
    }

    private function adminOnly(User $user): Response
    {
        return $user->role === User::ROLE_ADMIN
            ? Response::allow()
            : Response::deny('No tienes permisos para realizar esta acción.');
    }
}
