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

        if (Schema::hasTable('permissions')) {
            return;
        }

        // Stores granular permission actions grouped by category.
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Human-friendly grouping such as "User Management".
            $table->string('category');
            // Machine-friendly key such as "edit_grades".
            $table->string('action_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drops the permissions table.
        Schema::dropIfExists('permissions');
    }
};