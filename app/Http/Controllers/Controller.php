<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
}


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage: role:admin or role:admin,vendedor
     */
    public function handle(Request $request, Closure $next, $roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        $allowed = array_map('trim', explode(',', $roles));

        if (!in_array($user->role, $allowed)) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        return $next($request);
    }
}
