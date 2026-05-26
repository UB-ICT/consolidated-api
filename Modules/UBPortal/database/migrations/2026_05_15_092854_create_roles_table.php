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
        if (Schema::hasTable('roles')) {
            return;
        }

        // Stores role records used for access control.
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('role_name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drops the roles table.
        Schema::dropIfExists('roles');
    }
};
