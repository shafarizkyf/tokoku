<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleOAuthController extends Controller {

  public function redirect(){
    return Socialite::driver('google')->redirect();
  }

  public function callback(){
    $googleUser = Socialite::driver('google')->user();

    Log::channel('oauth')->info('google: ', [$googleUser]);

    $user = User::updateOrCreate(
      ['email' => $googleUser->getEmail()],
      [
          'name' => $googleUser->getName(),
          'email_verified_at' => now(),
          'provider' => 'google',
        ]
    );

    Auth::login($user);
    $user->tokens()->delete();
    $token = $user->createToken('customer');
    session()->put('token', $token->plainTextToken);
    return redirect('/');
  }

  // google oauth for web administrator, enabling gmail api
  public function adminCallback() {
    return request()->all();
  }

}
