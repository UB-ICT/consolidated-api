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
        if (Schema::hasTable('groups')) {
            return;
        }

        // Stores reusable user group definitions.
        Schema::create('groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('group_name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drops the groups table.
        Schema::dropIfExists('groups');
    }
};
