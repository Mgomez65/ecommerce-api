<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Orders;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\OrderConfirmed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MercadoPagoCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mercadopago.access_token' => 'TEST-ACCESS-TOKEN']);
        config(['services.mercadopago.webhook_secret' => null]);
    }

    private function fakePreferenceResponse(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-123',
                'init_point' => 'https://mercadopago.com/checkout/pref-123',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com/checkout/pref-123',
            ], 201),
        ]);
    }

    private function fakePaymentLookup(string $status, int $orderId, array $overrides = []): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-123',
                'init_point' => 'https://mercadopago.com/checkout/pref-123',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com/checkout/pref-123',
            ], 201),
            'api.mercadopago.com/v1/payments/*' => Http::response(array_merge([
                'id' => 555666,
                'status' => $status,
                'status_detail' => 'test',
                'transaction_amount' => 20.00,
                'currency_id' => 'ARS',
                'external_reference' => "order-{$orderId}",
            ], $overrides), 200),
        ]);
    }

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

    private function checkout(bool $fakeMp = true): array
    {
        $user = User::create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_CLIENTE,
        ]);

        $category = Category::create(['name' => 'Cat ' . uniqid()]);

        $product = Product::create([
            'name' => 'Producto pago',
            'description' => 'desc',
            'price' => 10,
            'stock' => 5,
            'stock_minimo' => 0,
            'active' => true,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertStatus(201);

        if ($fakeMp) {
            $this->fakePreferenceResponse();
        }

        $response = $this->postJson('/api/cart/checkout', $this->validShippingAddress());
        $response->assertStatus(201);

        $order = Orders::findOrFail($response->json('order.id'));

        return [$user, $product, $order, $response];
    }

    public function test_checkout_creates_pending_order_without_decrementing_stock()
    {
        [$user, $product, $order, $response] = $this->checkout();

        $this->assertSame(Orders::STATUS_PENDING, $order->status);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider' => 'mercadopago',
            'preference_id' => 'pref-123',
            'status' => Payment::STATUS_PENDING,
        ]);
        $this->assertSame(
            'https://sandbox.mercadopago.com/checkout/pref-123',
            $response->json('payment.checkout_url')
        );
        $this->assertDatabaseHas('order_shipping_addresses', [
            'order_id' => $order->id,
            'recipient_name' => 'Juan Pérez',
            'phone' => '+54 9 11 1234-5678',
            'address' => 'Av. Siempre Viva',
            'number' => '742',
            'city' => 'Springfield',
            'state' => 'Buenos Aires',
            'postal_code' => '1000',
        ]);
        $this->assertSame('Juan Pérez', $response->json('order.shipping_address.recipient_name'));
    }

    public function test_approved_webhook_confirms_order_and_decrements_stock()
    {
        [$user, $product, $order] = $this->checkout();

        $this->fakePaymentLookup('approved', $order->id);

        $webhook = $this->postJson('/api/payments/mercadopago/webhook?type=payment&data.id=555666');
        $webhook->assertStatus(200);

        $order->refresh();
        $this->assertSame(Orders::STATUS_CONFIRMED, $order->status);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'external_id' => '555666',
            'status' => Payment::STATUS_APPROVED,
        ]);
        $this->assertSame(1, StockMovement::where('product_id', $product->id)->count());
    }

    public function test_approved_webhook_sends_order_confirmed_email()
    {
        Notification::fake();

        [$user, $product, $order] = $this->checkout();

        $this->fakePaymentLookup('approved', $order->id);

        $this->postJson('/api/payments/mercadopago/webhook?type=payment&data.id=555666')
            ->assertStatus(200);

        Notification::assertSentTo(
            $user,
            OrderConfirmed::class,
            fn ($notification) => $notification->toMail($user)->subject === "Confirmamos tu pedido #{$order->id}"
        );
    }

    public function test_duplicate_webhook_delivery_does_not_double_decrement_stock()
    {
        [$user, $product, $order] = $this->checkout();

        $this->fakePaymentLookup('approved', $order->id);

        $this->postJson('/api/payments/mercadopago/webhook?type=payment&data.id=555666')->assertStatus(200);
        $this->postJson('/api/payments/mercadopago/webhook?type=payment&data.id=555666')->assertStatus(200);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
        $this->assertSame(1, StockMovement::where('product_id', $product->id)->count());
        $this->assertSame(
            1,
            Payment::where('order_id', $order->id)->where('external_id', '555666')->count()
        );
    }

    public function test_refunded_webhook_after_approval_restores_stock_and_cancels_order()
    {
        [$user, $product, $order] = $this->checkout();

        // Http::fake() has "first matching stub wins" semantics, so a second
        // call to fakePaymentLookup() wouldn't override the first — use a
        // sequence to return 'approved' then 'refunded' for the two calls.
        $basePayment = [
            'id' => 555666,
            'status_detail' => 'test',
            'transaction_amount' => 20.00,
            'currency_id' => 'ARS',
            'external_reference' => "order-{$order->id}",
        ];

        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::sequence()
                ->push(array_merge($basePayment, ['status' => 'approved']), 200)
                ->push(array_merge($basePayment, ['status' => 'refunded']), 200),
        ]);

        $this->postJson('/api/payments/mercadopago/webhook?type=payment&data.id=555666')
            ->assertStatus(200);

        $order->refresh();
        $this->assertSame(Orders::STATUS_CONFIRMED, $order->status);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);

        $this->postJson('/api/payments/mercadopago/webhook?type=payment&data.id=555666')
            ->assertStatus(200);

        $order->refresh();
        $this->assertSame(Orders::STATUS_CANCELLED, $order->status);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
        $this->assertSame(2, StockMovement::where('product_id', $product->id)->count());
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'external_id' => '555666',
            'status' => Payment::STATUS_REFUNDED,
        ]);
    }

    public function test_pay_endpoint_regenerates_preference_for_pending_order()
    {
        // Same first-match-wins caveat as above: set up the sequence before
        // checkout() runs, since it also hits 'checkout/preferences'.
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::sequence()
                ->push([
                    'id' => 'pref-123',
                    'init_point' => 'https://mercadopago.com/checkout/pref-123',
                    'sandbox_init_point' => 'https://sandbox.mercadopago.com/checkout/pref-123',
                ], 201)
                ->push([
                    'id' => 'pref-456',
                    'init_point' => 'https://mercadopago.com/checkout/pref-456',
                    'sandbox_init_point' => 'https://sandbox.mercadopago.com/checkout/pref-456',
                ], 201),
        ]);

        [$user, $product, $order] = $this->checkout(fakeMp: false);

        $response = $this->postJson("/api/orders/{$order->id}/pay");

        $response->assertStatus(200);
        $this->assertSame(
            'https://sandbox.mercadopago.com/checkout/pref-456',
            $response->json('payment.checkout_url')
        );
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'preference_id' => 'pref-456',
            'status' => Payment::STATUS_PENDING,
        ]);
        $this->assertSame(2, Payment::where('order_id', $order->id)->count());
    }

    public function test_pay_endpoint_rejects_non_pending_order()
    {
        [$user, $product, $order] = $this->checkout();

        $this->fakePaymentLookup('approved', $order->id);
        $this->postJson('/api/payments/mercadopago/webhook?type=payment&data.id=555666')
            ->assertStatus(200);

        $this->postJson("/api/orders/{$order->id}/pay")->assertStatus(422);
    }

    public function test_pay_endpoint_forbidden_for_other_users_order()
    {
        [$user, $product, $order] = $this->checkout();

        $other = User::create([
            'name' => 'Other',
            'email' => 'other@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_CLIENTE,
        ]);
        Sanctum::actingAs($other);

        $this->postJson("/api/orders/{$order->id}/pay")->assertStatus(403);
    }

    public function test_rejected_webhook_cancels_order_without_touching_stock()
    {
        [$user, $product, $order] = $this->checkout();

        $this->fakePaymentLookup('rejected', $order->id);

        $this->postJson('/api/payments/mercadopago/webhook?type=payment&data.id=555666')
            ->assertStatus(200);

        $order->refresh();
        $this->assertSame(Orders::STATUS_CANCELLED, $order->status);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
        $this->assertSame(0, StockMovement::where('product_id', $product->id)->count());
    }

    public function test_pending_webhook_leaves_order_pending()
    {
        [$user, $product, $order] = $this->checkout();

        $this->fakePaymentLookup('in_process', $order->id);

        $this->postJson('/api/payments/mercadopago/webhook?type=payment&data.id=555666')
            ->assertStatus(200);

        $order->refresh();
        $this->assertSame(Orders::STATUS_PENDING, $order->status);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    }

    public function test_webhook_rejects_invalid_signature_when_secret_is_configured()
    {
        config(['services.mercadopago.webhook_secret' => 'super-secret']);

        [$user, $product, $order] = $this->checkout();

        $this->fakePaymentLookup('approved', $order->id);

        $response = $this->postJson(
            '/api/payments/mercadopago/webhook?type=payment&data.id=555666',
            [],
            ['x-signature' => 'ts=123,v1=deadbeef', 'x-request-id' => 'req-1']
        );

        $response->assertStatus(401);

        $order->refresh();
        $this->assertSame(Orders::STATUS_PENDING, $order->status);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    }

    public function test_webhook_accepts_valid_signature_when_secret_is_configured()
    {
        $secret = 'super-secret';
        config(['services.mercadopago.webhook_secret' => $secret]);

        [$user, $product, $order] = $this->checkout();

        $this->fakePaymentLookup('approved', $order->id);

        $ts = '1700000000';
        $requestId = 'req-1';
        $dataId = '555666';
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $v1 = hash_hmac('sha256', $manifest, $secret);

        $response = $this->postJson(
            "/api/payments/mercadopago/webhook?type=payment&data.id={$dataId}",
            [],
            ['x-signature' => "ts={$ts},v1={$v1}", 'x-request-id' => $requestId]
        );

        $response->assertStatus(200);

        $order->refresh();
        $this->assertSame(Orders::STATUS_CONFIRMED, $order->status);
    }

    public function test_checkout_fails_gracefully_when_mercadopago_is_unreachable()
    {
        $user = User::create([
            'name' => 'Buyer2',
            'email' => 'buyer2@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_CLIENTE,
        ]);

        $category = Category::create(['name' => 'Cat ' . uniqid()]);

        $product = Product::create([
            'name' => 'Producto sin MP',
            'description' => 'desc',
            'price' => 10,
            'stock' => 5,
            'stock_minimo' => 0,
            'active' => true,
            'category_id' => $category->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertStatus(201);

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response(['message' => 'error'], 500),
        ]);

        $response = $this->postJson('/api/cart/checkout', $this->validShippingAddress());
        $response->assertStatus(502);

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'status' => Orders::STATUS_PENDING]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    }
}
