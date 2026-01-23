<?php

namespace App\Services;

/**
 * Agora Token Generator Service
 * 
 * This service generates RTC tokens for Agora video calls.
 * Based on Agora's token generation algorithm.
 */
class AgoraTokenService
{
    private string $appId;
    private string $appCertificate;

    public function __construct()
    {
        $this->appId = config('services.agora.app_id');
        $this->appCertificate = config('services.agora.app_certificate');
    }

    /**
     * Generate RTC token for a channel
     * 
     * @param string $channelName The channel name
     * @param int $uid User ID (0 for any user)
     * @param string $role Role: 'publisher' or 'subscriber'
     * @param int $privilegeExpireTime Token expiration time in seconds (default: 24 hours)
     * @return string The generated token
     */
    public function generateRtcToken(
        string $channelName,
        int $uid = 0,
        string $role = 'subscriber',
        int $privilegeExpireTime = 86400
    ): string {
        // If no app certificate is set, return empty string (for testing without token)
        if (empty($this->appCertificate)) {
            return '';
        }

        $role = $role === 'publisher' ? 1 : 2; // 1 = publisher, 2 = subscriber
        
        // Calculate privilege expire timestamp
        $privilegeExpiredTs = time() + $privilegeExpireTime;

        // Build token
        return $this->buildToken($channelName, $uid, $role, $privilegeExpiredTs);
    }

    /**
     * Build the actual token
     * 
     * Note: This is a simplified version. For production, you should use
     * Agora's official PHP SDK or implement the full token generation algorithm.
     * 
     * Install via: composer require agora-rtc-sdk/agora-access-token
     */
    private function buildToken(string $channelName, int $uid, $role, int $privilegeExpiredTs): string
    {
        // Check if Agora SDK is available
        if (class_exists('\Agora\RtcTokenBuilder')) {
            // Use official Agora SDK
            return \Agora\RtcTokenBuilder::buildTokenWithUid(
                $this->appId,
                $this->appCertificate,
                $channelName,
                $uid,
                $role,
                $privilegeExpiredTs
            );
        }

        // Fallback: Return empty string if SDK not installed
        // In production, you MUST install the Agora SDK
        \Log::warning('Agora SDK not installed. Install via: composer require agora-rtc-sdk/agora-access-token');
        return '';
    }

    /**
     * Generate token for a live stream
     * 
     * @param string $channelName
     * @param int $userId
     * @param bool $isHost Whether the user is the host
     * @return array Token data
     */
    public function generateLiveStreamToken(string $channelName, int $userId = 0, bool $isHost = false): array
    {
        $role = $isHost ? 'publisher' : 'subscriber';
        $expireTime = $isHost ? 86400 : 3600; // Host: 24h, Viewer: 1h

        $token = $this->generateRtcToken($channelName, $userId, $role, $expireTime);

        return [
            'token' => $token,
            'app_id' => $this->appId,
            'channel_name' => $channelName,
            'uid' => $userId,
            'role' => $role,
            'expire_time' => $expireTime,
            'expire_at' => now()->addSeconds($expireTime)->toIso8601String(),
        ];
    }

    /**
     * Validate if Agora is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->appId);
    }

    /**
     * Check if token authentication is enabled
     */
    public function isTokenAuthEnabled(): bool
    {
        return !empty($this->appCertificate);
    }
}
