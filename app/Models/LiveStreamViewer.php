<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveStreamViewer extends Model
{
    use HasFactory;

    protected $fillable = [
        'live_stream_id',
        'user_id',
        'session_id',
        'joined_at',
        'left_at',
        'watch_duration',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'watch_duration' => 'integer',
    ];

    /**
     * Get the live stream
     */
    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate watch duration
     */
    public function calculateDuration(): int
    {
        if (!$this->left_at) {
            return now()->diffInSeconds($this->joined_at);
        }

        return $this->left_at->diffInSeconds($this->joined_at);
    }
}
