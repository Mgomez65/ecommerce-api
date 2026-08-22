<?php

namespace App\Services\MercadoPago;

/**
 * Validates the `x-signature` header Mercado Pago sends with every webhook
 * notification, per their documented "Secret signature" scheme: the header
 * carries `ts=<unix_timestamp>,v1=<hmac>`, and `v1` is the HMAC-SHA256 (hex)
 * of the manifest string `id:<data.id>;request-id:<x-request-id>;ts:<ts>;`
 * keyed with the webhook secret configured in the Mercado Pago dashboard
 * (Tus integraciones > Webhooks > Configurar notificaciones).
 *
 * This is a secondary defense only: the webhook handler never trusts the
 * notification body for payment status regardless of signature validity —
 * it always re-fetches the payment from Mercado Pago's API as the source
 * of truth. Verify this manifest format against your own dashboard before
 * relying on it in production; Mercado Pago has revised it in the past.
 */
class WebhookSignatureValidator
{
    public function __construct(private readonly ?string $secret)
    {
    }

    /**
     * When no secret is configured, signature validation is skipped (the
     * server-to-server payment lookup remains the actual security control).
     */
    public function isEnabled(): bool
    {
        return (bool) $this->secret;
    }

    public function isValid(?string $signatureHeader, ?string $requestId, string $dataId): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if (!$signatureHeader || !$requestId) {
            return false;
        }

        ['ts' => $ts, 'v1' => $v1] = $this->parseSignatureHeader($signatureHeader);

        if (!$ts || !$v1) {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $this->secret);

        return hash_equals($expected, $v1);
    }

    /**
     * @return array{ts: ?string, v1: ?string}
     */
    private function parseSignatureHeader(string $header): array
    {
        $parts = [];

        foreach (explode(',', $header) as $chunk) {
            [$key, $value] = array_pad(explode('=', trim($chunk), 2), 2, null);

            if ($key !== null) {
                $parts[trim($key)] = $value !== null ? trim($value) : null;
            }
        }

        return ['ts' => $parts['ts'] ?? null, 'v1' => $parts['v1'] ?? null];
    }
}
