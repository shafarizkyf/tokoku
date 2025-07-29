<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller {

  public function index() {
    return view('homepage.cart.index');
  }

}
