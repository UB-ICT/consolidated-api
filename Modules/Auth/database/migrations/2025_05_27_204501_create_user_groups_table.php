<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    /**
     * Run the migrations.
     */
    public function up(): void
    {

        if (Schema::hasTable('user_groups')) {
            return;
        }

        // Pivot table mapping users to groups.
        Schema::create('user_groups', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('group_id')->constrained('groups')->onDelete('cascade');
            // Prevent duplicate user-group assignments.
            $table->primary(['user_id', 'group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drops the user-group pivot table.
        Schema::dropIfExists('user_groups');
    }
};