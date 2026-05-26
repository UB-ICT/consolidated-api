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
        if (Schema::hasTable('group_roles')) {
            return;
        }
        // Pivot table mapping groups to roles.
        Schema::create('group_roles', function (Blueprint $table) {
            $table->foreignUuid('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignUuid('role_id')->constrained('roles')->onDelete('cascade');
            // Prevent duplicate group-role assignments.
            $table->primary(['group_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drops the group-role pivot table.
        Schema::dropIfExists('group_roles');
    }
};
