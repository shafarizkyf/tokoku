<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller {

  public function store() {
    $file = Storage::disk('local')->get('sample.json');
    $xProduct = json_decode($file);

    $product = new Product;
    $product->store_id = 1;
    $product->name = substr($xProduct->name, 0, 191);
    $product->slug = Str::slug($xProduct->name);
    $product->description = $xProduct->description;
    $product->price = $xProduct->price;
    $product->discount_price =$xProduct->originalPrice ? $xProduct->price : null;
    $product->stock = $xProduct->stock;
    $product->review_avg = $xProduct->reviewAvg;
    $product->review_count = $xProduct->reviewCount;
    $product->created_by = 1;
    $product->save();
  }

}
