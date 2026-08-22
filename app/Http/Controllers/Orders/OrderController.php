<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Models\Orders;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\MercadoPago\MercadoPagoCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private readonly MercadoPagoCheckoutService $mercadoPagoCheckout
    ) {
    }

    /**
     * Display a listing of the resource. Admins/vendedores see every order
     * (optionally filtered by status); other users only see their own.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = $this->isStaff($user)
            ? Orders::query()
            : $user->orders();

        if ($request->filled('status')) {
            $status = $request->query('status');

            if (!in_array($status, $this->validStatuses(), true)) {
                return response()->json([
                    'message' => 'Estado inválido.',
                ], 422);
            }

            $query->where('status', $status);
        }

        $orders = $query
            ->with('items.product', 'latestPayment', 'shippingAddress')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    /**
     * Display the specified resource. Admins/vendedores can view any order;
     * other users can only view their own.
     */
    public function show(Request $request, int $id)
    {
        $order = Orders::with('items.product', 'latestPayment', 'shippingAddress')->findOrFail($id);

        abort_unless(
            $this->isStaff($request->user()) || $order->user_id === $request->user()->id,
            403
        );

        return response()->json([
            'order' => $order,
        ]);
    }

    /**
     * (Re)generate a Mercado Pago checkout preference for the authenticated
     * user's own pending order — e.g. their previous checkout session
     * expired or the payment was rejected and they want to try again.
     */
    public function pay(Request $request, int $id)
    {
        $order = Orders::findOrFail($id);

        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->status !== Orders::STATUS_PENDING) {
            return response()->json([
                'message' => 'Solo se puede pagar un pedido pendiente.',
            ], 422);
        }

        try {
            $preference = $this->mercadoPagoCheckout->createPreferenceForOrder($order);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo generar el link de pago. Intentá nuevamente en unos minutos.',
            ], 502);
        }

        return response()->json([
            'message' => 'Redirigí al cliente a checkout_url para completar el pago.',
            'payment' => $preference,
        ]);
    }

    /**
     * Cancel the authenticated user's own pending order and restore stock.
     */
    public function cancel(Request $request, int $id)
    {
        $order = Orders::with('items')->findOrFail($id);

        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->status !== Orders::STATUS_PENDING) {
            return response()->json([
                'message' => 'Solo se pueden cancelar pedidos pendientes.',
            ], 422);
        }

        DB::transaction(function () use ($order) {
            $this->restoreStock($order);
            $order->update(['status' => Orders::STATUS_CANCELLED]);
        });

        return response()->json([
            'message' => 'Pedido cancelado.',
            'order' => $order->fresh('items.product', 'shippingAddress'),
        ]);
    }

    /**
     * Update the status of an order (admin/vendedor only).
     */
    public function updateStatus(UpdateOrderRequest $request, int $id)
    {
        if (!$this->isStaff($request->user())) {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        $order = Orders::with('items')->findOrFail($id);

        if (in_array($order->status, [Orders::STATUS_CANCELLED, Orders::STATUS_COMPLETED])) {
            return response()->json([
                'message' => 'El pedido ya está en un estado final y no se puede modificar.',
            ], 422);
        }

        $newStatus = $request->validated()['status'];

        DB::transaction(function () use ($order, $newStatus) {
            if ($newStatus === Orders::STATUS_CANCELLED) {
                $this->restoreStock($order);
            }

            $order->update(['status' => $newStatus]);
        });

        return response()->json([
            'message' => 'Estado del pedido actualizado.',
            'order' => $order->fresh('items.product', 'shippingAddress'),
        ]);
    }

    /**
     * Restore stock for every item of an order, registering the movement.
     */
    private function restoreStock(Orders $order): void
    {
        foreach ($order->items as $item) {
            $product = Product::lockForUpdate()->findOrFail($item->product_id);

            $product->increment('stock', $item->quantity);

            StockMovement::create([
                'product_id' => $product->id,
                'quantity' => $item->quantity,
                'movement_type' => 'entrada',
                'motivo' => "Cancelación orden #{$order->id}",
            ]);
        }
    }

    private function isStaff(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_VENDEDOR], true);
    }

    private function validStatuses(): array
    {
        return [
            Orders::STATUS_PENDING,
            Orders::STATUS_CONFIRMED,
            Orders::STATUS_CANCELLED,
            Orders::STATUS_COMPLETED,
        ];
    }
}
