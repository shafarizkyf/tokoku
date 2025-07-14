<?php

use App\Http\Controllers\ImageDownloadController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('', [ProductController::class, 'import']);
Route::get('products/{product}/edit', [ProductController::class, 'edit']);
Route::get('image-downloader', [ImageDownloadController::class, 'download']);