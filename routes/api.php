<?php

use App\Http\Controllers\API\BannerController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ProductImageController;
use App\Http\Controllers\API\RegionController;
use App\Http\Controllers\API\ShippingController;
use App\Http\Controllers\API\TripayController;
use App\Http\Controllers\API\UserAddressController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function(){
  Route::prefix('carts')->group(function(){
    Route::get('', [CartController::class, 'index']);
    Route::get('count', [CartController::class, 'count']);
    Route::post('', [CartController::class, 'store']);
    Route::patch('items/{cart_item}', [CartController::class, 'update']);
    Route::delete('items/{cart_item}', [CartController::class, 'destroy']);
  });

  Route::prefix('orders')->group(function(){
    Route::get('', [OrderController::class, 'index']);
    Route::post('', [OrderController::class, 'store']);
    Route::patch('{order}/resi-number', [OrderController::class, 'updateResiNumber'])->middleware('ability:admin');
    Route::patch('{order}/cancel', [OrderController::class, 'cancel']);
  });

  Route::prefix('payments')->group(function(){
    Route::get('channels', [PaymentController::class, 'channels']);
  });

  Route::middleware('ability:admin')->group(function(){
    Route::prefix('banners')->group(function(){
      Route::post('', [BannerController::class, 'store']);
      Route::patch('{banner}', [BannerController::class, 'update']);
      Route::delete('{banner}', [BannerController::class, 'destroy']);
    });

    Route::prefix('products')->group(function(){
      Route::post('', [ProductController::class, 'store']);
      Route::post('{product}/images', [ProductImageController::class, 'store']);
      Route::patch('{productId}/toggle-active', [ProductController::class, 'toggleActive']);
      Route::patch('{product}', [ProductController::class, 'update']);
      Route::delete('{product}', [ProductController::class, 'destroy']);
      Route::post('import', [ProductController::class, 'saveProductsFromJSON']);
    });
  });

  Route::prefix('region')->group(function(){
    Route::get('provinces', [RegionController::class, 'provinces']);
    Route::get('provinces/{province}/regencies', [RegionController::class, 'regencies']);
    Route::get('provinces/{province}/regencies/{regency}/districts', [RegionController::class, 'districts']);
    Route::get('provinces/{province}/regencies/{regency}/districts/{district}/villages', [RegionController::class, 'villages']);
    Route::get('postal-code/{village}', [RegionController::class, 'postalCode']);
  });

  Route::prefix('shipping')->group(function(){
    Route::post('calculate', [ShippingController::class, 'calculate']);
  });

  Route::prefix('users')->group(function(){
    Route::apiResource('addresses', UserAddressController::class);
  });
});

Route::prefix('products')->group(function(){
  Route::get('', [ProductController::class, 'index']);
  Route::get('{productId}/variations', [ProductController::class, 'getProductVariationByOptions']);
  Route::get('{product}', [ProductController::class, 'show']);
});

Route::get('banners', [BannerController::class, 'index']);

Route::get('search', [ProductController::class, 'search']);

Route::post('tripay/callback', [TripayController::class, 'callback']);
