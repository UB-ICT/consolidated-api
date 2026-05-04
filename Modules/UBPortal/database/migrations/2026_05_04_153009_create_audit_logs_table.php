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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The "Who"
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->onDelete('set null');

            // The "Target" (Who was affected)
            $table->foreignUuid('target_id')->nullable()->constrained('users')->onDelete('set null');

            // The "Where"
            $table->foreignUuid('app_id')->nullable()->constrained('applications')->onDelete('set null');

            $table->string('action'); // e.g., "Updated Role", "Deleted Application"
            $table->string('severity'); // e.g., "low", "medium", "high", "critical"

            $table->timestamp('created_at')->useCurrent();
            // Usually audit logs don't need 'updated_at' because they are immutable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
