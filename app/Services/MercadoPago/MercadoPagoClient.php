<?php

namespace App\Services\MercadoPago;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoClient
{
    private const BASE_URL = 'https://api.mercadopago.com';

    public function __construct(private readonly ?string $accessToken)
    {
    }

    /**
     * Create a Checkout Pro preference.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createPreference(array $payload): array
    {
        $response = $this->http()
            ->post(self::BASE_URL . '/checkout/preferences', $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                'Mercado Pago rechazó la creación de la preferencia: ' . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Fetch the authoritative state of a payment directly from Mercado Pago.
     *
     * @return array<string, mixed>
     */
    public function getPayment(string $paymentId): array
    {
        $response = $this->http()
            ->get(self::BASE_URL . "/v1/payments/{$paymentId}");

        if ($response->failed()) {
            throw new RuntimeException(
                "Mercado Pago rechazó la consulta del pago {$paymentId}: " . $response->body()
            );
        }

        return $response->json();
    }

    private function http()
    {
        if (!$this->accessToken) {
            throw new RuntimeException(
                'MERCADOPAGO_ACCESS_TOKEN no está configurado.'
            );
        }

        return Http::withToken($this->accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }
}
