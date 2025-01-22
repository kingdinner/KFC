<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('availability', function (Blueprint $table) {
            $table->boolean('additional_shift')->default(false)->after('reason');
            $table->date('swap_shift_from')->nullable()->after('additional_shift');
            $table->date('swap_shift_to')->nullable()->after('swap_shift_from');
            $table->string('swap_reason')->nullable()->after('swap_shift_to');
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending')->after('reason');
            $table->string('approval_reason')->nullable()->after('status'); // Reason for rejection or approval
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('availability', function (Blueprint $table) {
            $table->dropColumn(['additional_shift', 'swap_shift_from', 'swap_shift_to', 'swap_reason']);
        });
    }
};
