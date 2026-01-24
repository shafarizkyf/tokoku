<?php

namespace App\Services;

use Ably\AblyRest;
use Ably\Models\TokenParams;
use Illuminate\Support\Facades\Log;

/**
 * Ably Token Generator Service
 *
 * This service generates Ably authentication tokens for real-time messaging.
 */
class AblyTokenService
{
    private string $apiKey;
    private ?AblyRest $client;

    public function __construct()
    {
        $this->apiKey = config('services.ably.app_api_key', '');
        $this->client = null;
    }

    /**
     * Get Ably client instance
     */
    private function getClient(): ?AblyRest
    {
        if (empty($this->apiKey)) {
            return null;
        }

        if ($this->client === null) {
            try {
                $this->client = new AblyRest($this->apiKey);
            } catch (\Exception $e) {
                Log::error('Failed to initialize Ably client', ['error' => $e->getMessage()]);
                return null;
            }
        }

        return $this->client;
    }

    /**
     * Generate an Ably token for a user
     * 
     * @param string|null $userId The user ID (optional, for authenticated users)
     * @param string $channelName The channel name to authorize
     * @param array $capabilities The capabilities to grant (default: ['subscribe', 'publish', 'presence'])
     * @param int $ttl Token TTL in seconds (default: 3600 = 1 hour)
     * @return array|null Token data or null if failed
     */
    public function generateToken(
        ?string $userId = null,
        string $channelName = '*',
        array $capabilities = ['subscribe', 'publish', 'presence'],
        int $ttl = 3600
    ): ?array {
        $client = $this->getClient();

        if ($client === null) {
            return null;
        }

        try {
            $tokenParams = [
                'ttl' => $ttl,
                'clientId' => $userId ?? 'anonymous',
                'capability' => json_encode([
                    $channelName => $capabilities,
                ]),
            ];

            $tokenRequest = $client->auth->createTokenRequest($tokenParams);

            return (array) $tokenRequest;
        } catch (\Exception $e) {
            Log::error('Failed to generate Ably token', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'channel' => $channelName,
            ]);
            return null;
        }
    }

    /**
     * Generate token for live stream chat
     * 
     * @param string $liveStreamId The live stream ID
     * @param int|null $userId The user ID
     * @param string|null $username The username (for display purposes)
     * @return array Token data
     */
    public function generateLiveStreamToken(
        string $liveStreamId,
        ?int $userId = null,
        ?string $username = null
    ): array {
        $channelName = 'live-stream:' . $liveStreamId;
        $clientId = $userId ? 'user:' . $userId : 'guest:' . uniqid();
        $capabilities = ['subscribe', 'publish', 'presence'];
        $ttl = 7200000; // 2 hours for live streams

        $tokenData = $this->generateToken($clientId, $channelName, $capabilities, $ttl);

        return $tokenData;
    }

    /**
     * Validate if Ably is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Publish a message to a live stream chat channel
     * 
     * @param string $liveStreamId The live stream ID
     * @param string $eventName The event name (e.g., 'message', 'system')
     * @param array $data The message data to publish
     * @return bool Success status
     */
    public function publishMessage(string $liveStreamId, string $eventName, array $data): bool
    {
        $client = $this->getClient();

        if ($client === null) {
            Log::warning('Ably client not available, skipping message publish', [
                'live_stream_id' => $liveStreamId,
                'event' => $eventName,
            ]);
            return false;
        }

        try {
            $channelName = 'live-stream:' . $liveStreamId;
            $channel = $client->channels->get($channelName);

            $result = $channel->publish($eventName, $data);

            Log::debug('Published message to Ably', [
                'channel' => $channelName,
                'event' => $eventName,
                'message_id' => $data['id'] ?? null,
            ]);

            return $result !== null;
        } catch (\Exception $e) {
            Log::error('Failed to publish message to Ably', [
                'error' => $e->getMessage(),
                'live_stream_id' => $liveStreamId,
                'event' => $eventName,
            ]);
            return false;
        }
    }

    /**
     * Generate a direct Ably token string (not just a token request)
     * 
     * @param string $clientId The client ID
     * @param string $channelName The channel name
     * @param array $capabilities The capabilities
     * @param int $ttl Token TTL in seconds
     * @return string|null The token string or null if failed
     */
    public function generateDirectToken(
        string $clientId,
        string $channelName = '*',
        array $capabilities = ['subscribe', 'publish', 'presence'],
        int $ttl = 3600
    ): ?string {
        $client = $this->getClient();

        if ($client === null) {
            return null;
        }

        try {
            $tokenParams = [
                'ttl' => $ttl,
                'clientId' => $clientId,
                'capability' => [
                    $channelName => $capabilities,
                ],
            ];

            $token = $client->auth->requestToken($tokenParams);

            return $token->token;
        } catch (\Exception $e) {
            Log::error('Failed to generate direct Ably token', [
                'error' => $e->getMessage(),
                'client_id' => $clientId,
                'channel' => $channelName,
            ]);
            return null;
        }
    }
}
