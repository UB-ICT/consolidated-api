<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('approvals')) {
            return;
        }

        $schema->table('approvals', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('approvals', 'stage_id')) {
                $table->foreignId('stage_id')
                    ->nullable()
                    ->constrained('stages')
                    ->onDelete('restrict');
            }

            if (!$schema->hasColumn('approvals', 'status')) {
                $table->string('status')->default('pending');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('approvals')) {
            return;
        }

        if ($schema->hasColumn('approvals', 'stage_id')) {
            $schema->table('approvals', function (Blueprint $table) {
                $table->dropForeign(['stage_id']);
                $table->dropColumn('stage_id');
            });
        }

        if ($schema->hasColumn('approvals', 'status')) {
            $schema->table('approvals', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
