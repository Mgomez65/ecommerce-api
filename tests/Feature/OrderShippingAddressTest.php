<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderShippingAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mercadopago.access_token' => 'TEST-ACCESS-TOKEN']);

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-999',
                'init_point' => 'https://mercadopago.com/checkout/pref-999',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com/checkout/pref-999',
            ], 201),
        ]);
    }

    private function setUpBuyerWithCartItem(User $user): Product
    {
        $category = Category::create(['name' => 'Cat ' . uniqid()]);

        $product = Product::create([
            'name' => 'Producto envío',
            'description' => 'desc',
            'price' => 15,
            'stock' => 10,
            'stock_minimo' => 0,
            'active' => true,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertStatus(201);

        return $product;
    }

    public function test_checkout_requires_shipping_address()
    {
        $user = User::create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_CLIENTE,
        ]);
        $this->setUpBuyerWithCartItem($user);

        $response = $this->postJson('/api/cart/checkout');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shipping_address']);
    }

    public function test_checkout_requires_each_mandatory_address_field()
    {
        $user = User::create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_CLIENTE,
        ]);
        $this->setUpBuyerWithCartItem($user);

        $response = $this->postJson('/api/cart/checkout', [
            'shipping_address' => [
                // recipient_name, phone, address, city, state, postal_code missing on purpose
                'number' => '742',
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'shipping_address.recipient_name',
            'shipping_address.phone',
            'shipping_address.address',
            'shipping_address.city',
            'shipping_address.state',
            'shipping_address.postal_code',
        ]);
    }

    public function test_checkout_allows_optional_number_and_notes_to_be_omitted()
    {
        $user = User::create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_CLIENTE,
        ]);
        $this->setUpBuyerWithCartItem($user);

        $response = $this->postJson('/api/cart/checkout', [
            'shipping_address' => [
                'recipient_name' => 'Ana Gómez',
                'phone' => '1122334455',
                'address' => 'Calle Falsa',
                'city' => 'CABA',
                'state' => 'CABA',
                'postal_code' => 'C1000',
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('order_shipping_addresses', [
            'order_id' => $response->json('order.id'),
            'recipient_name' => 'Ana Gómez',
            'number' => null,
            'notes' => null,
        ]);
    }

    public function test_order_detail_includes_shipping_address()
    {
        $user = User::create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_CLIENTE,
        ]);
        $this->setUpBuyerWithCartItem($user);

        $checkout = $this->postJson('/api/cart/checkout', [
            'shipping_address' => [
                'recipient_name' => 'Carlos Ruiz',
                'phone' => '1155556666',
                'address' => 'San Martín',
                'number' => '100',
                'city' => 'Rosario',
                'state' => 'Santa Fe',
                'postal_code' => '2000',
                'notes' => 'Dejar en portería',
            ],
        ]);
        $checkout->assertStatus(201);
        $orderId = $checkout->json('order.id');

        $show = $this->getJson("/api/orders/{$orderId}");

        $show->assertStatus(200);
        $show->assertJsonPath('order.shipping_address.recipient_name', 'Carlos Ruiz');
        $show->assertJsonPath('order.shipping_address.city', 'Rosario');
        $show->assertJsonPath('order.shipping_address.notes', 'Dejar en portería');

        $index = $this->getJson('/api/orders');
        $index->assertStatus(200);
        $index->assertJsonPath('orders.0.shipping_address.recipient_name', 'Carlos Ruiz');
    }

    public function test_shipping_address_is_an_independent_snapshot_per_order()
    {
        $user = User::create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_CLIENTE,
        ]);

        // First order, first address.
        $this->setUpBuyerWithCartItem($user);
        $firstOrder = $this->postJson('/api/cart/checkout', [
            'shipping_address' => [
                'recipient_name' => 'Dirección Vieja',
                'phone' => '1100000000',
                'address' => 'Calle Uno',
                'city' => 'Ciudad Uno',
                'state' => 'Provincia Uno',
                'postal_code' => '1111',
            ],
        ])->assertStatus(201);
        $firstOrderId = $firstOrder->json('order.id');

        // Second order for the SAME user, different delivery address entirely.
        $this->setUpBuyerWithCartItem($user);
        $secondOrder = $this->postJson('/api/cart/checkout', [
            'shipping_address' => [
                'recipient_name' => 'Dirección Nueva',
                'phone' => '2200000000',
                'address' => 'Calle Dos',
                'city' => 'Ciudad Dos',
                'state' => 'Provincia Dos',
                'postal_code' => '2222',
            ],
        ])->assertStatus(201);
        $secondOrderId = $secondOrder->json('order.id');

        // The first order's snapshot must remain exactly as it was placed —
        // untouched by the second checkout's different delivery data.
        $first = $this->getJson("/api/orders/{$firstOrderId}");
        $first->assertJsonPath('order.shipping_address.recipient_name', 'Dirección Vieja');
        $first->assertJsonPath('order.shipping_address.city', 'Ciudad Uno');

        $second = $this->getJson("/api/orders/{$secondOrderId}");
        $second->assertJsonPath('order.shipping_address.recipient_name', 'Dirección Nueva');
        $second->assertJsonPath('order.shipping_address.city', 'Ciudad Dos');
    }
}
