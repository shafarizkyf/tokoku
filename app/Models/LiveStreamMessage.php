<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveStreamMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'live_stream_id',
        'user_id',
        'username',
        'message',
        'is_pinned',
        'is_deleted',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_deleted' => 'boolean',
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
     * Scope to get only non-deleted messages
     */
    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope to get pinned messages
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }
}
