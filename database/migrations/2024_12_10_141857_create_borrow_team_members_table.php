<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('borrowed_store_id');
            $table->date('borrowed_date');
            $table->time('borrowed_time');
            $table->string('borrow_type');
            $table->string('skill_level');
            $table->unsignedBigInteger('transferred_store_id')->nullable();
            $table->date('transferred_date')->nullable();
            $table->time('transferred_time')->nullable();
            $table->string('status')->default('Pending');
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Add soft delete column

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('borrowed_store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('transferred_store_id')->references('id')->on('stores')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_team_members');
    }
};
