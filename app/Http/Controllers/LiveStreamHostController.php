<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LiveStreamHostController extends Controller
{
    /**
     * Show the live stream host dashboard
     */
    public function index()
    {
        return view('admin.live-stream.index');
    }
}
