<?php

namespace App\Http\Controllers\API;

use App\Helpers\Tripay;
use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    private static function generateSignature(string $merchantRef, int $amount): string
    {
        $privateKey = env('TRIPAY_MERCHANT_PRIVATE_KEY');
        $merchantCode = env('TRIPAY_MERCHANT_CODE');
        return hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey);
    }

    public function process(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:checkout_sessions,session_id',
            'recipient_name' => 'required|string|max:50',
            'recipient_email' => 'required|email|max:100',
            'recipient_phone' => 'required|string|max:20',
            'address_detail' => 'required|string',
            'province_id' => 'required|exists:reg_provinces,id',
            'regency_id' => 'required|exists:reg_regencies,id',
            'district_id' => 'required|exists:reg_districts,id',
            'village_id' => 'required|exists:reg_villages,id',
            'postal_code' => 'required|digits:5',
            'note' => 'nullable|string|max:100',
            'courier' => 'required|string',
            'service_type' => 'required|string',
            'shipping_price' => 'required|integer|min:0',
            'payment_method' => 'required|string',
        ]);

        $session = CheckoutSession::findBySessionId($request->session_id);

        if (!$session) {
            return response([
                'success' => false,
                'message' => 'Sesi checkout tidak valid atau sudah kedaluwarsa',
            ], 400);
        }

        if ($session->items()->count() === 0) {
            return response([
                'success' => false,
                'message' => 'Keranjang checkout kosong',
            ], 400);
        }

        try {
            DB::transaction(function () use ($request, $session) {
                $session->update([
                    'recipient_name' => $request->recipient_name,
                    'recipient_email' => $request->recipient_email,
                    'recipient_phone' => $request->recipient_phone,
                    'address_detail' => $request->address_detail,
                    'province_id' => $request->province_id,
                    'regency_id' => $request->regency_id,
                    'district_id' => $request->district_id,
                    'village_id' => $request->village_id,
                    'postal_code' => $request->postal_code,
                    'note' => $request->note,
                    'courier' => $request->courier,
                    'service_type' => $request->service_type,
                    'shipping_price' => $request->shipping_price,
                    'payment_method' => $request->payment_method,
                    'payment_status' => 'pending',
                    'payment_expired_at' => now()->addHours(24),
                ]);

                $orderItems = [];
                foreach ($session->items as $item) {
                    $orderItems[] = [
                        'name' => $item->product->name ?? 'Product',
                        'price' => $item->price_at_time,
                        'quantity' => $item->quantity,
                    ];
                }

                if ($request->shipping_price > 0) {
                    $orderItems[] = [
                        'name' => 'Biaya Pengiriman',
                        'price' => $request->shipping_price,
                        'quantity' => 1,
                    ];
                }

                $amount = $session->subtotal + $request->shipping_price;

                $tripayData = [
                    'method' => $request->payment_method,
                    'merchant_ref' => $session->session_id,
                    'amount' => $amount,
                    'customer_name' => $request->recipient_name,
                    'customer_email' => $request->recipient_email,
                    'customer_phone' => $request->recipient_phone,
                    'order_items' => $orderItems,
                    'callback_url'    => route('tripay.callback'),
                    'return_url' => route('checkout.session', ['sessionId' => $session->session_id]),
                    'expired_time' => now()->addHours(24)->timestamp,
                    'signature' => self::generateSignature($session->session_id, $amount),
                ];

                $tripay = new Tripay();
                $response = $tripay->tripay->post('/transaction/create', $tripayData)->json();

                if ($response['success']) {
                    $session->update([
                        'payment_reference' => $response['data']['reference'],
                        'payment_url' => $response['data']['checkout_url'],
                        'payment_response' => $response['data'],
                    ]);

                    Cookie::queue(Cookie::forget('checkout_session'));
                } else {
                    Log::error('Tripay transaction failed', $response);
                    throw new \Exception('Gagal membuat transaksi pembayaran: ' . ($response['message'] ?? 'Unknown error'));
                }
            });

            return response([
                'success' => true,
                'payment_url' => $session->payment_url,
                'redirect_url' => route('orders.index'),
            ]);
        } catch (\Exception $e) {
            Log::error('Checkout process failed: ' . $e->getMessage());

            return response([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses checkout: ' . $e->getMessage(),
            ], 500);
        }
    }
}
