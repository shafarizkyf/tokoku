<?php

namespace App\Http\Controllers\API;

use App\Helpers\Tripay;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller {

  public function store() {
    return Tripay::requestTransaction('ABC', 100000);
  }

}
