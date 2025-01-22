<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('labor_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->json('schedule_array'); // Store the schedule in JSON format
            $table->timestamps();
            $table->softDeletes(); // Enable soft deletes
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_schedules');
    }
};
