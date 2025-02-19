<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('star_statuses')) {
            Schema::create('star_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('reason')->nullable();
                $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
                $table->timestamps();
                $table->softDeletes(); // Add soft delete column
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('star_statuses');
    }
};
