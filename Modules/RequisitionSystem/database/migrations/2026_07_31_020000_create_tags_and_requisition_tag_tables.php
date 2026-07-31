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
        $this->ensureTableWithTimestamps('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('cost_center_id')->constrained('cost_centers')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['cost_center_id', 'name']);
        });

        $this->ensureTableWithTimestamps('requisition_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('requisitions')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['requisition_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->dropIfExists('requisition_tag');
        $schema->dropIfExists('tags');
    }
};
