<?php

use App\Http\Controllers\API\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);
Route::patch('products/{product}', [ProductController::class, 'update']);
Route::post('products/import', [ProductController::class, 'saveProductsFromJSON']);
