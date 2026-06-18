<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\RequisitionSystem\Support\RequisitionLogAction;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('logs')) {
            return;
        }

        if (!$schema->hasColumn('logs', 'action')) {
            $schema->table('logs', function (Blueprint $table) {
                $table->string('action')->default(RequisitionLogAction::UPDATED);
            });
        }

        if (!$schema->hasColumn('logs', 'summary')) {
            $schema->table('logs', function (Blueprint $table) {
                $table->text('summary')->nullable();
            });
        }

        if ($schema->hasColumn('logs', 'stage_id')) {
            $schema->table('logs', function (Blueprint $table) {
                $table->dropForeign(['stage_id']);
                $table->dropColumn('stage_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('logs')) {
            return;
        }

        if ($schema->hasColumn('logs', 'summary')) {
            $schema->table('logs', function (Blueprint $table) {
                $table->dropColumn('summary');
            });
        }

        if ($schema->hasColumn('logs', 'action')) {
            $schema->table('logs', function (Blueprint $table) {
                $table->dropColumn('action');
            });
        }

        if (!$schema->hasColumn('logs', 'stage_id')) {
            $schema->table('logs', function (Blueprint $table) {
                $table->foreignId('stage_id')
                    ->nullable()
                    ->constrained('stages');
            });
        }
    }
};
