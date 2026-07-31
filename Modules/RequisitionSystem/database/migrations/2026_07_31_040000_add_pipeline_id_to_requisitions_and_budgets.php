<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        $db = DB::connection($this->connection);

        if ($schema->hasTable('requisitions') && !$schema->hasColumn('requisitions', 'pipeline_id')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->foreignId('pipeline_id')
                    ->nullable()
                    ->after('cost_center_id')
                    ->constrained('pipelines')
                    ->nullOnDelete();
            });
        }

        if ($schema->hasTable('budgets') && !$schema->hasColumn('budgets', 'pipeline_id')) {
            $schema->table('budgets', function (Blueprint $table) {
                $table->foreignId('pipeline_id')
                    ->nullable()
                    ->after('budget_year_id')
                    ->constrained('pipelines')
                    ->nullOnDelete();
            });
        }

        $porPipelineId = $db->table('pipelines')->where('name', 'operations')->value('id')
            ?? $db->table('pipelines')->where('name', '!=', 'budget')->orderBy('id')->value('id');

        $budgetPipelineId = $db->table('pipelines')->where('name', 'budget')->value('id');

        if ($porPipelineId && $schema->hasColumn('requisitions', 'pipeline_id')) {
            $db->table('requisitions')
                ->whereNull('pipeline_id')
                ->update(['pipeline_id' => $porPipelineId]);
        }

        if ($budgetPipelineId && $schema->hasColumn('budgets', 'pipeline_id')) {
            $db->table('budgets')
                ->whereNull('pipeline_id')
                ->update(['pipeline_id' => $budgetPipelineId]);
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('requisitions') && $schema->hasColumn('requisitions', 'pipeline_id')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('pipeline_id');
            });
        }

        if ($schema->hasTable('budgets') && $schema->hasColumn('budgets', 'pipeline_id')) {
            $schema->table('budgets', function (Blueprint $table) {
                $table->dropConstrainedForeignId('pipeline_id');
            });
        }
    }
};
