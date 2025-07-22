<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller {

  public function index() {
    return view('admin.product.index');
  }

  public function show($slug) {
    $product = Product::whereSlug($slug)->firstOrFail();
    return view('homepage.product.show', compact('product'));
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
