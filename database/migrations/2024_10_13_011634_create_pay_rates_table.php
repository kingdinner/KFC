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
        Schema::create('pay_rates', function (Blueprint $table) {
            $table->id();
            $table->string('position');
            $table->decimal('hourly_rate', 8, 2);
            $table->unsignedBigInteger('store_employee_id'); // Add the foreign key column
            $table->timestamps();

            // Add foreign key constraint
            $table->foreign('store_employee_id')
                ->references('id')
                ->on('store_employees')
                ->onDelete('cascade'); // Adjust the "onDelete" action if needed
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_rates');
    }
};