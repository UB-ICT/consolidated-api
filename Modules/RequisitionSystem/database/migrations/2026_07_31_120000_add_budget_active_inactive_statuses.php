<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $db = DB::connection($this->connection);

        $db->table('statuses')->updateOrInsert(
            ['id' => 10],
            ['name' => 'Active']
        );

        $db->table('statuses')->updateOrInsert(
            ['id' => 11],
            ['name' => 'Inactive']
        );

        // Budgets previously used POR-oriented statuses; remap to the budget set.
        $draftId = $db->table('statuses')->where('name', 'Draft')->value('id');
        $inactiveId = $db->table('statuses')->where('name', 'Inactive')->value('id');
        $ccrId = $db->table('statuses')->where('name', 'Cost Center Review')->value('id');
        $rejectedId = $db->table('statuses')->where('name', 'Rejected')->value('id');

        if ($draftId && $ccrId) {
            $db->table('budgets')
                ->where('status_id', $ccrId)
                ->update(['status_id' => $draftId]);
        }

        if ($inactiveId && $rejectedId) {
            $db->table('budgets')
                ->where('status_id', $rejectedId)
                ->update(['status_id' => $inactiveId]);
        }
    }

    public function down(): void
    {
        $db = DB::connection($this->connection);

        $activeId = $db->table('statuses')->where('name', 'Active')->value('id');
        $inactiveId = $db->table('statuses')->where('name', 'Inactive')->value('id');
        $approvedId = $db->table('statuses')->where('name', 'Approved')->value('id');
        $rejectedId = $db->table('statuses')->where('name', 'Rejected')->value('id');

        if ($activeId && $approvedId) {
            $db->table('budgets')
                ->where('status_id', $activeId)
                ->update(['status_id' => $approvedId]);
        }

        if ($inactiveId && $rejectedId) {
            $db->table('budgets')
                ->where('status_id', $inactiveId)
                ->update(['status_id' => $rejectedId]);
        }

        $db->table('statuses')->whereIn('id', [10, 11])->delete();
    }
};
