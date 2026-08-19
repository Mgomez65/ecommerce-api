<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{

    public function index()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if ($user->role !== User::ROLE_ADMIN) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        return response()->json([
            'users' => User::all()
        ]);
    }



    public function store(StoreUserRequest $request)
    {
        $auth = auth()->user();
        if (!$auth) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if ($auth->role !== User::ROLE_ADMIN) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'role' => $request->input('role', User::ROLE_CLIENTE),
        ]);


        return response()->json([
            'message'=>'Usuario creado correctamente',
            'user'=>$user
        ],201);
    }




    public function show(User $user)
    {
        $auth = auth()->user();
        if (!$auth) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if ($auth->role !== User::ROLE_ADMIN) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        return response()->json([
            'user'=>$user
        ]);
    }





    public function update(UpdateUserRequest $request, User $user)
    {
        $auth = auth()->user();
        if (!$auth) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if ($auth->role !== User::ROLE_ADMIN) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        $data = $request->validated();


        if(isset($data['password'])){
            $data['password'] = Hash::make($data['password']);
        }


        $user->update($data);



        return response()->json([
            'message'=>'Usuario actualizado',
            'user'=>$user
        ]);
    }





    public function destroy(User $user)
    {
        $auth = auth()->user();
        if (!$auth) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if ($auth->role !== User::ROLE_ADMIN) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        $user->delete();


        return response()->json([
            'message'=>'Usuario eliminado'
        ]);
    }
}