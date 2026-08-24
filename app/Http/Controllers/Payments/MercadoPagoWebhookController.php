<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Orders;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Notifications\OrderConfirmed;
use App\Services\MercadoPago\MercadoPagoClient;
use App\Services\MercadoPago\WebhookSignatureValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        private readonly MercadoPagoClient $client,
        private readonly WebhookSignatureValidator $signatureValidator,
    ) {
    }

    /**
     * Receive a Mercado Pago webhook notification. The notification body is
     * never trusted for the actual payment status — it's only used to learn
     * *which* payment to look up; the real status always comes from a
     * server-to-server call to Mercado Pago's API (see MercadoPagoClient).
     */
    public function handle(Request $request): JsonResponse
    {
        $type = $request->input('type') ?? $request->query('type') ?? $request->query('topic');

        // MP sends the payment id as the query param `data.id`, but PHP
        // mangles dots into underscores when parsing query strings, so in
        // practice it only ever arrives as `data_id` — the POST body (JSON,
        // where dot-notation ->input('data.id') works fine) is checked first.
        $dataId = $request->input('data.id')
            ?? $request->query('data_id')
            ?? $request->query('id');

        if ($type !== 'payment' || !$dataId) {
            // Other topics (e.g. merchant_order) aren't relevant to this flow.
            return response()->json(['message' => 'Evento ignorado.'], 200);
        }

        $dataId = strtolower((string) $dataId);

        if (!$this->signatureValidator->isValid(
            $request->header('x-signature'),
            $request->header('x-request-id'),
            $dataId
        )) {
            Log::warning('Mercado Pago webhook: firma inválida', ['data_id' => $dataId]);

            return response()->json(['message' => 'Firma inválida.'], 401);
        }

        $existing = Payment::where('provider', 'mercadopago')
            ->where('external_id', $dataId)
            ->first();

        if ($existing && $existing->processed_at && in_array($existing->status, [
            Payment::STATUS_REJECTED,
            Payment::STATUS_CANCELLED,
            Payment::STATUS_REFUNDED,
            Payment::STATUS_CHARGED_BACK,
        ], true)) {
            // Terminal, unambiguous states: nothing further to reconcile.
            // STATUS_APPROVED is deliberately excluded — an approved payment
            // can still transition to refunded/charged_back later, so those
            // notifications must keep reaching the full flow below.
            return response()->json(['message' => 'Ya procesado.'], 200);
        }

        try {
            $payment = $this->client->getPayment($dataId);
        } catch (Throwable $e) {
            Log::error('Mercado Pago webhook: error consultando el pago', [
                'data_id' => $dataId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'No se pudo verificar el pago.'], 502);
        }

        $orderId = $this->resolveOrderId($payment['external_reference'] ?? null);

        if (!$orderId) {
            Log::warning('Mercado Pago webhook: external_reference inválida', [
                'data_id' => $dataId,
                'external_reference' => $payment['external_reference'] ?? null,
            ]);

            return response()->json(['message' => 'Referencia de orden inválida.'], 200);
        }

        $confirmedOrder = null;

        try {
            DB::transaction(function () use ($payment, $dataId, $orderId, &$confirmedOrder) {
                $order = Orders::with('items')->lockForUpdate()->findOrFail($orderId);

                // A Payment row already exists from checkout (created when the
                // preference was generated), but without an external_id yet —
                // find it by order instead of creating a disconnected duplicate.
                $paymentRecord = Payment::where('provider', 'mercadopago')
                    ->where('external_id', $dataId)
                    ->first()
                    ?? Payment::where('provider', 'mercadopago')
                        ->where('order_id', $order->id)
                        ->whereNull('external_id')
                        ->latest('id')
                        ->first()
                    ?? new Payment(['provider' => 'mercadopago']);

                $paymentRecord->fill([
                    'order_id' => $order->id,
                    'external_id' => $dataId,
                    'status' => $payment['status'] ?? Payment::STATUS_PENDING,
                    'status_detail' => $payment['status_detail'] ?? null,
                    'amount' => $payment['transaction_amount'] ?? $order->total,
                    'currency' => $payment['currency_id'] ?? config('services.mercadopago.currency'),
                    'raw_payload' => $payment,
                    'processed_at' => now(),
                ]);
                $paymentRecord->save();

                if ($paymentRecord->status === Payment::STATUS_APPROVED
                    && $order->status === Orders::STATUS_PENDING) {
                    $this->confirmOrderAndDecrementStock($order);
                    $confirmedOrder = $order;
                } elseif (in_array($paymentRecord->status, [Payment::STATUS_REJECTED, Payment::STATUS_CANCELLED], true)
                    && $order->status === Orders::STATUS_PENDING) {
                    $order->update(['status' => Orders::STATUS_CANCELLED]);
                } elseif (in_array($paymentRecord->status, [Payment::STATUS_REFUNDED, Payment::STATUS_CHARGED_BACK], true)
                    && $order->status === Orders::STATUS_CONFIRMED) {
                    $this->restoreStockAndCancelOrder($order);
                }
            });
        } catch (Throwable $e) {
            Log::error('Mercado Pago webhook: error procesando el pago', [
                'data_id' => $dataId,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'No se pudo procesar la notificación.'], 200);
        }

        if ($confirmedOrder) {
            $this->notifyOrderConfirmed($confirmedOrder);
        }

        return response()->json(['message' => 'Procesado.'], 200);
    }

    /**
     * Best-effort: a failed email must never turn an already-processed
     * payment into a webhook error (Mercado Pago would just retry it).
     */
    private function notifyOrderConfirmed(Orders $order): void
    {
        try {
            $order->loadMissing('user');
            $order->user?->notify(new OrderConfirmed($order));
        } catch (Throwable $e) {
            Log::error('No se pudo enviar el email de confirmación de pedido', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Informational landing endpoint for Mercado Pago's back_urls. Never
     * changes any order/payment state — the webhook is the only source of
     * truth for that. This just tells the client where to look.
     */
    public function return(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Estamos verificando tu pago. Consultá GET /api/orders/{id} para ver el estado real, ya confirmado por Mercado Pago.',
            'status_from_redirect' => $request->query('status', $request->query('collection_status')),
        ]);
    }

    private function resolveOrderId(?string $externalReference): ?int
    {
        if (!$externalReference || !str_starts_with($externalReference, 'order-')) {
            return null;
        }

        $id = substr($externalReference, strlen('order-'));

        return ctype_digit($id) ? (int) $id : null;
    }

    private function confirmOrderAndDecrementStock(Orders $order): void
    {
        foreach ($order->items as $item) {
            $product = Product::lockForUpdate()->find($item->product_id);

            if (!$product) {
                continue;
            }

            $product->decrement('stock', $item->quantity);

            StockMovement::create([
                'product_id' => $product->id,
                'quantity' => $item->quantity,
                'movement_type' => 'salida',
                'motivo' => "Pago aprobado orden #{$order->id}",
            ]);
        }

        $order->update(['status' => Orders::STATUS_CONFIRMED]);
    }

    /**
     * A previously approved payment was refunded or charged back: give the
     * stock back and void the order.
     */
    private function restoreStockAndCancelOrder(Orders $order): void
    {
        foreach ($order->items as $item) {
            $product = Product::lockForUpdate()->find($item->product_id);

            if (!$product) {
                continue;
            }

            $product->increment('stock', $item->quantity);

            StockMovement::create([
                'product_id' => $product->id,
                'quantity' => $item->quantity,
                'movement_type' => 'entrada',
                'motivo' => "Reembolso/contracargo orden #{$order->id}",
            ]);
        }

        $order->update(['status' => Orders::STATUS_CANCELLED]);
    }
}
