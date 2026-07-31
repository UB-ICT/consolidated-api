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

        if (!$schema->hasTable('chart_of_accounts')) {
            return;
        }

        if (!$schema->hasColumn('chart_of_accounts', 'parent_id')) {
            $schema->table('chart_of_accounts', function (Blueprint $table) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('chart_of_accounts')
                    ->nullOnDelete();
            });
        }

        // Nest dotted child account numbers under their parent (e.g. 70103.1 → 70103).
        $accounts = DB::connection($this->connection)
            ->table('chart_of_accounts')
            ->orderBy('account_no')
            ->get(['id', 'account_no', 'parent_id']);

        $byAccountNo = $accounts->keyBy('account_no');

        foreach ($accounts as $account) {
            if ($account->parent_id || !str_contains((string) $account->account_no, '.')) {
                continue;
            }

            $parentNo = substr((string) $account->account_no, 0, strrpos((string) $account->account_no, '.'));
            $parent = $byAccountNo->get($parentNo);

            if ($parent) {
                DB::connection($this->connection)
                    ->table('chart_of_accounts')
                    ->where('id', $account->id)
                    ->update(['parent_id' => $parent->id]);
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('chart_of_accounts') || !$schema->hasColumn('chart_of_accounts', 'parent_id')) {
            return;
        }

        $schema->table('chart_of_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
