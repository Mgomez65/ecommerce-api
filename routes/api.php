<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductImageController;
use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Cart\CartItemController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Payments\MercadoPagoWebhookController;

// Auth
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');
Route::get('/auth/me', [AuthController::class, 'me'])
    ->middleware('auth:sanctum');

// Rutas públicas
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

// Mercado Pago (llamado por Mercado Pago, sin auth de la app)
Route::prefix('payments/mercadopago')->group(function () {
    Route::post('/webhook', [MercadoPagoWebhookController::class, 'handle']);
    Route::get('/return', [MercadoPagoWebhookController::class, 'return']);
});

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::patch('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    Route::post(
        '/products/{product}/images',
        [ProductImageController::class, 'store']
    );

    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::patch('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    Route::apiResource('/users', UserController::class);

    // Carrito
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'store']);

        Route::patch('/items/{cartItem}', [
            CartItemController::class,
            'update',
        ]);

        Route::delete('/items/{cartItem}', [
            CartItemController::class,
            'destroy',
        ]);

        Route::delete('/', [CartController::class, 'clear']);
        Route::post('/checkout', [CartController::class, 'checkout']);
    });

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
        Route::patch('/{id}/status', [OrderController::class, 'updateStatus']);
        Route::post('/{id}/pay', [OrderController::class, 'pay']);
    });

    // Dashboard (admin/vendedor)
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [DashboardController::class, 'summary']);
        Route::get('/low-stock', [DashboardController::class, 'lowStock']);
        Route::get('/recent-orders', [DashboardController::class, 'recentOrders']);
        Route::get('/top-products', [DashboardController::class, 'topProducts']);
    });
});