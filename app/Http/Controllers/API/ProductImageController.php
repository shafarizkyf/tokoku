<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;

class ProductImageController extends Controller {

  public function store(Product $product, StoreProductImageRequest $request) {
    $images = [];
    foreach($request->file('images') as $image) {
      $path = $image->storePublicly("products/{$product->id}", 'public');
      $images[] = $path;

      ProductImage::create([
        'product_id' => $product->id,
        'path' => $path,
      ]);
    }

    return response([
      'success' => true,
      'message' => '',
      'data' => $images,
    ]);
  }

}
