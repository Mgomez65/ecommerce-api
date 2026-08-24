<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Orders;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * General overview: revenue, orders by status, and entity counts.
     */
    public function summary(Request $request)
    {
        $this->authorize('viewDashboard');

        return response()->json([
            'orders_by_status' => Orders::query()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'total_revenue' => (float) Orders::where('status', Orders::STATUS_COMPLETED)->sum('total'),
            'orders_count' => Orders::count(),
            'products_count' => Product::count(),
            'categories_count' => Category::count(),
            'users_count' => User::count(),
        ]);
    }

    /**
     * Products whose stock has dropped to or below their configured minimum.
     */
    public function lowStock(Request $request)
    {
        $this->authorize('viewDashboard');

        $products = Product::with('category')
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->orderBy('stock')
            ->get();

        return response()->json(['products' => $products]);
    }

    /**
     * Most recently placed orders, newest first.
     */
    public function recentOrders(Request $request)
    {
        $this->authorize('viewDashboard');

        $limit = min((int) $request->query('limit', 10), 50);

        $orders = Orders::with('user', 'items.product')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json(['orders' => $orders]);
    }

    /**
     * Best-selling products by total quantity sold in non-cancelled orders.
     */
    public function topProducts(Request $request)
    {
        $this->authorize('viewDashboard');

        $limit = min((int) $request->query('limit', 10), 50);

        $products = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', '!=', Orders::STATUS_CANCELLED)
            ->selectRaw('products.id, products.name, sum(order_items.quantity) as total_sold')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();

        return response()->json(['products' => $products]);
    }
}
