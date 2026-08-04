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

        if (!$schema->hasTable('requisitions') || !$schema->hasColumn('requisitions', 'priority')) {
            return;
        }

        DB::connection($this->connection)
            ->table('requisitions')
            ->whereIn('priority', ['routine', 'low'])
            ->update(['priority' => 'standard']);

        DB::connection($this->connection)->statement(
            "ALTER TABLE requisitions ALTER COLUMN priority SET DEFAULT 'standard'"
        );
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('requisitions') || !$schema->hasColumn('requisitions', 'priority')) {
            return;
        }

        DB::connection($this->connection)->statement(
            "ALTER TABLE requisitions ALTER COLUMN priority SET DEFAULT 'routine'"
        );
    }
};
