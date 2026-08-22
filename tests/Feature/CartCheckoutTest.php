<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Orders;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function validShippingAddress(): array
    {
        return [
            'shipping_address' => [
                'recipient_name' => 'Juan Pérez',
                'phone' => '+54 9 11 1234-5678',
                'address' => 'Av. Siempre Viva',
                'number' => '742',
                'city' => 'Springfield',
                'state' => 'Buenos Aires',
                'postal_code' => '1000',
                'notes' => 'Tocar timbre 2B',
            ],
        ];
    }

    public function test_checkout_successful()
    {
        config(['services.mercadopago.access_token' => 'TEST-ACCESS-TOKEN']);
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-123',
                'init_point' => 'https://mercadopago.com/checkout/pref-123',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com/checkout/pref-123',
            ], 201),
        ]);

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

        // Checkout: creates a pending order and a Mercado Pago preference.
        // Stock is intentionally NOT decremented yet — that only happens
        // once the payment webhook confirms the payment as approved.
        $checkout = $this->postJson('/api/cart/checkout', $this->validShippingAddress());
        $checkout->assertStatus(201);
        $checkout->assertJsonStructure(['message', 'order', 'payment' => ['checkout_url', 'preference_id']]);

        $this->assertDatabaseHas('order_shipping_addresses', [
            'order_id' => $checkout->json('order.id'),
            'recipient_name' => 'Juan Pérez',
            'city' => 'Springfield',
        ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => Orders::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 5,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $checkout->json('order.id'),
            'status' => 'pending',
            'preference_id' => 'pref-123',
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

        $this->postJson('/api/cart/checkout', $this->validShippingAddress())->assertStatus(422);
    }
}
