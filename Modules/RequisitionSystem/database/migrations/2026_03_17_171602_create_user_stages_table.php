<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('user_stages')) {
            return;
        }

        Schema::connection($this->connection)->create('user_stages', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('por_users');
            $table->foreignId('stage_id')->constrained('stages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('user_stages');
    }
};
