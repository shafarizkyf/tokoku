<?php

use App\Http\Controllers\ImageDownloadController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->group(function(){
  Route::get('', [ProductController::class, 'index']);
  Route::get('{product}/edit', [ProductController::class, 'edit']);
  Route::get('import', [ProductController::class, 'import'])->name('products.import');
});

Route::get('image-downloader', [ImageDownloadController::class, 'download']);