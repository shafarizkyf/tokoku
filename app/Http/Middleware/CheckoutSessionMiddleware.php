<?php

namespace App\Http\Middleware;

use App\Models\CheckoutSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckoutSessionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $publicToken = $request->route('token');

        if (! $publicToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid checkout session',
            ], 403);
        }

        $checkoutSession = CheckoutSession::validatePublicToken($publicToken);

        if (! $checkoutSession) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout session invalid or expired',
            ], 403);
        }

        $request->merge(['checkout_session_id' => $checkoutSession->id]);
        $request->route()->setParameter('checkoutSession', $checkoutSession);

        return $next($request);
    }
}
