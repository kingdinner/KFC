<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_employee_id');
            $table->date('food_safety_certification_date')->nullable();
            $table->date('champs_certification_date')->nullable();
            $table->date('restaurant_basic_certification_date')->nullable();
            $table->date('foh_certification_date')->nullable();
            $table->date('moh_certification_date')->nullable();
            $table->date('boh_certification_date')->nullable();
            $table->string('kitchen_station_level', 50)->nullable();
            $table->date('kitchen_station_certification_date')->nullable();
            $table->string('counter_station_level', 50)->nullable();
            $table->date('counter_station_certification_date')->nullable();
            $table->string('dining_station_level', 50)->nullable();
            $table->date('dining_station_certification_date')->nullable();
            $table->decimal('tenure_in_months', 5, 2)->nullable();
            $table->boolean('retention_90_days')->default(false);
            $table->string('remarks', 255)->nullable();
            $table->timestamps();
            $table->softDeletes(); // Add soft delete column

            $table->foreign('store_employee_id')->references('id')->on('store_employees')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
