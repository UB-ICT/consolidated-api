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
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('requisitions')) {
            return;
        }

        if (!$schema->hasColumn('requisitions', 'reviewing_cost_center_id')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->foreignId('reviewing_cost_center_id')
                    ->nullable()
                    ->after('cost_center_id')
                    ->constrained('cost_centers')
                    ->nullOnDelete();

                $table->index('reviewing_cost_center_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('requisitions')) {
            return;
        }

        if ($schema->hasColumn('requisitions', 'reviewing_cost_center_id')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->dropForeign(['reviewing_cost_center_id']);
                $table->dropColumn('reviewing_cost_center_id');
            });
        }
    }
};
