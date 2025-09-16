<?php

use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\RegionController;
use App\Http\Controllers\API\ShippingController;
use Illuminate\Support\Facades\Route;

Route::prefix('carts')->group(function(){
  Route::get('', [CartController::class, 'index']);
  Route::get('count', [CartController::class, 'count']);
  Route::post('', [CartController::class, 'store']);
  Route::delete('items/{cart_item}', [CartController::class, 'destroy']);
});

Route::prefix('orders')->group(function(){
  Route::post('', [OrderController::class, 'store']);
});

Route::prefix('region')->group(function(){
  Route::get('provinces', [RegionController::class, 'provinces']);
  Route::get('provinces/{province}/regencies', [RegionController::class, 'regencies']);
  Route::get('provinces/{province}/regencies/{regency}/districts', [RegionController::class, 'districts']);
  Route::get('provinces/{province}/regencies/{regency}/districts/{district}/villages', [RegionController::class, 'villages']);
  Route::get('postal-code/{village}', [RegionController::class, 'postalCode']);
});

Route::prefix('products')->group(function(){
  Route::get('', [ProductController::class, 'index']);
  Route::get('{productId}/variations', [ProductController::class, 'getProductVariationByOptions']);
  Route::get('{product}', [ProductController::class, 'show']);
  Route::post('', [ProductController::class, 'store']);
  Route::patch('{product}', [ProductController::class, 'update']);
  Route::post('import', [ProductController::class, 'saveProductsFromJSON']);
});

Route::prefix('shipping')->group(function(){
  Route::post('calculate', [ShippingController::class, 'calculate']);
});

Route::get('search', [ProductController::class, 'search']);
