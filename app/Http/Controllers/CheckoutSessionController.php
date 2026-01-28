<?php

namespace App\Http\Controllers;

use App\Models\CheckoutSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class CheckoutSessionController extends Controller
{
    public function index($sessionId)
    {
        $session = CheckoutSession::findBySessionId($sessionId);

        if (!$session) {
            return redirect('/')->with('error', 'Sesi checkout tidak valid atau sudah kedaluwarsa');
        }

        return view('homepage.checkout.session', [
            'session' => $session,
            'session_id' => $session->session_id,
            'public_token' => $session->public_token,
        ]);
    }
}
