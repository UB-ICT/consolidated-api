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
        // Pivot table mapping roles to applications.
        Schema::create('role_applications', function (Blueprint $table) {
            $table->foreignUuid('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignUuid('app_id')->constrained('applications')->onDelete('cascade');

            // Prevent duplicate role-application assignments.
            $table->primary(['role_id', 'app_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drops the role-application pivot table.
        Schema::dropIfExists('role_applications');
    }
};
