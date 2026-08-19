<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Orders::all();
        return response()->json([
            'orders' => $orders
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $order = Orders::create($request->all());
        return response()->json([
            'message' => 'Pedido creado correctamente',
            'order' => $order
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Orders::findOrFail($id);
        return response()->json([
            'order' => $order
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = Orders::findOrFail($id);
        $order->update($request->all());
        return response()->json([
            'message' => 'Pedido actualizado correctamente',
            'order' => $order
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Orders::findOrFail($id);
        $order->delete();
        return response()->json([
            'message' => 'Pedido eliminado correctamente'
        ]);
    }
}
