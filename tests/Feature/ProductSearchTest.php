<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $attributes = []): Product
    {
        $category = $attributes['category_id'] ?? null;

        if (!$category) {
            $category = Category::create(['name' => 'Cat ' . uniqid()])->id;
        }

        return Product::create(array_merge([
            'name' => 'Producto ' . uniqid(),
            'description' => 'desc',
            'price' => 10,
            'stock' => 5,
            'stock_minimo' => 0,
            'active' => true,
            'category_id' => $category,
        ], $attributes));
    }

    public function test_can_search_products_by_name()
    {
        $this->makeProduct(['name' => 'Silla de madera']);
        $this->makeProduct(['name' => 'Mesa de vidrio']);

        $response = $this->getJson('/api/products?search=silla');

        $response->assertStatus(200);
        $names = collect($response->json('products.data'))->pluck('name');
        $this->assertTrue($names->contains('Silla de madera'));
        $this->assertFalse($names->contains('Mesa de vidrio'));
    }

    public function test_can_filter_products_by_category()
    {
        $categoryA = Category::create(['name' => 'Categoria A']);
        $categoryB = Category::create(['name' => 'Categoria B']);

        $this->makeProduct(['name' => 'Producto A', 'category_id' => $categoryA->id]);
        $this->makeProduct(['name' => 'Producto B', 'category_id' => $categoryB->id]);

        $response = $this->getJson("/api/products?category_id={$categoryA->id}");

        $response->assertStatus(200);
        $products = collect($response->json('products.data'));
        $this->assertTrue($products->every(fn ($p) => $p['category_id'] === $categoryA->id));
        $this->assertCount(1, $products);
    }

    public function test_can_filter_products_by_price_range()
    {
        $this->makeProduct(['name' => 'Barato', 'price' => 5]);
        $this->makeProduct(['name' => 'Medio', 'price' => 50]);
        $this->makeProduct(['name' => 'Caro', 'price' => 500]);

        $response = $this->getJson('/api/products?min_price=10&max_price=100');

        $response->assertStatus(200);
        $names = collect($response->json('products.data'))->pluck('name');
        $this->assertTrue($names->contains('Medio'));
        $this->assertFalse($names->contains('Barato'));
        $this->assertFalse($names->contains('Caro'));
    }

    public function test_can_filter_products_by_active_status()
    {
        $this->makeProduct(['name' => 'Activo', 'active' => true]);
        $this->makeProduct(['name' => 'Inactivo', 'active' => false]);

        $response = $this->getJson('/api/products?active=0');

        $response->assertStatus(200);
        $names = collect($response->json('products.data'))->pluck('name');
        $this->assertTrue($names->contains('Inactivo'));
        $this->assertFalse($names->contains('Activo'));
    }

    public function test_index_without_filters_returns_all_products_paginated()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->makeProduct();
        }

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'products' => ['data', 'current_page', 'per_page', 'total', 'last_page'],
        ]);
        $this->assertSame(3, $response->json('products.total'));
    }

    public function test_pagination_maintains_filters_across_pages()
    {
        $category = Category::create(['name' => 'Filtrada']);
        $other = Category::create(['name' => 'Otra']);

        for ($i = 0; $i < 5; $i++) {
            $this->makeProduct(['name' => "Filtrado {$i}", 'category_id' => $category->id]);
        }
        $this->makeProduct(['name' => 'No filtrado', 'category_id' => $other->id]);

        $response = $this->getJson("/api/products?category_id={$category->id}&per_page=2");

        $response->assertStatus(200);
        $this->assertSame(5, $response->json('products.total'));
        $this->assertCount(2, $response->json('products.data'));

        $nextPageUrl = $response->json('products.next_page_url');
        $this->assertNotNull($nextPageUrl);
        $this->assertStringContainsString("category_id={$category->id}", $nextPageUrl);

        $secondPage = $this->getJson($nextPageUrl);
        $secondPage->assertStatus(200);
        $secondPageCategories = collect($secondPage->json('products.data'))->pluck('category_id');
        $this->assertTrue($secondPageCategories->every(fn ($id) => $id === $category->id));
    }

    public function test_invalid_price_range_returns_validation_error()
    {
        $response = $this->getJson('/api/products?min_price=100&max_price=10');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('max_price');
    }

    public function test_invalid_category_id_returns_validation_error()
    {
        $response = $this->getJson('/api/products?category_id=999999');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('category_id');
    }

    public function test_per_page_above_limit_returns_validation_error()
    {
        $response = $this->getJson('/api/products?per_page=500');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('per_page');
    }
}
