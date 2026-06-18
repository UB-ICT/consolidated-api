<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\RequisitionSystem\Database\Migrations\EnsuresTimestamps;

return new class extends Migration
{
    use EnsuresTimestamps;

    protected $connection = 'porsql';

    public function up(): void
    {
        $this->ensureTableWithTimestamps('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('pipelines')->onDelete('cascade');
            $table->foreignId('stage_id')->constrained('stages')->onDelete('cascade');
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamps();
            $table->unique(['pipeline_id', 'stage_id']);
            $table->index(['pipeline_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('pipeline_stages');
    }
};
