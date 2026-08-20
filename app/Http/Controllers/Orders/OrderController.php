<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource (orders for the authenticated user).
     */
    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, int $id)
    {
        $order = Order::with('items.product')->findOrFail($id);

        // Ensure the authenticated user owns the order
        abort_unless($order->user_id === $request->user()->id, 403);

        return response()->json([
            'order' => $order,
        ]);
    }
}

