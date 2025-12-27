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
        Schema::create('reg_provinces', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
        });

        Schema::create('reg_regencies', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('province_id');
            $table->string('name');
            
            $table->foreign('province_id')->references('id')->on('reg_provinces')->onDelete('cascade');
        });

        Schema::create('reg_districts', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('regency_id');
            $table->string('name');
            
            $table->foreign('regency_id')->references('id')->on('reg_regencies')->onDelete('cascade');
        });

        Schema::create('reg_villages', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('district_id');
            $table->string('name');
            
            $table->foreign('district_id')->references('id')->on('reg_districts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reg_villages');
        Schema::dropIfExists('reg_districts');
        Schema::dropIfExists('reg_regencies');
        Schema::dropIfExists('reg_provinces');
    }
};
