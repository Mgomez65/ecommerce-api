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
        return response()->json([
            'users' => User::all()
        ]);
    }



    public function store(StoreUserRequest $request)
    {

        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password)
        ]);


        return response()->json([
            'message'=>'Usuario creado correctamente',
            'user'=>$user
        ],201);
    }




    public function show(User $user)
    {
        return response()->json([
            'user'=>$user
        ]);
    }





    public function update(UpdateUserRequest $request, User $user)
    {

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

        $user->delete();


        return response()->json([
            'message'=>'Usuario eliminado'
        ]);
    }
}