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
        if (Schema::hasTable('audit_logs')) {
            return;
        }
        // Stores immutable audit events for access and security actions.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // User who performed the action.
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->onDelete('set null');

            // User or subject impacted by the action.
            $table->foreignUuid('target_id')->nullable()->constrained('users')->onDelete('set null');

            // Application context where the action occurred.
            $table->foreignUuid('app_id')->nullable()->constrained('applications')->onDelete('set null');

            // Action description, for example "Updated Role".
            $table->string('action');
            // Severity level such as low, medium, high, or critical.
            $table->string('severity');

            $table->timestamp('created_at')->useCurrent();
            // updated_at is intentionally omitted to keep records immutable.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drops the audit logs table.
        Schema::dropIfExists('audit_logs');
    }
};
