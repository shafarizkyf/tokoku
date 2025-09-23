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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('name', 50);
            $table->string('phone_number', 20);
            $table->text('address_detail');
            $table->unsignedMediumInteger('province_id');
            $table->unsignedInteger('regency_id');
            $table->unsignedInteger('district_id');
            $table->unsignedInteger('village_id');
            $table->unsignedInteger('postal_code');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
