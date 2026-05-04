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
        Schema::create('access_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('app_id')->constrained('applications')->onDelete('cascade');
            $table->foreignUuid('requested_role_id')->constrained('roles')->onDelete('cascade');

            $table->string('status')->default('pending'); // pending, approved, denied
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_requests');
    }
};
