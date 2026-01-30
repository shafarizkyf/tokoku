<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            return redirect('/');
        }

        return view('login');
    }

    public function logout()
    {
        $user = Auth::user();
        if ($user) {
            $user->tokens()->delete();
        }
        session()->flush();
        Auth::logout();

        return redirect('/');
    }

    public function loginByUser(User $user)
    {
        Auth::login($user);
        $user->tokens()->delete();
        $token = $user->createToken('api', [$user->user_type]);
        session()->put('token', $token->plainTextToken);

        return redirect('/');
    }
}
