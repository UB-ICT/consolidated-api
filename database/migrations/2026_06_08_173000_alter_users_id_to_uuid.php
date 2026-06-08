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
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'id')) {
            return;
        }

        $columnType = Schema::getColumnType('users', 'id');
        if ($columnType === 'uuid') {
            return;
        }

        DB::transaction(function () {
            DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');

            DB::statement('ALTER TABLE IF EXISTS user_roles DROP CONSTRAINT IF EXISTS user_roles_user_id_foreign');

            DB::statement('ALTER TABLE users ADD COLUMN id_uuid UUID');
            DB::statement('UPDATE users SET id_uuid = gen_random_uuid()');

            if (Schema::hasTable('user_roles') && Schema::hasColumn('user_roles', 'user_id')) {
                $userRolesColumnType = Schema::getColumnType('user_roles', 'user_id');

                if (in_array($userRolesColumnType, ['int8', 'bigint', 'integer', 'int4'], true)) {
                    DB::statement('
                        UPDATE user_roles ur
                        SET user_id = u.id_uuid
                        FROM users u
                        WHERE ur.user_id::text = u.id::text
                    ');
                    DB::statement('ALTER TABLE user_roles ALTER COLUMN user_id TYPE UUID USING user_id::text::uuid');
                } else {
                    DB::statement('
                        UPDATE user_roles ur
                        SET user_id = u.id_uuid
                        FROM users u
                        WHERE ur.user_id::text = u.id::text
                    ');
                }
            }

            if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                $sessionsColumnType = Schema::getColumnType('sessions', 'user_id');

                if (in_array($sessionsColumnType, ['int8', 'bigint', 'integer', 'int4'], true)) {
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
            }

            if (Schema::hasTable('personal_access_tokens') && Schema::hasColumn('personal_access_tokens', 'tokenable_id')) {
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

            DB::statement('ALTER TABLE users DROP CONSTRAINT users_pkey');
            DB::statement('ALTER TABLE users DROP COLUMN id');
            DB::statement('ALTER TABLE users RENAME COLUMN id_uuid TO id');
            DB::statement('ALTER TABLE users ADD PRIMARY KEY (id)');

            if (Schema::hasTable('user_roles') && Schema::hasColumn('user_roles', 'user_id')) {
                DB::statement('
                    ALTER TABLE user_roles
                    ADD CONSTRAINT user_roles_user_id_foreign
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot safely restore bigint IDs after UUID assignment.
    }
};
