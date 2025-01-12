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
        Schema::create('tmar_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('pc')->nullable();
            $table->string('area')->nullable();
            $table->integer('count_per_area')->nullable();
            $table->integer('store_number')->nullable();
            $table->string('sas_name')->nullable();
            $table->string('other_name')->nullable();

            // Star Ratings
            $table->integer('star_0')->default(0);
            $table->integer('star_1')->default(0);
            $table->integer('star_2')->default(0);
            $table->integer('star_3')->default(0);
            $table->integer('star_4')->default(0);
            $table->integer('all_star')->default(0);

            $table->string('team_leader')->nullable();
            $table->string('sldc')->nullable();
            $table->string('sletp')->nullable();

            // Additional Columns
            $table->integer('total_team_member')->nullable();
            $table->decimal('average_tenure', 5, 2)->nullable();
            $table->integer('retention_90_days')->nullable();
            $table->string('restaurant_basics')->nullable();
            $table->string('foh')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmar_summaries');
    }
};
