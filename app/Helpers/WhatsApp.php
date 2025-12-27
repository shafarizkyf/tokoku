<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsApp {

  public static function sendText(string $text) {
    if (!env('META_WA_SYSTEM_USER_TOKEN') || !env('META_PHONE_NUMBER_ID') || !env('MY_WA_NUMBER')) {
      Log::channel('whatsapp')->info('sendText: META_WA_SYSTEM_USER_TOKEN, META_PHONE_NUMBER_ID, and MY_WA_NUMBER are required');
      return false;
    }

    $response = Http::withToken(env('META_WA_SYSTEM_USER_TOKEN'))
      ->post('https://graph.facebook.com/v22.0/'. env('META_PHONE_NUMBER_ID') .'/messages', [
        'messaging_product' => 'whatsapp',
        'to' => env('MY_WA_NUMBER'),
        'type' => 'text',
        'text' => [
          'body' => $text,
        ],
      ]);

    if (!$response->successful()) {
      Log::channel('whatsapp')->error('sendText: ' . $response->body());
      return false;
    }

    Log::channel('whatsapp')->info('sendText: ', $response->json());
    return $response->json();
  }

}