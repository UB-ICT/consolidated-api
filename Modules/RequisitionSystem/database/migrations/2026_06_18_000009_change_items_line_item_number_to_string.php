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

        if (!$schema->hasTable('items')) {
            return;
        }

        if (!$schema->hasColumn('items', 'line_item_number')) {
            return;
        }

        $columnType = DB::connection($this->connection)
            ->selectOne("
                SELECT data_type
                FROM information_schema.columns
                WHERE table_schema = current_schema()
                  AND table_name = 'items'
                  AND column_name = 'line_item_number'
            ");

        if ($columnType && $columnType->data_type === 'character varying') {
            return;
        }

        DB::connection($this->connection)->statement(
            'ALTER TABLE items ALTER COLUMN line_item_number TYPE VARCHAR(50) USING line_item_number::text'
        );
    }

    public function down(): void
    {
        // Line item numbers may include non-numeric values after this migration.
    }
};
