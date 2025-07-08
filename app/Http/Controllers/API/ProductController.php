<?php

namespace App\Http\Controllers\API;

use App\Helpers\Image;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller {

  public function store() {
    $file = Storage::disk('local')->get('sample.json');
    $productRequest = json_decode($file);

    $product = new Product;
    $product->store_id = 1;
    $product->name = substr($productRequest->name, 0, 191);
    $product->slug = Str::slug($productRequest->name);
    $product->description = $productRequest->description;
    $product->price = $productRequest->price;
    $product->discount_price =$productRequest->originalPrice ? $productRequest->price : null;
    $product->stock = $productRequest->stock;
    $product->review_avg = $productRequest->reviewAvg;
    $product->review_count = $productRequest->reviewCount;
    $product->created_by = 1;
    $product->save();

    $images = [];
    foreach($productRequest->images as $image) {
      $savePath = "products/{$product->id}";
      $path = Image::saveImageFromUrl($image->image500, $savePath);

      if ($path) {
        $images[] = [
          'product_id' => $product->id,
          'path' => $path,
          'alt' => substr($image->alt, 0, 191),
          'created_at' => now(),
          'updated_at' => now(),
        ];
      }
    }

    ProductImage::insert($images);

    return response($product);
  }

}
