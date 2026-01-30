<?php

namespace App\Http\Controllers;

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
