<?php

use App\Http\Controllers\API\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('products/{product}', [ProductController::class, 'show']);
Route::post('products/import', [ProductController::class, 'saveProductsFromJSON']);
