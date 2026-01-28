<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_session_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('checkout_session_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variation_id');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedBigInteger('price_at_time');
            $table->unsignedBigInteger('price_discount_at_time')->nullable();
            $table->timestamps();

            $table->foreign('checkout_session_id')
                ->references('id')
                ->on('checkout_sessions')
                ->onDelete('cascade');
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            $table->foreign('product_variation_id')
                ->references('id')
                ->on('product_variations')
                ->onDelete('cascade');

            $table->index('checkout_session_id');
            $table->index('product_id');
            $table->index('product_variation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_session_items');
    }
};
