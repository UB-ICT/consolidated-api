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

        if (!$schema->hasTable('user_stages')) {
            return;
        }

        if (!$schema->hasColumn('user_stages', 'id')) {
            $primaryKey = DB::connection($this->connection)->selectOne("
                SELECT con.conname AS name
                FROM pg_constraint con
                INNER JOIN pg_class rel ON rel.oid = con.conrelid
                INNER JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
                WHERE nsp.nspname = current_schema()
                  AND rel.relname = 'user_stages'
                  AND con.contype = 'p'
                LIMIT 1
            ");

            if ($primaryKey?->name) {
                DB::connection($this->connection)->statement(
                    sprintf('ALTER TABLE user_stages DROP CONSTRAINT %s', $primaryKey->name)
                );
            }

            DB::connection($this->connection)->statement(
                'ALTER TABLE user_stages ADD COLUMN id BIGSERIAL PRIMARY KEY'
            );
        }

        $this->ensureUniqueUserStagePair($schema);
    }

    public function down(): void
    {
        // Legacy tables without surrogate keys cannot be safely restored.
    }

    private function ensureUniqueUserStagePair($schema): void
    {
        $uniqueConstraint = DB::connection($this->connection)->selectOne("
            SELECT con.conname AS name
            FROM pg_constraint con
            INNER JOIN pg_class rel ON rel.oid = con.conrelid
            INNER JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
            WHERE nsp.nspname = current_schema()
              AND rel.relname = 'user_stages'
              AND con.contype = 'u'
            LIMIT 1
        ");

        if ($uniqueConstraint?->name) {
            return;
        }

        $schema->table('user_stages', function (Blueprint $table) {
            $table->unique(['user_id', 'stage_id']);
        });
    }
};
