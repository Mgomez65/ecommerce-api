<?php

namespace App\Policies;

use App\Models\Orders;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrdersPolicy
{
    /**
     * View a single order: staff can view any order, everyone else only
     * their own. Listing ("viewAny") has no gate — OrderController scopes
     * the query itself (all orders for staff, own orders otherwise).
     */
    public function view(User $user, Orders $order): Response
    {
        return $this->isStaff($user) || $order->user_id === $user->id
            ? Response::allow()
            : Response::deny('No tienes permisos para ver este pedido.');
    }

    public function cancel(User $user, Orders $order): Response
    {
        return $order->user_id === $user->id
            ? Response::allow()
            : Response::deny('No tienes permisos para cancelar este pedido.');
    }

    public function pay(User $user, Orders $order): Response
    {
        return $order->user_id === $user->id
            ? Response::allow()
            : Response::deny('No tienes permisos para pagar este pedido.');
    }

    public function updateStatus(User $user): Response
    {
        return $this->isStaff($user)
            ? Response::allow()
            : Response::deny('No tienes permisos para realizar esta acción.');
    }

    public function isStaff(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_VENDEDOR], true);
    }
}
