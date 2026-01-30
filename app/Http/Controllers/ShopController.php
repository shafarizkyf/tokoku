<?php

namespace App\Http\Controllers;

class ShopController extends Controller
{
    public function settings()
    {
        return view('admin.shop.settings');
    }
}
