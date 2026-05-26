<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'ubportal';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('access_requests')) {
            return;
        }
        // Tracks user requests for role-based application access.
        Schema::create('access_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('app_id')->constrained('applications')->onDelete('cascade');
            $table->foreignUuid('requested_role_id')->constrained('roles')->onDelete('cascade');

            // Workflow status such as pending, approved, or denied.
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drops the access requests table.
        Schema::dropIfExists('access_requests');
    }
};
