<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        $schema->table('suppliers', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('suppliers', 'approved_by_user_id')) {
                $table->dropForeign(['approved_by_user_id']);
                $table->dropColumn('approved_by_user_id');
            }

            if ($schema->hasColumn('suppliers', 'status_id')) {
                $table->dropForeign(['status_id']);
                $table->dropColumn('status_id');
            }
        });
    }
};
