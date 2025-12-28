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
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            // make product_id nullable so tests can create variations without a product
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('sku')->nullable();
            $table->decimal('price', 10);
            $table->decimal('discount_price', 10)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->decimal('weight')->default(500);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['product_id', 'price'], 'product_variations_product_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variations');
    }
};
