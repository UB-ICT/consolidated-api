<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');

            $this->convertPrimaryKeyToUuid('users', [
                'user_roles' => 'user_id',
            ]);
            $this->convertPrimaryKeyToUuid('roles', [
                'user_roles' => 'role_id',
                'role_permissions' => 'role_id',
                'menus' => 'role_id',
            ]);
            $this->convertPrimaryKeyToUuid('permissions', [
                'role_permissions' => 'permission_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot safely restore bigint IDs after UUID assignment.
    }

    private function convertPrimaryKeyToUuid(string $table, array $referencingColumns = []): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'id')) {
            return;
        }

        if (Schema::getColumnType($table, 'id') === 'uuid') {
            return;
        }

        foreach ($referencingColumns as $referencingTable => $referencingColumn) {
            if (!Schema::hasTable($referencingTable) || !Schema::hasColumn($referencingTable, $referencingColumn)) {
                continue;
            }

            DB::statement(sprintf(
                'ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s_%s_foreign',
                $referencingTable,
                $referencingTable,
                $referencingColumn
            ));
        }

        DB::statement("ALTER TABLE {$table} ADD COLUMN id_uuid UUID");
        DB::statement("UPDATE {$table} SET id_uuid = gen_random_uuid()");

        if ($table === 'users') {
            $this->convertSessionsUserIdFromUsers();
            $this->convertPersonalAccessTokensForUsers();
        }

        foreach ($referencingColumns as $referencingTable => $referencingColumn) {
            if (!Schema::hasTable($referencingTable) || !Schema::hasColumn($referencingTable, $referencingColumn)) {
                continue;
            }

            $referencingColumnType = Schema::getColumnType($referencingTable, $referencingColumn);

            DB::statement("
                UPDATE {$referencingTable} ref
                SET {$referencingColumn} = src.id_uuid
                FROM {$table} src
                WHERE ref.{$referencingColumn}::text = src.id::text
            ");

            if (in_array($referencingColumnType, ['int8', 'bigint', 'integer', 'int4'], true)) {
                DB::statement("
                    ALTER TABLE {$referencingTable}
                    ALTER COLUMN {$referencingColumn} TYPE UUID
                    USING {$referencingColumn}::text::uuid
                ");
            }
        }

        DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$table}_pkey");
        DB::statement("ALTER TABLE {$table} DROP COLUMN id");
        DB::statement("ALTER TABLE {$table} RENAME COLUMN id_uuid TO id");
        DB::statement("ALTER TABLE {$table} ADD PRIMARY KEY (id)");

        foreach ($referencingColumns as $referencingTable => $referencingColumn) {
            if (!Schema::hasTable($referencingTable) || !Schema::hasColumn($referencingTable, $referencingColumn)) {
                continue;
            }

            $onDelete = $referencingTable === 'menus' ? 'SET NULL' : 'CASCADE';

            DB::statement("
                ALTER TABLE {$referencingTable}
                ADD CONSTRAINT {$referencingTable}_{$referencingColumn}_foreign
                FOREIGN KEY ({$referencingColumn}) REFERENCES {$table}(id) ON DELETE {$onDelete}
            ");
        }
    }

    private function convertSessionsUserIdFromUsers(): void
    {
        if (!Schema::hasTable('sessions') || !Schema::hasColumn('sessions', 'user_id')) {
            return;
        }

        $sessionsColumnType = Schema::getColumnType('sessions', 'user_id');
        if (!in_array($sessionsColumnType, ['int8', 'bigint', 'integer', 'int4'], true)) {
            return;
        }

        DB::statement('ALTER TABLE sessions ADD COLUMN user_id_uuid UUID');
        DB::statement('
            UPDATE sessions s
            SET user_id_uuid = u.id_uuid
            FROM users u
            WHERE s.user_id = u.id
        ');
        DB::statement('ALTER TABLE sessions DROP COLUMN user_id');
        DB::statement('ALTER TABLE sessions RENAME COLUMN user_id_uuid TO user_id');
    }

    private function convertPersonalAccessTokensForUsers(): void
    {
        if (
            !Schema::hasTable('personal_access_tokens')
            || !Schema::hasColumn('personal_access_tokens', 'tokenable_id')
        ) {
            return;
        }

        $tokenableColumnType = Schema::getColumnType('personal_access_tokens', 'tokenable_id');
        if (!in_array($tokenableColumnType, ['string', 'varchar', 'text'], true)) {
            DB::statement('ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE varchar(255) USING tokenable_id::text');
        }

        DB::statement("
            UPDATE personal_access_tokens pat
            SET tokenable_id = u.id_uuid::text
            FROM users u
            WHERE pat.tokenable_type LIKE '%User%'
              AND pat.tokenable_id = u.id::text
        ");
    }
};
