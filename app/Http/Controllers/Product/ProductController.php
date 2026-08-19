<?php

namespace App\Http\Controllers\Product;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category', 'images')->get();

        return response()->json([
            'products' => $products
        ]);
    }

    public function store(StoreProductRequest $request)
    {
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
        $product = Product::findOrFail($id);

        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente'
        ]);
    }
}