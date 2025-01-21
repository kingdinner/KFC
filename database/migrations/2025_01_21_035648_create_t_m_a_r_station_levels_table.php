<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTMARStationLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tmar_station_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tmar_achievement_id')->constrained('tmar_achievements')->onDelete('cascade'); // Foreign key to TMAR Achievements
            $table->string('station_type'); // kitchen_station, counter_station, dining_station
            $table->enum('level', ['gold', 'silver', 'bronze'])->nullable();
            $table->date('date')->nullable();
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
        Schema::dropIfExists('tmar_station_levels');
    }
}
