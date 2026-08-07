<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        if (!Schema::connection($this->connection)->hasTable('items')) {
            return;
        }

        if (!Schema::connection($this->connection)->hasColumn('items', 'quantity')) {
            return;
        }

        DB::connection($this->connection)->statement(
            'ALTER TABLE items ALTER COLUMN quantity TYPE NUMERIC(15, 4) USING quantity::numeric(15, 4)'
        );
        DB::connection($this->connection)->statement(
            'ALTER TABLE items ALTER COLUMN quantity SET DEFAULT 0'
        );
    }

    public function down(): void
    {
        if (!Schema::connection($this->connection)->hasTable('items')) {
            return;
        }

        if (!Schema::connection($this->connection)->hasColumn('items', 'quantity')) {
            return;
        }

        DB::connection($this->connection)->statement(
            'ALTER TABLE items ALTER COLUMN quantity TYPE INTEGER USING ROUND(quantity)::integer'
        );
        DB::connection($this->connection)->statement(
            'ALTER TABLE items ALTER COLUMN quantity SET DEFAULT 0'
        );
    }
};
