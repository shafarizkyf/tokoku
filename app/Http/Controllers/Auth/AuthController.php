<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller {

  public function index() {
    if (Auth::check()) {
      return redirect('/');
    }

    return view('login');
  }

  public function logout() {
    $user = Auth::user();
    $user->tokens()->delete();
    Auth::logout();
    return redirect('/');
  }

}
