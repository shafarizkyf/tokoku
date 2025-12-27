<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;

class HomepageController extends Controller {

  public function index() {
    $banners = Banner::take(10)->latest()->get();
    return view('homepage.index', compact('banners'));
  }

}
