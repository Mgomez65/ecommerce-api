<?php


namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function update(
        UpdateCartItemRequest $request,
        CartItem $cartItem
    ): JsonResponse {
        abort_unless(
            $cartItem->cart?->user_id === $request->user()->id,
            403
        );

        $cartItem->load('product');
        $quantity = $request->validated()['quantity'];

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
}