<?php

use App\Http\Controllers\API\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('products/import', [ProductController::class, 'saveProductsFromJSON']);
