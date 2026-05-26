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

        if (Schema::hasTable('applications')) {
            return;
        }

        // Stores applications that roles and requests can target.
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('app_name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drops the applications table.
        Schema::dropIfExists('applications');
    }
};
