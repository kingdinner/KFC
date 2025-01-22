<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTMARAchievementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tmar_achievements', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id'); // Employee name
            $table->string('service_provider'); // Service provider
            $table->integer('tenure_months'); // Tenure in months
            $table->boolean('ninety_day_retention')->default(false); // 90-day retention
            $table->enum('all_star', ['gold', 'silver', 'bronze'])->nullable(); // All-star level
            $table->boolean('team_leader')->default(false); // Is a team leader
            $table->boolean('sletp')->default(false); // SLETP
            $table->boolean('resigned')->default(false); // Is resigned
            $table->boolean('rtm')->default(false); // RTM
            $table->text('remarks')->nullable(); // Remarks

            // Certification Dates
            $table->date('basic_certification')->nullable();
            $table->date('food_safety')->nullable();
            $table->date('champs_certification')->nullable();
            $table->date('restaurant_basic')->nullable();
            $table->date('fod')->nullable(); // FOD certification
            $table->date('mod')->nullable(); // MOD certification
            $table->date('boh')->nullable(); // BOH certification
            $table->date('basic')->nullable(); // Basic certification
            $table->date('certification')->nullable();
            $table->date('sldc')->nullable(); // SLDC certification

            // Station Levels
            $table->enum('kitchen_station_level', ['gold', 'silver', 'bronze'])->nullable();
            $table->date('kitchen_station_date')->nullable();
            $table->enum('counter_station_level', ['gold', 'silver', 'bronze'])->nullable();
            $table->date('counter_station_date')->nullable();
            $table->enum('dining_station_level', ['gold', 'silver', 'bronze'])->nullable();
            $table->date('dining_station_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tmar_achievements');
    }
}
