<?php

namespace Modules\RequisitionSystem\database\migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait EnsuresTimestamps
{
    protected function ensureTableWithTimestamps(string $table, callable $definition): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable($table)) {
            $schema->create($table, $definition);

            return;
        }

        $schema->table($table, function (Blueprint $blueprint) use ($schema, $table) {
            if (!$schema->hasColumn($table, 'created_at')) {
                $blueprint->timestamp('created_at')->nullable();
            }

            if (!$schema->hasColumn($table, 'updated_at')) {
                $blueprint->timestamp('updated_at')->nullable();
            }
        });
    }
}
