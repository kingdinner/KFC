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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('authentication_account_id');
            $table->string('fullname');
            $table->text('address');
            $table->string('contact_number');
            $table->string('job_position');
            $table->softDeletes();  // Soft delete column
            $table->timestamps();

            // Foreign key linking to the authentication_accounts table
            $table->foreign('authentication_account_id')
                  ->references('id')
                  ->on('authentication_accounts')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
