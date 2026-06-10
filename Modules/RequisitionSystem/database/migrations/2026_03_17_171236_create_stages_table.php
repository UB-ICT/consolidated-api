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
        if (Schema::connection($this->connection)->hasTable('stages')) {
            return;
        }

        Schema::connection($this->connection)->create('stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('pipeline_id')->constrained('pipelines');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('stages');
    }
};
