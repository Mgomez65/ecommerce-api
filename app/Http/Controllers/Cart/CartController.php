<?php


namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Orders;
use App\Models\OrderItem;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
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

    public function update(
        UpdateCartItemRequest $request,
        CartItem $cartItem
    ): JsonResponse {
        abort_unless(
            $cartItem->cart?->user_id === $request->user()->id,
            403
        );

        $quantity = $request->validated()['quantity'];
        $cartItem->load('product');

        if ($quantity > $cartItem->product->stock) {
            return response()->json([
                'message' => 'La cantidad supera el stock disponible.',
            ], 422);
        }

        $cartItem->update(['quantity' => $quantity]);

        return response()->json([
            'message' => 'Cantidad actualizada.',
            'item' => $cartItem->fresh('product'),
        ]);
    }

    public function destroy(
        Request $request,
        CartItem $cartItem
    ): JsonResponse {
        abort_unless(
            $cartItem->cart?->user_id === $request->user()->id,
            403
        );

        $cartItem->delete();

        return response()->json([
            'message' => 'Producto eliminado del carrito.',
        ]);
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

    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();

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

        DB::transaction(function () use ($cart, $user, &$order) {
            // Create the order record
            $order = Orders::create([
                'user_id' => $user->id,
                'status' => Orders::STATUS_PENDING,
                'total' => 0,
            ]);

            $total = 0;

            foreach ($cart->items as $item) {
                $product = Product::lockForUpdate()
                    ->findOrFail($item->product_id);

                // Validate active
                if (! $product->active) {
                    abort(422, "El producto {$product->name} no está disponible.");
                }

                // Validate stock
                if ($item->quantity > $product->stock) {
                    abort(422, "Stock insuficiente para {$product->name}.");
                }

                $unitPrice = $product->price;
                $subtotal = round($unitPrice * $item->quantity, 2);

                // Create order item with the current unit price
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                ]);

                // Decrement product stock
                $product->decrement('stock', $item->quantity);

                // Register stock movement (salida)
                StockMovement::create([
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'movement_type' => 'salida',
                    'motivo' => "Venta orden #{$order->id}",
                ]);

                $total += $subtotal;
            }

            // Update order total
            $order->update(['total' => $total]);

            // Clear cart
            $cart->items()->delete();
        });

        return response()->json([
            'message' => 'Compra realizada correctamente.',
            'order' => $order->load('items.product'),
        ], 201);
    }
}