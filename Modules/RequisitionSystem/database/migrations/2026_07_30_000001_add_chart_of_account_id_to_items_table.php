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

        if (!$schema->hasTable('items')) {
            return;
        }

        if (!$schema->hasColumn('items', 'chart_of_account_id')) {
            $schema->table('items', function (Blueprint $table) {
                $table->foreignId('chart_of_account_id')
                    ->nullable()
                    ->after('requisition_id')
                    ->constrained('chart_of_accounts')
                    ->onDelete('restrict');
            });
        }

        $hasItemsNeedingBackfill = DB::connection($this->connection)
            ->table('items')
            ->whereNull('chart_of_account_id')
            ->exists();

        if ($hasItemsNeedingBackfill) {
            DB::connection($this->connection)
                ->table('chart_of_accounts')
                ->upsert(
                    [['account_no' => '70403', 'description' => 'Miscellaneous']],
                    ['account_no'],
                    ['description']
                );

            $fallbackAccountId = DB::connection($this->connection)
                ->table('chart_of_accounts')
                ->where('account_no', '70403')
                ->value('id');

            DB::connection($this->connection)
                ->table('items')
                ->whereNull('chart_of_account_id')
                ->update(['chart_of_account_id' => $fallbackAccountId]);
        }

        DB::connection($this->connection)->statement(
            'ALTER TABLE items ALTER COLUMN chart_of_account_id SET NOT NULL'
        );

        // Free-text line # / description are replaced by the linked chart of account.
        foreach (['line_item_number', 'description'] as $column) {
            if ($schema->hasColumn('items', $column)) {
                $schema->table('items', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('items')) {
            return;
        }

        if (!$schema->hasColumn('items', 'description')) {
            $schema->table('items', function (Blueprint $table) {
                $table->string('description')->nullable();
            });
        }

        if (!$schema->hasColumn('items', 'line_item_number')) {
            $schema->table('items', function (Blueprint $table) {
                $table->string('line_item_number', 50)->nullable();
            });
        }

        if ($schema->hasColumn('items', 'chart_of_account_id')) {
            $schema->table('items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('chart_of_account_id');
            });
        }
    }
};
