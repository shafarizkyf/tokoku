<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('cart_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();

            $table->string('recipient_name', 50)->nullable();
            $table->string('recipient_phone', 20)->nullable();
            $table->text('address_detail')->nullable();
            $table->unsignedMediumInteger('province_id')->nullable();
            $table->unsignedInteger('regency_id')->nullable();
            $table->unsignedInteger('district_id')->nullable();
            $table->unsignedInteger('village_id')->nullable();
            $table->unsignedMediumInteger('postal_code')->nullable();
            $table->text('note')->nullable();

            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedMediumInteger('total_weight')->default(0);
            $table->unsignedBigInteger('shipping_price')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->string('courier', 50)->nullable();
            $table->string('service_type', 50)->nullable();

            $table->string('payment_method', 50)->nullable();
            $table->enum('payment_status', ['pending', 'processing', 'paid', 'failed', 'expired'])->default('pending');
            $table->string('payment_reference', 100)->nullable();
            $table->text('payment_response')->nullable();
            $table->string('payment_url', 500)->nullable();
            $table->timestamp('payment_expired_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->enum('status', ['active', 'completed', 'expired', 'cancelled'])->default('active');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('session_id');
            $table->index('user_id');
            $table->index('cart_id');
            $table->index('order_id');
            $table->index(['status', 'expires_at']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_sessions');
    }
};
