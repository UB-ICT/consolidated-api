<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    /** @var array<string, array{onDelete: string, clearStrategy: 'truncate'|'null'}> */
    private array $userReferenceColumns = [
        'user_stages' => [
            'column' => 'user_id',
            'onDelete' => 'CASCADE',
            'clearStrategy' => 'truncate',
        ],
        'user_cost_center' => [
            'column' => 'user_id',
            'onDelete' => 'CASCADE',
            'clearStrategy' => 'truncate',
        ],
        'approvals' => [
            'column' => 'user_id',
            'onDelete' => 'CASCADE',
            'clearStrategy' => 'truncate',
        ],
        'logs' => [
            'column' => 'user_id',
            'onDelete' => 'CASCADE',
            'clearStrategy' => 'truncate',
        ],
        'attachments' => [
            'column' => 'uploaded_by',
            'onDelete' => 'RESTRICT',
            'clearStrategy' => 'truncate',
        ],
        'suppliers' => [
            'column' => 'approved_by_user_id',
            'onDelete' => 'SET NULL',
            'clearStrategy' => 'null',
        ],
    ];

    public function up(): void
    {
        if (!$this->usersPrimaryKeyIsUuid()) {
            return;
        }

        foreach ($this->userReferenceColumns as $table => $config) {
            $this->convertUserReferenceColumnToUuid(
                $table,
                $config['column'],
                $config['onDelete'],
                $config['clearStrategy']
            );
        }
    }

    public function down(): void
    {
        // UUID user references cannot be safely restored to bigint.
    }

    private function usersPrimaryKeyIsUuid(): bool
    {
        $columnType = DB::connection('pgsql')->selectOne("
            SELECT pg_catalog.format_type(a.atttypid, a.atttypmod) AS data_type
            FROM pg_catalog.pg_attribute a
            JOIN pg_catalog.pg_class c ON c.oid = a.attrelid
            JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
            WHERE n.nspname = 'public'
              AND c.relname = 'users'
              AND a.attname = 'id'
              AND a.attnum > 0
              AND NOT a.attisdropped
        ");

        return $columnType?->data_type === 'uuid';
    }

    private function convertUserReferenceColumnToUuid(
        string $table,
        string $column,
        string $onDelete,
        string $clearStrategy
    ): void {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable($table) || !$schema->hasColumn($table, $column)) {
            return;
        }

        $columnType = DB::connection($this->connection)->selectOne("
            SELECT pg_catalog.format_type(a.atttypid, a.atttypmod) AS data_type
            FROM pg_catalog.pg_attribute a
            JOIN pg_catalog.pg_class c ON c.oid = a.attrelid
            JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
            WHERE n.nspname = current_schema()
              AND c.relname = ?
              AND a.attname = ?
              AND a.attnum > 0
              AND NOT a.attisdropped
        ", [$table, $column]);

        if (!$columnType || $columnType->data_type === 'uuid') {
            return;
        }

        if (!in_array($columnType->data_type, ['bigint', 'integer', 'smallint'], true)) {
            return;
        }

        $this->dropForeignKeyIfExists($table, $column);

        if ($clearStrategy === 'null') {
            DB::connection($this->connection)->statement(
                sprintf('UPDATE %s SET %s = NULL', $table, $column)
            );
        } else {
            DB::connection($this->connection)->statement(
                sprintf('TRUNCATE TABLE %s RESTART IDENTITY CASCADE', $table)
            );
        }

        DB::connection($this->connection)->statement(
            sprintf(
                'ALTER TABLE %s ALTER COLUMN %s TYPE UUID USING %s::text::uuid',
                $table,
                $column,
                $column
            )
        );

        $constraintName = sprintf('%s_%s_foreign', $table, $column);

        DB::connection($this->connection)->statement(
            sprintf(
                'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES public.users(id) ON DELETE %s',
                $table,
                $constraintName,
                $column,
                $onDelete
            )
        );
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
