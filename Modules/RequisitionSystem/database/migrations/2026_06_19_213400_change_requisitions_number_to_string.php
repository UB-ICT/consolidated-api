<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('requisitions')) {
            return;
        }

        if (!$schema->hasColumn('requisitions', 'number')) {
            return;
        }

        $columnType = DB::connection($this->connection)
            ->selectOne("
                SELECT pg_catalog.format_type(a.atttypid, a.atttypmod) AS data_type
                FROM pg_catalog.pg_attribute a
                JOIN pg_catalog.pg_class c ON c.oid = a.attrelid
                JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                WHERE n.nspname = current_schema()
                  AND c.relname = 'requisitions'
                  AND a.attname = 'number'
                  AND a.attnum > 0
                  AND NOT a.attisdropped
            ");

        if ($columnType && $columnType->data_type !== 'integer') {
            return;
        }

        DB::connection($this->connection)->statement(
            'ALTER TABLE requisitions ALTER COLUMN number TYPE VARCHAR(255) USING number::text'
        );
    }

    public function down(): void
    {
        // Requisition numbers include non-numeric prefixes, so this migration is not reversible.
    }
};
