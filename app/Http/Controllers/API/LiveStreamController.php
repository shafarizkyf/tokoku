<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LiveStream;
use App\Models\LiveStreamViewer;
use App\Models\LiveStreamMessage;
use App\Services\AgoraTokenService;
use App\Services\AblyTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LiveStreamController extends Controller
{
    protected AgoraTokenService $agoraService;
    protected AblyTokenService $ablyService;

    public function __construct(AgoraTokenService $agoraService, AblyTokenService $ablyService)
    {
        $this->agoraService = $agoraService;
        $this->ablyService = $ablyService;
    }

    /**
     * Get all active live streams
     */
    public function active()
    {
        $cacheKey = 'live_streams.active';

        return Cache::remember($cacheKey, now()->addSeconds(10), function () {
            return LiveStream::live()
                ->with(['user', 'products' => function ($query) {
                    $query->limit(3)->with('image');
                }])
                ->orderBy('viewer_count', 'desc')
                ->get()
                ->map(function ($stream) {
                    return [
                        'id' => $stream->id,
                        'title' => $stream->title,
                        'description' => $stream->description,
                        'channel_name' => $stream->channel_name,
                        'thumbnail' => $stream->thumbnail_url,
                        'seller_name' => $stream->user->name ?? 'Unknown',
                        'seller_avatar' => $stream->user->avatar_url ?? null,
                        'viewers' => $stream->viewer_count,
                        'started_at' => $stream->started_at->toIso8601String(),
                        'products' => $stream->products,
                    ];
                });
        });
    }

    /**
     * Get a specific live stream
     */
    public function show($id)
    {
        $stream = LiveStream::with(['user', 'products.image'])
            ->findOrFail($id);

        return [
            'id' => $stream->id,
            'title' => $stream->title,
            'description' => $stream->description,
            'channel_name' => $stream->channel_name,
            'thumbnail' => $stream->thumbnail_url,
            'status' => $stream->status,
            'seller_name' => $stream->user->name ?? 'Unknown',
            'seller_avatar' => $stream->user->avatar_url ?? null,
            'viewers' => $stream->viewer_count,
            'peak_viewers' => $stream->peak_viewer_count,
            'started_at' => $stream->started_at?->toIso8601String(),
            'products' => $stream->products,
        ];
    }

    /**
     * Start a new live stream
     */
    public function start(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $response = null;

        DB::transaction(function () use ($validated, $request, &$response) {
            // Generate unique channel name
            $channelName = 'live-' . Str::random(16);

            // Create live stream
            $stream = LiveStream::create([
                'user_id' => Auth::id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'channel_name' => $channelName,
                'status' => 'live',
                'started_at' => now(),
            ]);

            // Attach products if provided
            if (!empty($validated['product_ids'])) {
                $products = [];
                foreach ($validated['product_ids'] as $index => $productId) {
                    $products[$productId] = [
                        'display_order' => $index,
                        'is_featured' => $index === 0,
                    ];
                }
                $stream->products()->attach($products);
            }

            // Generate Agora token
            $tokenData = $this->agoraService->generateLiveStreamToken(
                $channelName,
                Auth::id(),
                true // isHost
            );

            // Clear cache
            Cache::forget('live_streams.active');

            $response = response()->json([
                'success' => true,
                'message' => 'Live stream started successfully',
                'data' => [
                    'stream' => $stream,
                    'agora' => $tokenData,
                ],
            ], 201);
        });

        return $response;
    }

    /**
     * Stop a live stream
     */
    public function stop($id)
    {
        $stream = LiveStream::where('user_id', Auth::id())
            ->findOrFail($id);

        if ($stream->status !== 'live') {
            return response()->json([
                'success' => false,
                'message' => 'Stream is not live',
            ], 400);
        }

        $stream->end();

        // Clear cache
        Cache::forget('live_streams.active');

        return response()->json([
            'success' => true,
            'message' => 'Live stream ended successfully',
            'data' => [
                'duration' => $stream->ended_at->diffInSeconds($stream->started_at),
                'total_viewers' => $stream->viewers()->count(),
                'peak_viewers' => $stream->peak_viewer_count,
            ],
        ]);
    }

    /**
     * Join a live stream (get token)
     */
    public function join($id, Request $request)
    {
        $stream = LiveStream::with(['products.image', 'products.cheapestVariation'])->findOrFail($id);

        if ($stream->status !== 'live') {
            return response()->json([
                'success' => false,
                'message' => 'Stream is not live',
            ], 400);
        }

        $userId = Auth::id() ?? 0;
        $sessionId = $request->input('session_id', Str::uuid()->toString());

        // Record viewer
        LiveStreamViewer::create([
            'live_stream_id' => $stream->id,
            'user_id' => $userId ?: null,
            'session_id' => $sessionId,
            'joined_at' => now(),
        ]);

        // Update viewer count
        $currentViewers = LiveStreamViewer::where('live_stream_id', $stream->id)
            ->whereNull('left_at')
            ->count();

        $stream->updateViewerCount($currentViewers);

        // Generate token
        $tokenData = $this->agoraService->generateLiveStreamToken(
            $stream->channel_name,
            $userId,
            false // isHost
        );

        // Clear cache
        Cache::forget('live_streams.active');

        return response()->json([
            'success' => true,
            'data' => [
                'stream' => $stream,
                'agora' => $tokenData,
                'session_id' => $sessionId,
            ],
        ]);
    }

    /**
     * Leave a live stream
     */
    public function leave($id, Request $request)
    {
        $sessionId = $request->input('session_id');

        if (!$sessionId) {
            return response()->json([
                'success' => false,
                'message' => 'Session ID required',
            ], 400);
        }

        $viewer = LiveStreamViewer::where('live_stream_id', $id)
            ->where('session_id', $sessionId)
            ->whereNull('left_at')
            ->first();

        if ($viewer) {
            $viewer->left_at = now();
            $viewer->watch_duration = $viewer->calculateDuration();
            $viewer->save();

            // Update viewer count
            $stream = LiveStream::find($id);
            if ($stream) {
                $currentViewers = LiveStreamViewer::where('live_stream_id', $id)
                    ->whereNull('left_at')
                    ->count();

                $stream->updateViewerCount($currentViewers);

                // Clear cache
                Cache::forget('live_streams.active');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Left stream successfully',
        ]);
    }

    /**
     * Get chat messages for a live stream
     */
    public function messages($id)
    {
        $messages = LiveStreamMessage::where('live_stream_id', $id)
            ->active()
            ->latest()
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Send a chat message
     */
    public function sendMessage($id, Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $stream = LiveStream::findOrFail($id);

        if ($stream->status !== 'live') {
            return response()->json([
                'success' => false,
                'message' => 'Stream is not live',
            ], 400);
        }

        $user = Auth::user();
        $username = $user ? $user->name : 'Guest';

        $message = LiveStreamMessage::create([
            'live_stream_id' => $stream->id,
            'user_id' => $user?->id,
            'username' => $username,
            'message' => $validated['message'],
        ]);

        $messageData = [
            'id' => $message->id,
            'username' => $username,
            'message' => $message->message,
            'created_at' => $message->created_at->toIso8601String(),
        ];

        $this->ablyService->publishMessage($stream->id, 'message', $messageData);

        return response()->json([
            'success' => true,
            'data' => $message,
        ], 201);
    }

    /**
     * Get Ably token for real-time chat
     */
    public function ablyToken(Request $request)
    {
        $request->validate([
            'live_stream_id' => 'nullable|exists:live_streams,id',
        ]);

        $user = Auth::user();
        $userId = $user?->id;
        $username = $user?->name;

        $liveStreamId = $request->input('live_stream_id');

        if ($liveStreamId) {
            $stream = LiveStream::find($liveStreamId);
            if (!$stream) {
                return response()->json([
                    'success' => false,
                    'message' => 'Live stream not found',
                ], 404);
            }

            $tokenData = $this->ablyService->generateLiveStreamToken(
                $liveStreamId,
                $userId,
                $username
            );
        } else {
            $clientId = $userId ? 'user:' . $userId : 'guest:' . uniqid();
            $tokenData = $this->ablyService->generateToken(
                $clientId,
                'live-stream:*',
                ['subscribe', 'publish', 'presence'],
                3600
            );
        }

        return $tokenData;
    }

    /**
     * Get viewer statistics
     */
    public function statistics($id)
    {
        $stream = LiveStream::findOrFail($id);

        $stats = [
            'total_viewers' => $stream->viewers()->count(),
            'current_viewers' => $stream->viewer_count,
            'peak_viewers' => $stream->peak_viewer_count,
            'total_messages' => $stream->messages()->count(),
            'average_watch_time' => $stream->viewers()
                ->whereNotNull('left_at')
                ->avg('watch_duration'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
