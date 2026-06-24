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

        if (!$schema->hasTable('suppliers')) {
            return;
        }

        $schema->table('suppliers', function (Blueprint $table) use ($schema) {
            if (!$schema->hasColumn('suppliers', 'status_id')) {
                $table->foreignId('status_id')
                    ->default(1)
                    ->constrained('statuses')
                    ->onDelete('restrict');
            }

            if (!$schema->hasColumn('suppliers', 'approved_by_user_id')) {
                $table->foreignUuid('approved_by_user_id')
                    ->nullable()
                    ->constrained('public.users')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('suppliers')) {
            return;
        }

        if ($schema->hasColumn('suppliers', 'approved_by_user_id')) {
            $this->dropForeignKeyIfExists('suppliers', 'approved_by_user_id');

            $schema->table('suppliers', function (Blueprint $table) {
                $table->dropColumn('approved_by_user_id');
            });
        }

        if ($schema->hasColumn('suppliers', 'status_id')) {
            $this->dropForeignKeyIfExists('suppliers', 'status_id');

            $schema->table('suppliers', function (Blueprint $table) {
                $table->dropColumn('status_id');
            });
        }
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
