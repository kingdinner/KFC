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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // Foreign key to employees
            $table->date('date_applied');             // Date when leave is applied    // Leave duration (e.g., "2 days")
            $table->string('type');                     // type of leave (vl and sl)
            $table->string('date_ended');               // Leave duration (e.g., "2 days")
            $table->string('reporting_manager');      // Name of reporting manager
            $table->text('reasons')->nullable();      // Leave reason (nullable)
            $table->string('status')->default('Pending'); // Leave status
            $table->timestamps();

            // Define foreign key constraint
            $table->foreign('employee_id')
                  ->references('id')->on('employees')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
