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

        if ($schema->hasTable('requisitions')) {
            if ($schema->hasColumn('requisitions', 'conversion_rate_id')) {
                $this->dropForeignKeyIfExists('requisitions', 'conversion_rate_id');

                $schema->table('requisitions', function (Blueprint $table) {
                    $table->dropColumn('conversion_rate_id');
                });
            }

            if ($schema->hasColumn('requisitions', 'conversion_rate')) {
                $schema->table('requisitions', function (Blueprint $table) {
                    $table->dropColumn('conversion_rate');
                });
            }
        }

        $schema->dropIfExists('conversion_rates');
    }

    public function down(): void
    {
        // Conversion rates were removed from the requisition workflow.
    }

    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $constraint = DB::connection($this->connection)->selectOne(
            "
                SELECT con.conname AS name
                FROM pg_constraint con
                INNER JOIN pg_class rel ON rel.oid = con.conrelid
                INNER JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
                INNER JOIN pg_attribute att
                    ON att.attrelid = con.conrelid
                    AND att.attnum = ANY (con.conkey)
                WHERE nsp.nspname = current_schema()
                  AND rel.relname = ?
                  AND con.contype = 'f'
                  AND att.attname = ?
                LIMIT 1
            ",
            [$table, $column]
        );

        if (!$constraint?->name) {
            return;
        }

        DB::connection($this->connection)->statement(
            sprintf(
                'ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s',
                $table,
                $constraint->name
            )
        );
    }
};
