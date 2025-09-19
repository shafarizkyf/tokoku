<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\ImageDownloadController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
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
});

Route::prefix('emails')->group(function(){
  Route::get('orders/{order}', [OrderController::class, 'orderEmailPreview']);
});

Route::prefix('orders')->group(function(){
  Route::get('', [OrderController::class, 'index'])->name('orders.index');
  Route::get('{orderCode}', [OrderController::class, 'show'])->name('orders.details');
});

Route::prefix('products')->group(function(){
  Route::get('', [ProductController::class, 'index'])->name('products.index');
  Route::get('add', [ProductController::class, 'add'])->name('products.add');
  Route::get('{product}/edit', [ProductController::class, 'edit']);
  Route::get('import', [ProductController::class, 'import'])->name('products.import');
  Route::get('{productSlug}', [ProductController::class, 'show'])->name('products.details');
});

Route::get('image-downloader', [ImageDownloadController::class, 'download']);