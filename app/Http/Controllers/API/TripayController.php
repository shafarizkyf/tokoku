<?php

namespace App\Http\Controllers\API;

use App\Helpers\Gmail;
use App\Helpers\Stock;
use App\Helpers\Utils;
use App\Helpers\WhatsApp;
use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Models\Order;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class TripayController extends Controller
{
    public function callback()
    {
        Log::channel('tripay')->info('callback', request()->all());

        if (App::environment('production')) {
            $callbackSignature = request()->server('HTTP_X_CALLBACK_SIGNATURE');
            $json = request()->getContent();
            $signature = hash_hmac('sha256', $json, env('TRIPAY_MERCHANT_PRIVATE_KEY'));

            if ($signature !== (string) $callbackSignature) {
                return response([
                    'success' => false,
                    'message' => 'Invalid signature',
                ]);
            }

            if ((string) request()->server('HTTP_X_CALLBACK_EVENT') !== 'payment_status') {
                return response([
                    'success' => false,
                    'message' => 'Unrecognized callback event, no action was taken',
                ]);
            }

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response([
                    'success' => false,
                    'message' => 'Invalid data sent by tripay',
                ]);
            }
        }

        $merchantRef = request('merchant_ref');

        $order = Order::whereCode($merchantRef)->first();

        if ($order) {
            $this->handleOrderCallback($order);

            return [];
        }

        $checkoutSession = CheckoutSession::whereSessionId($merchantRef)->first();

        if ($checkoutSession) {
            $this->handleCheckoutSessionCallback($checkoutSession);

            return [];
        }

        Log::channel('tripay')->warning('No order or checkout session found for merchant_ref: '.$merchantRef);

        return [
            'success' => true,
        ];
    }

    protected function handleOrderCallback(Order $order): void
    {
        Log::channel('tripay')->info(request('status'), ['order_id' => $order->id]);
        $paymentResponse = $order->payment_response;
        $paymentResponse->callback = request()->all();
        $order->payment_response = json_encode($paymentResponse);
        $status = strtoupper(request('status'));

        switch ($status) {
            case 'PAID':
                $order->status = 'paid';
                $order->payment_status = 'paid';
                $order->paid_at = now();
                $order->save();

                $emailTemplate = view('email.order', compact('order'))->render();
                $recipientEmail = $order->user ? $order->user->email : $order->recipient_email;
                if ($recipientEmail) {
                    Gmail::send($recipientEmail, config('app.name').': Kuitansi Pesanan', $emailTemplate);
                }

                $amount = Utils::currencyFormat($order->grand_total);
                $notificationMessage = "Pesanan {$order->code} senilai: {$amount} telah dibayar.";
                WhatsApp::sendText($notificationMessage);
                break;

            case 'FAILED':
                $order->status = 'pending';
                $order->payment_status = 'failed';
                $order->save();

                Stock::revert($order);
                break;

            case 'EXPIRED':
                $order->status = 'pending';
                $order->payment_status = 'expired';
                $order->save();

                Stock::revert($order);
                break;

            case 'REFUND':
                // TODO:
                break;
        }
    }

    protected function handleCheckoutSessionCallback(CheckoutSession $session): void
    {
        Log::channel('tripay')->info('checkout_session_callback', [
            'session_id' => $session->session_id,
            'status' => request('status'),
        ]);

        $paymentResponse = $session->payment_response ?? [];
        $paymentResponse['callback'] = request()->all();
        $session->payment_response = $paymentResponse;

        $status = strtoupper(request('status'));

        switch ($status) {
            case 'PAID':
                $session->payment_status = 'paid';
                $session->payment_reference = request('reference');
                $session->paid_at = now();
                $session->status = 'completed';
                $session->save();

                $newOrder = $session->convertToOrder();

                if ($newOrder) {
                    Log::channel('tripay')->info('checkout_session_converted_to_order', [
                        'session_id' => $session->session_id,
                        'order_id' => $newOrder->id,
                        'order_code' => $newOrder->code,
                    ]);

                    $this->handleOrderCallback($newOrder);
                }
                break;

            case 'FAILED':
                $session->payment_status = 'failed';
                $session->payment_reference = request('reference');
                $session->status = 'cancelled';
                $session->save();

                Stock::revertCheckoutSession($session);
                break;

            case 'EXPIRED':
                $session->payment_status = 'expired';
                $session->status = 'expired';
                $session->save();

                Stock::revertCheckoutSession($session);
                break;

            case 'REFUND':
                // TODO:
                break;
        }
    }
}
