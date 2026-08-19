<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductImageController;



Route::apiResource(
    'users',
    UserController::class
    
);
Route::apiResource(
    'categories',
    CategoryController::class
);

Route::apiResource(
    'products',
    ProductController::class
);

Route::post('/products/{product}/images', [ProductImageController::class, 'store']);
