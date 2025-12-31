<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\ImageDownloadController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ShopController;
use App\Http\Middleware\EnsureUserTypeIsValid;
use Illuminate\Support\Facades\Route;

Route::get('', [HomepageController::class, 'index']);

Route::prefix('auth')->group(function(){
  Route::get('', [AuthController::class, 'index'])->name('login');
  Route::get('logout', [AuthController::class, 'logout'])->name('logout');
  Route::get('google', [GoogleOAuthController::class, 'redirect'])->name('oauth.google');
  Route::get('google/admin/callback', [GoogleOAuthController::class, 'adminCallback']);
  Route::get('google/callback', [GoogleOAuthController::class, 'callback']);
});

Route::middleware(['auth'])->group(function(){
  Route::get('carts', [CartController::class, 'index'])->name('carts.index');

  Route::prefix('emails')->group(function(){
    Route::get('orders/{order}', [OrderController::class, 'orderEmailPreview']);
  });

  Route::prefix('orders')->group(function(){
    Route::get('', [OrderController::class, 'index'])->name('orders.index');
    Route::get('{orderCode}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    Route::get('{orderCode}', [OrderController::class, 'show'])->name('orders.details');
  });

  Route::middleware(EnsureUserTypeIsValid::class . ':admin')->group(function(){
    Route::get('banners', [BannerController::class, 'index'])->name('banners.index');
    Route::prefix('products')->group(function(){
      Route::get('', [ProductController::class, 'index'])->name('products.index');
      Route::get('add', [ProductController::class, 'add'])->name('products.add');
      Route::get('{product}/edit', [ProductController::class, 'edit']);
      Route::get('import', [ProductController::class, 'import'])->name('products.import');
      Route::get('bulk-discount', [ProductController::class, 'bulkDiscountPage'])->name('products.bulk_discount');
      Route::get('bulk-stock', [ProductController::class, 'bulkStockPage'])->name('products.bulk_stock');
    });
    Route::get('shop/settings', [ShopController::class, 'settings'])->name('shop.settings');
  });
});

Route::get('products/{productSlug}', [ProductController::class, 'show'])->name('products.details');
Route::get('image-downloader', [ImageDownloadController::class, 'download']);

Route::get('login/users/{user}', [AuthController::class, 'loginByUser']);
