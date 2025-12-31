<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShopController extends Controller {

  public function settings() {
    return view('admin.shop.settings');
  }

}
