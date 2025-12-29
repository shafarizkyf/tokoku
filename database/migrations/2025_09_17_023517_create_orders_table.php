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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code', 30)->unique();
            $table->enum('status', ['pending', 'paid', 'shipped', 'completed', 'cancelled'])->default('pending');
            $table->string('payment_method', 20);
            $table->string('payment_reference', 100)->nullable()->unique();
            $table->enum('payment_status', ['unpaid', 'paid', 'expired', 'failed'])->default('unpaid');
            $table->text('payment_response')->nullable();
            $table->dateTime('payment_expired_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('total_price');
            $table->unsignedMediumInteger('total_weight');
            $table->unsignedBigInteger('shipping_price');
            $table->unsignedBigInteger('total_discount')->default(0);
            $table->unsignedBigInteger('grand_total');
            $table->string('courier', 50);
            $table->string('resi_number', 50)->nullable();
            $table->text('resi_track_response')->nullable();
            $table->timestamp('resi_last_track_at')->nullable();
            $table->string('recipient_name', 50);
            $table->string('recipient_phone', 20);
            $table->text('address_detail');
            $table->unsignedMediumInteger('province_id');
            $table->unsignedInteger('regency_id');
            $table->unsignedInteger('district_id');
            $table->unsignedInteger('village_id');
            $table->unsignedInteger('postal_code');
            $table->text('note')->nullable();
            $table->timestamps();
            // Indexes for performance
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['status', 'payment_status']);
            $table->index('code');
            $table->index('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
