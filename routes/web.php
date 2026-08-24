<?php

use App\Http\Controllers\AdminPageController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/catalogo', [PageController::class, 'catalog'])->name('catalog');
Route::get('/categorias', [PageController::class, 'categories'])->name('categories');
Route::get('/productos/{id}', [PageController::class, 'product'])->name('product.show');
Route::get('/carrito', [PageController::class, 'cart'])->name('cart');
Route::get('/checkout', [PageController::class, 'checkout'])->name('checkout');
Route::get('/login', [PageController::class, 'login'])->name('login');
Route::get('/registro', [PageController::class, 'register'])->name('register');
Route::get('/olvide-password', [PageController::class, 'forgotPassword'])->name('password.forgot');
Route::get('/resetear-password', [PageController::class, 'resetPassword'])->name('password.reset');
Route::get('/mis-pedidos', [PageController::class, 'orders'])->name('orders.index');
Route::get('/mis-pedidos/{id}', [PageController::class, 'orderShow'])->name('orders.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminPageController::class, 'dashboard'])->name('dashboard');
    Route::get('/pedidos', [AdminPageController::class, 'orders'])->name('orders');
    Route::get('/productos', [AdminPageController::class, 'products'])->name('products');
    Route::get('/productos/nuevo', [AdminPageController::class, 'productCreate'])->name('products.create');
    Route::get('/productos/{id}/editar', [AdminPageController::class, 'productEdit'])->name('products.edit');
});
