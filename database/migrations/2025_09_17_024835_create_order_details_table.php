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
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variation_id');
            $table->string('name_snapshot');
            $table->string('variation_snapshot')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedMediumInteger('quantity');
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('subtotal');
            $table->unsignedMediumInteger('weight');
            $table->timestamps();
            // Indexes for performance
            $table->index('order_id');
            $table->index('product_id');
            $table->index('product_variation_id');
            $table->index('name_snapshot');

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
