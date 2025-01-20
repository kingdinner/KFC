<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tmar_reports', function (Blueprint $table) {
            $table->id();
            $table->string('pc')->nullable();
            $table->string('area')->nullable();
            $table->integer('count_per_area')->nullable();
            $table->integer('store_number')->nullable();
            $table->string('sas_name')->nullable();
            $table->string('other_name')->nullable();
            $table->integer('star_0')->default(0);
            $table->integer('star_1')->default(0);
            $table->integer('star_2')->default(0);
            $table->integer('star_3')->default(0);
            $table->integer('star_4')->default(0);
            $table->integer('all_star')->default(0);
            $table->string('team_leader')->nullable();
            $table->string('sldc')->nullable();
            $table->string('sletp')->nullable();
            $table->integer('total_team_member')->nullable();
            $table->decimal('average_tenure', 5, 2)->nullable();
            $table->integer('retention_90_days')->nullable();
            $table->string('restaurant_basics')->nullable();
            $table->string('foh')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Add soft delete column
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tmar_reports');
    }
};
