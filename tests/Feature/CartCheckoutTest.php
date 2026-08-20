<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Orders;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_successful()
    {
        $user = User::factory()->create();

        // Ensure category exists
                \DB::table('categories')->insert(['id' => 1, 'name' => 'cat']);

                $product = Product::create([
                    'name' => 'Producto 1',
                    'price' => 10.00,
                    'stock' => 5,
                    'stock_minimo' => 0,
                    'description' => 'Desc',
                    'active' => true,
                    'category_id' => 1,
                ]);

        $this->actingAs($user, 'sanctum');

        // Add item to cart
        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(201);

        // Checkout
        $checkout = $this->postJson('/api/cart/checkout');
        $checkout->assertStatus(201);
        $checkout->assertJsonStructure(['message', 'order']);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3,
        ]);
    }

    public function test_checkout_insufficient_stock()
    {
        $user = User::factory()->create();

        // Ensure category exists
                \DB::table('categories')->insert(['id' => 1, 'name' => 'cat']);

                $product = Product::create([
                    'name' => 'Producto 2',
                    'price' => 5.00,
                    'stock' => 1,
                    'stock_minimo' => 0,
                    'description' => 'Desc',
                    'active' => true,
                    'category_id' => 1,
                ]);

        $this->actingAs($user, 'sanctum');

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertStatus(422);

        // Add 1 unit then try to increase stock beyond
        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertStatus(201);

        // Manually set cart item to 2 to simulate race (this should be prevented but we test checkout fails)
        $cart = $user->cart()->firstOrCreate([]);
        $item = $cart->items()->where('product_id', $product->id)->first();
        $item->update(['quantity' => 2]);

        $this->postJson('/api/cart/checkout')->assertStatus(422);
    }
}
