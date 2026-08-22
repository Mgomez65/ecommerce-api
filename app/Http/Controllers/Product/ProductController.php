<?php

namespace App\Http\Controllers\Product;

use App\Models\Product;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\IndexProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;

class ProductController extends Controller
{
    public function index(IndexProductRequest $request)
    {
        $filters = $request->validated();

        $products = Product::query()
            ->with('category', 'images')
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query->where('name', 'like', '%' . $search . '%')
            )
            ->when(
                $filters['category_id'] ?? null,
                fn ($query, $categoryId) => $query->where('category_id', $categoryId)
            )
            ->when(
                $filters['min_price'] ?? null,
                fn ($query, $minPrice) => $query->where('price', '>=', $minPrice)
            )
            ->when(
                $filters['max_price'] ?? null,
                fn ($query, $maxPrice) => $query->where('price', '<=', $maxPrice)
            )
            ->when(
                array_key_exists('active', $filters),
                fn ($query) => $query->where('active', $request->boolean('active'))
            )
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json([
            'products' => $products
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if (!in_array($user->role, [User::ROLE_ADMIN, User::ROLE_VENDEDOR])) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        $data = $request->validated();

        $product = Product::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'stock' => $data['stock'],
            'stock_minimo' => $data['stock_minimo'],
            'category_id' => $data['category_id'],
            'active' => $data['active'],
        ]);

        return response()->json([
            'message' => 'Producto creado correctamente',
            'product' => $product
        ], 201);
    }

    public function show(string $id)
    {
        $product = Product::with('category')->findOrFail($id);

        return response()->json([
            'product' => $product
        ]);
    }

    public function update(UpdateProductRequest $request, string $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if (!in_array($user->role, [User::ROLE_ADMIN, User::ROLE_VENDEDOR])) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        $data = $request->validated();

        $product = Product::findOrFail($id);

        $product->update($data);

        return response()->json([
            'message' => 'Producto actualizado correctamente',
            'product' => $product
        ]);
    }

    public function destroy(string $id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if (!in_array($user->role, [User::ROLE_ADMIN, User::ROLE_VENDEDOR])) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        $product = Product::findOrFail($id);

        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente'
        ]);
    }
}