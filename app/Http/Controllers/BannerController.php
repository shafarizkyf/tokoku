<?php

namespace App\Http\Controllers;

class BannerController extends Controller
{
    public function index()
    {
        return view('admin.banner.index');
    }
}
