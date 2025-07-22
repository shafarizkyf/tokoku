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

    $variationOptions = ProductVariation::select(
      'variation_attributes.id as attribute_id',
        'variation_attributes.name as attribute_name',
        'variation_options.id as option_id',
        'variation_options.value as option_name',
        'product_variation_id'
      )
      ->join('product_variation_options', 'product_variation_options.product_variation_id', '=', 'product_variations.id')
      ->join('variation_options', 'variation_options.id', '=', 'product_variation_options.variation_option_id')
      ->join('variation_attributes', 'variation_attributes.id', '=', 'variation_options.variation_attribute_id')
      ->where('product_id', $product->id)
      ->get()
      ->groupBy('attribute_name')
      ->toArray();

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
