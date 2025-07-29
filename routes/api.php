<?php

use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('carts')->group(function(){
  Route::get('', [CartController::class, 'index']);
  Route::get('count', [CartController::class, 'count']);
  Route::post('', [CartController::class, 'store']);
});

Route::prefix('products')->group(function(){
  Route::get('', [ProductController::class, 'index']);
  Route::get('{productId}/variations', [ProductController::class, 'getProductVariationByOptions']);
  Route::get('{product}', [ProductController::class, 'show']);
  Route::post('', [ProductController::class, 'store']);
  Route::patch('{product}', [ProductController::class, 'update']);
  Route::post('import', [ProductController::class, 'saveProductsFromJSON']);
});
