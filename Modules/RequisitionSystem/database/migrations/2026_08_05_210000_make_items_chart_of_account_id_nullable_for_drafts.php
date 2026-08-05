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

        if (!$schema->hasTable('items') || !$schema->hasColumn('items', 'chart_of_account_id')) {
            return;
        }

        // Drafts may persist incomplete line rows before a budget account is chosen.
        DB::connection($this->connection)->statement(
            'ALTER TABLE items ALTER COLUMN chart_of_account_id DROP NOT NULL'
        );
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('items') || !$schema->hasColumn('items', 'chart_of_account_id')) {
            return;
        }

        $fallbackAccountId = DB::connection($this->connection)
            ->table('chart_of_accounts')
            ->orderBy('id')
            ->value('id');

        if ($fallbackAccountId) {
            DB::connection($this->connection)
                ->table('items')
                ->whereNull('chart_of_account_id')
                ->update(['chart_of_account_id' => $fallbackAccountId]);
        }

        DB::connection($this->connection)->statement(
            'ALTER TABLE items ALTER COLUMN chart_of_account_id SET NOT NULL'
        );
    }
};
