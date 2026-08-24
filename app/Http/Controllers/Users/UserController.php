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
        $this->authorize('viewAny', User::class);

        return response()->json([
            'users' => User::all()
        ]);
    }



    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

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
        $this->authorize('view', $user);

        return response()->json([
            'user'=>$user
        ]);
    }





    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

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
        $this->authorize('delete', $user);

        $user->delete();


        return response()->json([
            'message'=>'Usuario eliminado'
        ]);
    }
}
