<?php

namespace App\Http\Controllers;

class ProductController extends Controller {

  public function import() {
    return view('admin.product.import');
  }

}
