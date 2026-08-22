<?php


namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\CheckoutRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Orders;
use App\Models\OrderItem;
use App\Models\OrderShippingAddress;
use App\Services\MercadoPago\MercadoPagoCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function __construct(
        private readonly MercadoPagoCheckoutService $mercadoPagoCheckout
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $request->user()->cart()->firstOrCreate([]);

        return response()->json([
            'cart' => $cart->load('items.product'),
            'total' => $cart->items->sum(
                fn (CartItem $item) => $item->quantity * $item->product->price
            ),
        ]);
    }

    public function store(
        AddCartItemRequest $request
    ): JsonResponse {
        $data = $request->validated();
        $product = Product::findOrFail($data['product_id']);

        if ($product->stock < $data['quantity']) {
            return response()->json([
                'message' => 'No hay suficiente stock disponible.',
            ], 422);
        }

        $cart = $request->user()->cart()->firstOrCreate([]);

        $item = $cart->items()
            ->where('product_id', $product->id)
            ->first();

        $quantity = ($item?->quantity ?? 0) + $data['quantity'];

        if ($quantity > $product->stock) {
            return response()->json([
                'message' => 'La cantidad supera el stock disponible.',
            ], 422);
        }

        $item = $cart->items()->updateOrCreate(
            ['product_id' => $product->id],
            ['quantity' => $quantity]
        );

        return response()->json([
            'message' => 'Producto añadido al carrito.',
            'item' => $item->load('product'),
        ], 201);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $request->user()->cart()->first();

        if ($cart) {
            $cart->items()->delete();
        }

        return response()->json([
            'message' => 'Carrito vaciado.',
        ]);
    }

    /**
     * Turn the cart into a pending order and generate a Mercado Pago
     * checkout preference. Stock is NOT decremented here — it's only
     * decremented once the payment webhook confirms the payment as
     * approved (see MercadoPagoWebhookController).
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $user = $request->user();
        $shippingAddress = $request->validated()['shipping_address'];

        $cart = $user
            ->cart()
            ->with('items.product')
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            return response()->json([
                'message' => 'El carrito está vacío.',
            ], 422);
        }

        $order = null;

        DB::transaction(function () use ($cart, $user, $shippingAddress, &$order) {
            $order = Orders::create([
                'user_id' => $user->id,
                'status' => Orders::STATUS_PENDING,
                'total' => 0,
            ]);

            // Snapshot of the delivery data as entered for THIS order — never
            // updated afterward, so later changes to the user's own data
            // (or a future address book) can't rewrite order history.
            OrderShippingAddress::create(array_merge($shippingAddress, [
                'order_id' => $order->id,
            ]));

            $total = 0;

            foreach ($cart->items as $item) {
                $product = Product::lockForUpdate()
                    ->findOrFail($item->product_id);

                if (! $product->active) {
                    abort(422, "El producto {$product->name} no está disponible.");
                }

                if ($item->quantity > $product->stock) {
                    abort(422, "Stock insuficiente para {$product->name}.");
                }

                $unitPrice = $product->price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                ]);

                $total += round($unitPrice * $item->quantity, 2);
            }

            $order->update(['total' => $total]);

            $cart->items()->delete();
        });

        try {
            $preference = $this->mercadoPagoCheckout->createPreferenceForOrder($order);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'El pedido se creó pero no se pudo generar el link de pago. Podés reintentar desde POST /api/orders/{id}/pay.',
                'order' => $order->load('items.product', 'shippingAddress'),
            ], 502);
        }

        return response()->json([
            'message' => 'Pedido creado. Redirigí al cliente a checkout_url para completar el pago.',
            'order' => $order->load('items.product', 'shippingAddress'),
            'payment' => $preference,
        ], 201);
    }
}