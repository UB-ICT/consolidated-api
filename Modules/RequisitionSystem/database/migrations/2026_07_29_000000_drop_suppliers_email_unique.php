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

        if (!$this->uniqueConstraintExists('suppliers_email_unique')) {
            return;
        }

        $schema->table('suppliers', function (Blueprint $table) {
            $table->dropUnique('suppliers_email_unique');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('suppliers')) {
            return;
        }

        if ($this->uniqueConstraintExists('suppliers_email_unique')) {
            return;
        }

        $schema->table('suppliers', function (Blueprint $table) {
            $table->unique('email');
        });
    }

    private function uniqueConstraintExists(string $name): bool
    {
        $constraint = DB::connection($this->connection)->selectOne(
            "
                SELECT con.conname AS name
                FROM pg_constraint con
                INNER JOIN pg_class rel ON rel.oid = con.conrelid
                INNER JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
                WHERE nsp.nspname = current_schema()
                  AND rel.relname = 'suppliers'
                  AND con.contype = 'u'
                  AND con.conname = ?
                LIMIT 1
            ",
            [$name]
        );

        return $constraint !== null;
    }
};
