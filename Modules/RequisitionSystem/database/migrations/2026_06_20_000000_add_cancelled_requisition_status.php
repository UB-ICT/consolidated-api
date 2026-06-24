<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        if (!Schema::connection($this->connection)->hasTable('statuses')) {
            return;
        }

        DB::connection($this->connection)->table('statuses')->updateOrInsert(
            ['id' => 8],
            ['name' => 'Cancelled']
        );
    }

    public function down(): void
    {
        if (!Schema::connection($this->connection)->hasTable('statuses')) {
            return;
        }

        DB::connection($this->connection)
            ->table('statuses')
            ->where('id', 8)
            ->delete();
    }
};
