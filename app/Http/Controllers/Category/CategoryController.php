<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;

class CategoryController extends Controller
{
  public function index()
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->get();

        return response()->json([
            'categories' => $categories
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();

        $category = Category::create([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null
        ]);

        return response()->json([
            'message' => 'Categoría creada correctamente',
            'category' => $category
        ], 201);
    }

    public function show(string $id)
    {
        $category = Category::findOrFail($id);

        return response()->json([
            'category' => $category
        ]);
    }

    public function update(UpdateCategoryRequest $request, string $id)
    {
        $data = $request->validated();

        $category = Category::findOrFail($id);

        $category->update([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null
        ]);

        return response()->json([
            'message' => 'Categoría actualizada correctamente',
            'category' => $category
        ]);
    }

    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'Categoría eliminada correctamente'
        ]);
    }
}