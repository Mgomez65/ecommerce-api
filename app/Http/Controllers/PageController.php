<?php

namespace App\Http\Controllers;

/**
 * Renders the storefront page shells. All actual data comes from the
 * existing JSON API (routes/api.php) via client-side fetch — these methods
 * intentionally contain no business logic, just view + route param wiring.
 */
class PageController extends Controller
{
    public function home()
    {
        return view('pages.home', ['page' => 'home']);
    }

    public function catalog()
    {
        return view('pages.catalog', ['page' => 'catalog']);
    }

    public function categories()
    {
        return view('pages.categories', ['page' => 'categories']);
    }

    public function product(string $id)
    {
        return view('pages.product', [
            'page' => 'product',
            'bodyData' => ['product-id' => $id],
        ]);
    }

    public function cart()
    {
        return view('pages.cart', ['page' => 'cart']);
    }

    public function checkout()
    {
        return view('pages.checkout', ['page' => 'checkout']);
    }

    public function login()
    {
        return view('pages.login', ['page' => 'login']);
    }

    public function register()
    {
        return view('pages.register', ['page' => 'register']);
    }

    public function forgotPassword()
    {
        return view('pages.forgot-password', ['page' => 'forgot-password']);
    }

    public function resetPassword()
    {
        return view('pages.reset-password', ['page' => 'reset-password']);
    }

    public function orders()
    {
        return view('pages.orders', ['page' => 'orders']);
    }

    public function orderShow(string $id)
    {
        return view('pages.order-detail', [
            'page' => 'order-detail',
            'bodyData' => ['order-id' => $id],
        ]);
    }
}
