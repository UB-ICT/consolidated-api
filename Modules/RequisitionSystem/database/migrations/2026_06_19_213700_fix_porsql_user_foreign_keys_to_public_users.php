<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    /** @var array<string, array{column: string, onDelete: string}> */
    private array $userReferenceForeignKeys = [
        'user_stages' => [
            'column' => 'user_id',
            'onDelete' => 'CASCADE',
        ],
        'user_cost_center' => [
            'column' => 'user_id',
            'onDelete' => 'CASCADE',
        ],
        'approvals' => [
            'column' => 'user_id',
            'onDelete' => 'CASCADE',
        ],
        'logs' => [
            'column' => 'user_id',
            'onDelete' => 'CASCADE',
        ],
        'attachments' => [
            'column' => 'uploaded_by',
            'onDelete' => 'RESTRICT',
        ],
        'suppliers' => [
            'column' => 'approved_by_user_id',
            'onDelete' => 'SET NULL',
        ],
    ];

    public function up(): void
    {
        foreach ($this->userReferenceForeignKeys as $table => $config) {
            $this->ensureForeignKeyToPublicUsers(
                $table,
                $config['column'],
                $config['onDelete']
            );
        }
    }

    public function down(): void
    {
        // Foreign-key targets are corrected in place.
    }

    private function ensureForeignKeyToPublicUsers(
        string $table,
        string $column,
        string $onDelete
    ): void {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable($table) || !$schema->hasColumn($table, $column)) {
            return;
        }

        $this->dropForeignKeyIfExists($table, $column);
        $this->removeOrphanUserReferences($table, $column);

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

    private function removeOrphanUserReferences(string $table, string $column): void
    {
        $schema = Schema::connection($this->connection);
        $columnType = DB::connection($this->connection)->selectOne("
            SELECT a.attnotnull AS not_null
            FROM pg_catalog.pg_attribute a
            JOIN pg_catalog.pg_class c ON c.oid = a.attrelid
            JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
            WHERE n.nspname = current_schema()
              AND c.relname = ?
              AND a.attname = ?
              AND a.attnum > 0
              AND NOT a.attisdropped
        ", [$table, $column]);

        if ($columnType && !$columnType->not_null) {
            DB::connection($this->connection)->statement(
                sprintf(
                    'UPDATE %s SET %s = NULL WHERE %s IS NOT NULL AND NOT EXISTS (SELECT 1 FROM public.users u WHERE u.id = %s.%s)',
                    $table,
                    $column,
                    $column,
                    $table,
                    $column
                )
            );

            return;
        }

        DB::connection($this->connection)->statement(
            sprintf(
                'DELETE FROM %s WHERE NOT EXISTS (SELECT 1 FROM public.users u WHERE u.id = %s.%s)',
                $table,
                $table,
                $column
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
