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
        Schema::create('user_stages', function (Blueprint $table) {
            $table->foreignUuId('user_id')->constrained('users');
            $table->foreignUuId('stage_id')->constrained('stages');
            $table->primary(['user_id', 'stage_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_stages');
    }
};
