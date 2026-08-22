<?php

namespace App\Http\Controllers;

/**
 * Renders the admin page shells. Like PageController, these contain no
 * business logic — data and authorization both come from the existing
 * JSON API; client-side JS redirects non-staff visitors away.
 */
class AdminPageController extends Controller
{
    public function dashboard()
    {
        return view('pages.admin.dashboard', ['page' => 'admin-dashboard']);
    }

    public function orders()
    {
        return view('pages.admin.orders', ['page' => 'admin-orders']);
    }

    public function products()
    {
        return view('pages.admin.products', ['page' => 'admin-products']);
    }

    public function productCreate()
    {
        return view('pages.admin.product-form', [
            'page' => 'admin-product-form',
            'bodyData' => ['product-id' => ''],
        ]);
    }

    public function productEdit(string $id)
    {
        return view('pages.admin.product-form', [
            'page' => 'admin-product-form',
            'bodyData' => ['product-id' => $id],
        ]);
    }
}
