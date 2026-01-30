<?php

namespace App\Http\Controllers;

class IntegrationStatusController extends Controller
{
    public function index()
    {
        $integrations = [
            [
                'name' => 'Komerce',
                'description' => 'Cek Ongkir',
                'required_envs' => ['KOMERCE_URL', 'KOMERCE_SHIPPING_DELIVERY_KEY', 'KOMERCE_SHIPPER_DESTINATION_ID'],
                'message' => 'Integrasi dengan Komerce untuk cek ongkir belum diatur',
            ],
            [
                'name' => 'BinderByte',
                'description' => 'Cek Resi',
                'required_envs' => ['BINDERBYTE_API_KEY'],
                'message' => 'Integrasi dengan BinderByte untuk cek resi belum diatur',
            ],
            [
                'name' => 'Tripay',
                'description' => 'Gerbang Pembayaran',
                'required_envs' => ['TRIPAY_URL', 'TRIPAY_MERCHANT_CODE', 'TRIPAY_MERCHANT_NAME', 'TRIPAY_MERCHANT_API_KEY', 'TRIPAY_MERCHANT_PRIVATE_KEY'],
                'message' => 'Integrasi dengan Tripay untuk gerbang pembayaran belum diatur',
            ],
            [
                'name' => 'Google OAuth',
                'description' => 'Login',
                'required_envs' => ['GOOGLE_OAUTH_CLIENT_ID', 'GOOGLE_OAUTH_CLIENT_SECRET'],
                'message' => 'Integrasi dengan Google OAuth untuk login belum diatur',
            ],
            [
                'name' => 'Google Admin',
                'description' => 'Kirim Email',
                'required_envs' => ['GOOGLE_OAUTH_ADMIN_CLIENT_ID', 'GOOGLE_OAUTH_ADMIN_CLIENT_SECRET', 'GMAIL_FROM_ADDRESS'],
                'message' => 'Integrasi dengan Google Admin untuk mengirim email belum diatur',
            ],
            [
                'name' => 'Agora',
                'description' => 'Live Streaming',
                'required_envs' => ['AGORA_APP_ID', 'AGORA_APP_CERTIFICATE'],
                'message' => 'Integrasi dengan Agora untuk live streaming belum diatur',
            ],
            [
                'name' => 'Ably',
                'description' => 'Real-time Messaging',
                'required_envs' => ['ABLY_API_KEY'],
                'message' => 'Integrasi dengan Ably untuk real-time messaging belum diatur',
            ],
            [
                'name' => 'N8N',
                'description' => 'Chatbot',
                'required_envs' => ['N8N_CHAT_URL'],
                'message' => 'Integrasi dengan N8N untuk chatbot belum diatur',
            ],
            [
                'name' => 'Posthog',
                'description' => 'Analytics',
                'required_envs' => ['POSTHOG_CLIENT_ID'],
                'message' => 'Integrasi dengan Posthog untuk analytics belum diatur',
            ],
        ];

        $status = [];
        foreach ($integrations as $integration) {
            $missing = [];
            foreach ($integration['required_envs'] as $env) {
                if (empty(env($env))) {
                    $missing[] = $env;
                }
            }
            $status[] = [
                'name' => $integration['name'],
                'description' => $integration['description'],
                'message' => $integration['message'],
                'is_configured' => empty($missing),
                'missing_envs' => $missing,
            ];
        }

        return view('homepage.integration-status', compact('status'));
    }
}
