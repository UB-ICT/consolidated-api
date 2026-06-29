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

        if (!$schema->hasTable('suppliers')) {
            return;
        }

        if (!$schema->hasColumn('suppliers', 'TIN')) {
            return;
        }

        $columnType = DB::connection($this->connection)
            ->selectOne("
                SELECT pg_catalog.format_type(a.atttypid, a.atttypmod) AS data_type
                FROM pg_catalog.pg_attribute a
                JOIN pg_catalog.pg_class c ON c.oid = a.attrelid
                JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                WHERE n.nspname = current_schema()
                  AND c.relname = 'suppliers'
                  AND a.attname = 'TIN'
                  AND a.attnum > 0
                  AND NOT a.attisdropped
            ");

        if ($columnType && $columnType->data_type !== 'integer') {
            return;
        }

        DB::connection($this->connection)->statement(
            'ALTER TABLE suppliers ALTER COLUMN "TIN" TYPE VARCHAR(100) USING "TIN"::text'
        );
    }

    public function down(): void
    {
        // TIN values may include non-numeric characters, so this migration is not reversible.
    }
};
