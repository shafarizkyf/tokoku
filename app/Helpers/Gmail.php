<?php

namespace App\Helpers;

use Google\Client;
use Google\Service\Gmail as GmailService;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;

class Gmail {

  public static function setupCredentials() {
    $client = new Client();
    $client->setAuthConfig(storage_path('google-oauth-credentials.json'));
    $client->addScope(GmailService::GMAIL_SEND);
    $client->setAccessType('offline');

    $tokenPath = storage_path('google-oauth-token.json');
    if (file_exists($tokenPath)) {
      $accessToken = json_decode(file_get_contents($tokenPath), true);
      $client->setAccessToken($accessToken);
    }

    if (file_exists($tokenPath)) {
      $accessToken = json_decode(file_get_contents($tokenPath), true);
      $client->setAccessToken($accessToken);
    }

    if ($client->isAccessTokenExpired()) {
      if ($client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
      } else {
        // First-time auth: redirect to Google
        $authUrl = $client->createAuthUrl();
        echo "Open this link in your browser:\n$authUrl\n";
        echo "Enter the verification code: ";
        $authCode = trim(fgets(STDIN));
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        $client->setAccessToken($accessToken);

        if (!file_exists(dirname($tokenPath))) {
          mkdir(dirname($tokenPath), 0700, true);
        }

        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
      }
    }
  }

  public static function send($to, $subject, $bodyText) {
    $client = new Client();
    $client->setAuthConfig(storage_path('google-oauth-credentials.json'));
    $client->addScope(GmailService::GMAIL_SEND);
    $client->setAccessType('offline');

    $tokenPath = storage_path('google-oauth-token.json');
    if (file_exists($tokenPath)) {
      $accessToken = json_decode(file_get_contents($tokenPath), true);
      $client->setAccessToken($accessToken);
    } else {
      echo "call setupCredentials first";
      return;
    }

    $service = new GmailService($client);

    $rawMessage = [];
    $rawMessage[] = "From: " . env('GMAIL_FROM_ADDRESS');
    $rawMessage[] = "To: $to";
    $rawMessage[] = "Subject: $subject";
    $rawMessage[] = "MIME-Version: 1.0";
    $rawMessage[] = "Content-Type: text/html; charset=UTF-8";
    $rawMessage[] = ""; // Empty line, MUST be here to separate headers and body
    $rawMessage[] = $bodyText;

    // Join all message parts with CRLF to form the raw string
    $mime = implode("\r\n", $rawMessage);
    // Gmail API expects URL-safe base64 encoding (RFC 4648)
    $raw = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($mime));

    try {
      $msg = new Message();
      $msg->setRaw($raw);
      $service->users_messages->send("me", $msg);
      Log::channel('email')->info('send: success');
    } catch (\Exception $e) {
      Log::channel('email')->error('send', ['message' => $e->getMessage()]);
    }
  }

}