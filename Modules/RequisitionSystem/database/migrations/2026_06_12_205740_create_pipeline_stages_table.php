<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        Schema::connection($this->connection)->create('pipeline_stages', function (Blueprint $table) {
            $table->foreignId('pipeline_id')->constrained('pipelines')->onDelete('cascade');
            $table->foreignId('stage_id')->constrained('stages')->onDelete('cascade');

            // Setting a composite primary key for the pivot relationship consistency
            $table->primary(['pipeline_id', 'stage_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('pipeline_stages');
    }
};
