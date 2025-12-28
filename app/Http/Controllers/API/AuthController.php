<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller {

  public function loginByUser(User $user) {
    if (request()->header('X-Test-Key') !== env('TEST_KEY')) {
        abort(403);
    }

    Auth::login($user);
    $user->tokens()->delete();
    $token = $user->createToken('api', [$user->user_type]);
    return ['token' => $token->plainTextToken];
  }

}
