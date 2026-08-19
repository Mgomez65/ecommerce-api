<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if (!in_array($user->role, [User::ROLE_ADMIN, User::ROLE_VENDEDOR])) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        $data = $request->validate([
            'image' => ['required', 'image', 'max:5120'],
            'alt' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $path = $request->file('image')->store('products', 'public');

        $image = $product->images()->create([
            'image' => $path,
            'alt' => $data['alt'] ?? null,
            'is_primary' => $data['is_primary'] ?? false,
        ]);

        return response()->json([
            'message' => 'Imagen agregada correctamente',
            'image' => $image,
            'url' => Storage::url($path),
        ], 201);
    }
}