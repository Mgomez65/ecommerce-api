<?php

namespace App\Services\MercadoPago;

use App\Models\Orders;
use App\Models\Payment;
use Illuminate\Support\Str;

class MercadoPagoCheckoutService
{
    public function __construct(private readonly MercadoPagoClient $client)
    {
    }

    /**
     * Create a Mercado Pago Checkout Pro preference for a pending order and
     * record a Payment row (status pending) tracking the attempt.
     *
     * @return array{checkout_url: string, preference_id: string}
     */
    public function createPreferenceForOrder(Orders $order): array
    {
        $order->loadMissing('items.product', 'user');

        $items = $order->items->map(fn ($item) => [
            'title' => Str::limit($item->product->name ?? "Producto #{$item->product_id}", 250, ''),
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'currency_id' => config('services.mercadopago.currency'),
        ])->values()->all();

        $payload = [
            'items' => $items,
            'payer' => array_filter([
                'email' => $order->user?->email,
                'name' => $order->user?->name,
            ]),
            'external_reference' => "order-{$order->id}",
            'back_urls' => array_filter([
                'success' => config('services.mercadopago.success_url'),
                'failure' => config('services.mercadopago.failure_url'),
                'pending' => config('services.mercadopago.pending_url'),
            ]),
            'auto_return' => 'approved',
            'notification_url' => config('services.mercadopago.notification_url'),
        ];

        $preference = $this->client->createPreference($payload);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'mercadopago',
            'preference_id' => $preference['id'] ?? null,
            'status' => Payment::STATUS_PENDING,
            'amount' => $order->total,
            'currency' => config('services.mercadopago.currency'),
            'raw_payload' => $preference,
        ]);

        $checkoutUrl = config('services.mercadopago.sandbox')
            ? ($preference['sandbox_init_point'] ?? $preference['init_point'] ?? null)
            : ($preference['init_point'] ?? $preference['sandbox_init_point'] ?? null);

        return [
            'checkout_url' => $checkoutUrl,
            'preference_id' => $preference['id'] ?? null,
        ];
    }
}
