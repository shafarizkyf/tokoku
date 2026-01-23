<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('channel_name')->unique();
            $table->string('thumbnail_path')->nullable();
            $table->enum('status', ['scheduled', 'live', 'ended'])->default('scheduled');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('viewer_count')->default(0);
            $table->integer('peak_viewer_count')->default(0);
            $table->timestamps();
            
            $table->index('status');
            $table->index('started_at');
        });

        Schema::create('live_stream_viewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->integer('watch_duration')->default(0); // in seconds
            $table->timestamps();
            
            $table->index(['live_stream_id', 'session_id']);
        });

        Schema::create('live_stream_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('username');
            $table->text('message');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
            
            $table->index('live_stream_id');
            $table->index('created_at');
        });

        Schema::create('live_stream_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('display_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            
            $table->unique(['live_stream_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_stream_products');
        Schema::dropIfExists('live_stream_messages');
        Schema::dropIfExists('live_stream_viewers');
        Schema::dropIfExists('live_streams');
    }
};
