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
        Schema::create('borrow_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // Employee being borrowed
            $table->unsignedBigInteger('borrowed_store_id'); // Store borrowing the employee
            $table->date('borrowed_date'); // Date when borrowed
            $table->string('skill_level'); // Skill level of employee
            $table->unsignedBigInteger('transferred_store_id')->nullable(); // Store where transferred (if applicable)
            $table->date('transferred_date')->nullable(); // Date of transfer (if applicable)
            $table->time('transferred_time')->nullable(); // Transfer time
            $table->string('status')->default('Pending'); // Borrow request status
            $table->text('reason')->nullable(); // Reason for borrowing

            $table->timestamps();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('borrowed_store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('transferred_store_id')->references('id')->on('stores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrow_team_members');
    }
};
