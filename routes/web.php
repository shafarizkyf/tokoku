<?php

use App\Http\Controllers\ImageDownloadController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('', [ProductController::class, 'import']);
Route::get('image-downloader', [ImageDownloadController::class, 'download']);