<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationOption;
use App\Models\VariationAttribute;
use App\Models\VariationOption;

class ProductController extends Controller {

  public function index() {
    return view('admin.product.index');
  }

  public function show($slug) {
    $product = Product::whereSlug($slug)->firstOrFail();

    $variationOptions = ProductVariation::options()
      ->where('product_id', $product->id)
      ->get()
      ->groupBy('attribute_name',)
      ->toArray();

    // return $variationOptions;

    return view('homepage.product.show', compact('product', 'variationOptions'));
  }

  public function import() {
    return view('admin.product.import');
  }

  public function add() {
    return view('admin.product.form');
  }

  public function edit(Product $product) {
    return view('admin.product.form', compact('product'));
  }

}
