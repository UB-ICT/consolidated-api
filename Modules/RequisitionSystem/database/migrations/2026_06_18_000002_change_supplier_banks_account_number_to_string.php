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

        if (!$schema->hasTable('supplier_banks')) {
            return;
        }

        if (!$schema->hasColumn('supplier_banks', 'account_number')) {
            return;
        }

        $columnType = DB::connection($this->connection)
            ->selectOne("
                SELECT data_type
                FROM information_schema.columns
                WHERE table_schema = current_schema()
                  AND table_name = 'supplier_banks'
                  AND column_name = 'account_number'
            ");

        if ($columnType && $columnType->data_type !== 'integer') {
            return;
        }

        DB::connection($this->connection)->statement(
            'ALTER TABLE supplier_banks ALTER COLUMN account_number TYPE VARCHAR(50) USING account_number::text'
        );
    }

    public function down(): void
    {
        // Account numbers may exceed integer range or include non-numeric
        // characters, so this migration is intentionally not reversible.
    }
};
