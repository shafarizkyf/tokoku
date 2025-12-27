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
            $table->string('code', 20)->unique();
            $table->enum('status', ['pending', 'paid', 'shipped', 'completed', 'cancelled'])->default('pending');
            $table->string('payment_method', 20);
            $table->enum('payment_status', ['unpaid', 'paid', 'expired', 'failed'])->default('unpaid');
            $table->text('payment_response')->nullable();
            $table->dateTime('payment_expired_at')->nullable();
            $table->decimal('total_price', 12);
            $table->unsignedMediumInteger('total_weight');
            $table->decimal('shipping_price', 12);
            $table->decimal('total_discount', 12)->default(0);
            $table->decimal('grand_total', 12);
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
