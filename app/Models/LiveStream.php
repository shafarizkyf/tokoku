<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveStream extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'channel_name',
        'thumbnail_path',
        'status',
        'scheduled_at',
        'started_at',
        'ended_at',
        'viewer_count',
        'peak_viewer_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'viewer_count' => 'integer',
        'peak_viewer_count' => 'integer',
    ];

    /**
     * Get the user who owns the live stream
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all viewers for this live stream
     */
    public function viewers(): HasMany
    {
        return $this->hasMany(LiveStreamViewer::class);
    }

    /**
     * Get all messages for this live stream
     */
    public function messages(): HasMany
    {
        return $this->hasMany(LiveStreamMessage::class);
    }

    /**
     * Get all products associated with this live stream
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'live_stream_products')
            ->withPivot('display_order', 'is_featured')
            ->withTimestamps()
            ->orderBy('display_order');
    }

    /**
     * Get the thumbnail URL
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        return asset('storage/'.$this->thumbnail_path);
    }

    /**
     * Scope to get only live streams
     */
    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    /**
     * Scope to get scheduled streams
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope to get ended streams
     */
    public function scopeEnded($query)
    {
        return $query->where('status', 'ended');
    }

    /**
     * Start the live stream
     */
    public function start(): void
    {
        $this->update([
            'status' => 'live',
            'started_at' => now(),
        ]);
    }

    /**
     * End the live stream
     */
    public function end(): void
    {
        $this->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);
    }

    /**
     * Update viewer count
     */
    public function updateViewerCount(int $count): void
    {
        $this->viewer_count = $count;

        if ($count > $this->peak_viewer_count) {
            $this->peak_viewer_count = $count;
        }

        $this->save();
    }
}
