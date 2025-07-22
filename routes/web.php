<?php

use App\Http\Controllers\HomepageController;
use App\Http\Controllers\ImageDownloadController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('', [HomepageController::class, 'index']);

Route::prefix('products')->group(function(){
  Route::get('', [ProductController::class, 'index'])->name('products.index');
  Route::get('add', [ProductController::class, 'add'])->name('products.add');
  Route::get('{product}/edit', [ProductController::class, 'edit']);
  Route::get('import', [ProductController::class, 'import'])->name('products.import');
  Route::get('{productSlug}', [ProductController::class, 'show']);
});

Route::get('image-downloader', [ImageDownloadController::class, 'download']);